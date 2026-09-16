const API_BASE = '/server';

interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T;
}

export async function api<T = unknown>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const token = localStorage.getItem('token');

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...((options.headers as Record<string, string>) || {}),
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok || !data.success) {
    throw new Error(data.message || 'Error en la solicitud');
  }

  return data;
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

export async function login(username: string, password: string): Promise<LoginResponse> {
  const response = await api<LoginResponse>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ username, password }),
  });
  return response.data;
}

export async function getProfile() {
  const response = await api('/auth/profile');
  return response.data;
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
  informe: string;
  parsed_informe: InformeItem[];
  director_de_grupo: string;
  p1: string;
  p2: string;
  p3: string;
  observaciones: string;
}

export async function getInforme(estudiante: string, anio: string): Promise<InformeData> {
  const response = await api<InformeData>(`/informes/${estudiante}/${anio}`);
  return response.data;
}

export async function downloadInforme(estudiante: string, anio: string, documento: string, nombre: string): Promise<void> {
  const token = localStorage.getItem('token');

  const response = await fetch(`${API_BASE}/informes/${estudiante}/${anio}/download`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify({ documento, nombre }),
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({ message: 'Error al descargar' }));
    throw new Error(errorData.message || 'Error al descargar el informe');
  }

  const blob = await response.blob();
  const contentDisposition = response.headers.get('Content-Disposition');
  let filename = 'Informe.docx';
  if (contentDisposition) {
    const match = contentDisposition.match(/filename="?([^"]+)"?/);
    if (match) filename = match[1];
  }

  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}
