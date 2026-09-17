import { session } from './stores.svelte';

const API_BASE = '/server';

/* ==========================================================================
   Tipos
   ========================================================================== */

export interface YearOption {
  anio: string;
  total: number;
}

export interface MatriculaRecord {
  ind: number;
  nombres2: string | null;
  identificacion: string | null;
  aula: string | null;
  nivel: number | null;
  codigo: string | number | null;
  [key: string]: unknown;
}

export interface InformeItem {
  numero: string;
  area: string;
  descripcion: string;
  valoracion: string;
}

export interface InformeData {
  ind: number;
  anio: string;
  estudiante: string;
  aula: string | null;
  informeno: number | null;
  p1: string | null;
  p2: string | null;
  p3: string | null;
  director_de_grupo: string | null;
  informe: string;
  observaciones: string | null;
  parsed_informe: InformeItem[];
}

export interface LoginResponse {
  token: string;
  user: {
    id: number;
    username: string;
    email: string;
    role: string;
  };
}

/* ==========================================================================
   Errores
   ========================================================================== */

export class ApiError extends Error {
  readonly status: number;

  constructor(message: string, status = 0) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

function statusMessage(status: number): string {
  switch (status) {
    case 0:
      return 'Sin conexión con el servidor.';
    case 401:
      return 'Tu sesión expiró. Vuelve a iniciar sesión.';
    case 403:
      return 'No tienes permisos para esta acción.';
    case 404:
      return 'No se encontró la información solicitada.';
    case 500:
      return 'El servidor tuvo un error inesperado.';
    default:
      return `La solicitud falló (código ${status}).`;
  }
}

function stripTags(value: string): string {
  return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
}

interface ApiEnvelope<T> {
  success: boolean;
  message?: string;
  data: T;
}

/**
 * Lee la respuesta del backend sin asumir que es JSON.
 *
 * Un error fatal de PHP llega como HTML con código 200/500; sin esto, el
 * cliente fallaba con un "Unexpected token '<'" imposible de diagnosticar.
 */
async function readEnvelope<T>(response: Response): Promise<ApiEnvelope<T>> {
  const raw = await response.text();

  if (raw.trim() === '') {
    return { success: response.ok, message: '', data: null as T };
  }

  try {
    return JSON.parse(raw) as ApiEnvelope<T>;
  } catch {
    const fatal = /Fatal error<\/b>:\s*(.+?)\s+in\s/i.exec(raw.replace(/<br\s*\/?>/gi, ' '));
    const detail = fatal ? stripTags(fatal[1]) : '';
    throw new ApiError(
      detail
        ? `Error del servidor: ${detail}`
        : `El servidor devolvió una respuesta inesperada (código ${response.status}).`,
      response.status
    );
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
  signal?: AbortSignal;
  /** Añade el token de sesión. */
  auth?: boolean;
}

async function request<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, signal, auth = true } = options;

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (auth && session.token) headers.Authorization = `Bearer ${session.token}`;

  let response: Response;
  try {
    response = await fetch(`${API_BASE}${endpoint}`, {
      method,
      headers,
      signal,
      body: body === undefined ? undefined : JSON.stringify(body),
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') throw error;
    throw new ApiError(
      'No se pudo contactar el servidor. Verifica que Apache y MySQL estén en ejecución.',
      0
    );
  }

  const payload = await readEnvelope<T>(response);

  if (!response.ok || !payload.success) {
    if (response.status === 401) session.signOut();
    throw new ApiError(payload.message?.trim() || statusMessage(response.status), response.status);
  }

  return payload.data;
}

/* ==========================================================================
   Endpoints
   ========================================================================== */

export function login(username: string, password: string): Promise<LoginResponse> {
  return request<LoginResponse>('/auth/login', {
    method: 'POST',
    body: { username, password },
    auth: false,
  });
}

export function getProfile(): Promise<LoginResponse['user']> {
  return request<LoginResponse['user']>('/auth/profile');
}

export function getYears(): Promise<YearOption[]> {
  return request<YearOption[]>('/years');
}

export function searchMatricula(query: string, signal?: AbortSignal): Promise<MatriculaRecord[]> {
  return request<MatriculaRecord[]>('/matricula/search', {
    method: 'POST',
    body: { query },
    signal,
  });
}

export function getInforme(estudiante: string, anio: string): Promise<InformeData> {
  const e = encodeURIComponent(estudiante);
  const a = encodeURIComponent(anio);
  return request<InformeData>(`/informes/${e}/${a}`);
}

export interface GeneratedFile {
  blob: Blob;
  filename: string;
}

/** Extensión del nombre de archivo que envía el backend. */
function filenameFrom(disposition: string | null, fallback: string): string {
  if (!disposition) return fallback;

  const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
  if (utf8) return decodeURIComponent(utf8[1].replace(/^"|"$/g, ''));

  const plain = /filename="?([^";]+)"?/i.exec(disposition);
  return plain ? plain[1].trim() : fallback;
}

/**
 * Genera el certificado en el servidor y devuelve el archivo listo para
 * guardar. No dispara la descarga: de eso se encarga quien la invoca, para
 * poder mostrar el resultado (o el error) al usuario.
 */
export async function generateInformeFile(
  estudiante: string,
  anio: string,
  documento: string,
  nombre: string
): Promise<GeneratedFile> {
  const e = encodeURIComponent(estudiante);
  const a = encodeURIComponent(anio);

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept:
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/json',
  };
  if (session.token) headers.Authorization = `Bearer ${session.token}`;

  let response: Response;
  try {
    response = await fetch(`${API_BASE}/informes/${e}/${a}/download`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ documento, nombre }),
    });
  } catch {
    throw new ApiError(
      'No se pudo contactar el servidor. Verifica que Apache y MySQL estén en ejecución.',
      0
    );
  }

  if (!response.ok) {
    // El backend responde JSON cuando rechaza la petición.
    const payload = await readEnvelope<null>(response).catch(() => null);
    if (response.status === 401) session.signOut();
    throw new ApiError(
      payload?.message?.trim() || statusMessage(response.status),
      response.status
    );
  }

  const blob = await response.blob();
  const fallback = `Certificado_${estudiante}_${anio}.docx`;

  return {
    blob,
    filename: filenameFrom(response.headers.get('Content-Disposition'), fallback),
  };
}

/** Guarda un archivo generado en el equipo del usuario. */
export function saveFile(file: GeneratedFile): void {
  const url = URL.createObjectURL(file.blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = file.filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}
