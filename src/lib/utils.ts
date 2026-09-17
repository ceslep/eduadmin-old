/** Utilidades transversales de la interfaz. */

/** Une clases ignorando valores falsy. */
export function cx(...parts: Array<string | false | null | undefined>): string {
  return parts.filter(Boolean).join(' ');
}

/** Entero con separador de miles en formato es-CO. */
export function formatInt(value: number): string {
  return new Intl.NumberFormat('es-CO').format(value);
}

/** Iniciales para el avatar (máximo dos letras). */
export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

/**
 * Los informes vienen de una base heredada: algunos textos traen saltos de
 * línea y espacios dobles incrustados en medio de la frase.
 */
export function normalizeText(value: string | null | undefined): string {
  return (value ?? '').replace(/\s+/g, ' ').trim();
}

/**
 * Repara texto que quedó con doble codificación (UTF-8 leído como latin1),
 * típico de las tablas heredadas: "INSTITUCIÃ“N" → "INSTITUCIÓN".
 *
 * Solo actúa si detecta los caracteres centinela y si la reinterpretación
 * produce UTF-8 válido; ante la duda devuelve el original.
 */
export function repairMojibake(value: string): string {
  if (!value || !/[ÃÂ]/.test(value)) return value;

  try {
    const bytes = Uint8Array.from(value, (char) => char.charCodeAt(0) & 0xff);
    return new TextDecoder('utf-8', { fatal: true }).decode(bytes);
  } catch {
    return value;
  }
}

/** Texto listo para mostrar: repara codificación y normaliza espacios. */
export function cleanText(value: string | null | undefined): string {
  return normalizeText(repairMojibake(value ?? ''));
}

/** Nombre completo del estudiante: la base trae apellidos primero. */
export function displayName(raw: string | null | undefined): string {
  return cleanText(raw);
}

/** Debounce tipado; útil para la búsqueda incremental. */
export function debounce<A extends unknown[]>(
  fn: (...args: A) => void,
  wait = 300
): ((...args: A) => void) & { cancel: () => void } {
  let timer: ReturnType<typeof setTimeout> | undefined;

  const wrapped = (...args: A) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), wait);
  };

  wrapped.cancel = () => {
    if (timer) clearTimeout(timer);
  };

  return wrapped;
}

/* --- Persistencia segura -------------------------------------------------- */

export function readStored<T>(key: string, fallback: T): T {
  if (typeof localStorage === 'undefined') return fallback;
  try {
    const raw = localStorage.getItem(key);
    return raw === null ? fallback : (JSON.parse(raw) as T);
  } catch {
    return fallback;
  }
}

export function writeStored(key: string, value: unknown): void {
  if (typeof localStorage === 'undefined') return;
  try {
    if (value === null || value === undefined) localStorage.removeItem(key);
    else localStorage.setItem(key, JSON.stringify(value));
  } catch {
    /* almacenamiento lleno o bloqueado: la app sigue funcionando */
  }
}

/**
 * Selección de trabajo (año y estudiante). Va en `sessionStorage` y no en
 * `localStorage` a propósito: en un equipo compartido, la ficha del último
 * estudiante no debe quedar abierta para el siguiente usuario.
 */
export function readSession<T>(key: string, fallback: T): T {
  if (typeof sessionStorage === 'undefined') return fallback;
  try {
    const raw = sessionStorage.getItem(key);
    return raw === null ? fallback : (JSON.parse(raw) as T);
  } catch {
    return fallback;
  }
}

export function writeSession(key: string, value: unknown): void {
  if (typeof sessionStorage === 'undefined') return;
  try {
    if (value === null || value === undefined) sessionStorage.removeItem(key);
    else sessionStorage.setItem(key, JSON.stringify(value));
  } catch {
    /* ignore */
  }
}

/* --- Escala de valoración ------------------------------------------------- */

export type ValoracionTone = 'best' | 'good' | 'ok' | 'low' | 'worst' | 'neutral';

export interface ValoracionMeta {
  /** Etiqueta legible y normalizada. */
  label: string;
  tone: ValoracionTone;
  /** Posición en la escala (mayor = mejor). 0 cuando no se reconoce. */
  rank: number;
}

const VALORACIONES: Record<string, ValoracionMeta> = {
  SOBRESALIENTE: { label: 'Sobresaliente', tone: 'best', rank: 5 },
  SUPERIOR: { label: 'Superior', tone: 'best', rank: 5 },
  EXCELENTE: { label: 'Excelente', tone: 'good', rank: 4 },
  ALTO: { label: 'Alto', tone: 'good', rank: 4 },
  ACEPTABLE: { label: 'Aceptable', tone: 'ok', rank: 3 },
  BASICO: { label: 'Básico', tone: 'ok', rank: 3 },
  INSUFICIENTE: { label: 'Insuficiente', tone: 'low', rank: 2 },
  BAJO: { label: 'Bajo', tone: 'low', rank: 2 },
  DEFICIENTE: { label: 'Deficiente', tone: 'worst', rank: 1 },
};

const MARCADOR_A_VALORACION: Record<string, string> = {
  S: 'SOBRESALIENTE',
  E: 'EXCELENTE',
  A: 'ACEPTABLE',
  I: 'INSUFICIENTE',
  D: 'DEFICIENTE',
};

const SIN_DATO: ValoracionMeta = { label: 'Sin dato', tone: 'neutral', rank: 0 };

/**
 * Interpreta el campo de valoración de un área.
 *
 * En la base heredada unas pocas filas quedaron desalineadas y el campo de
 * valoración terminó conteniendo un fragmento de la descripción. En esos
 * casos la escala real viaja como sufijo `(S)`, `(E)`, `(A)`, `(I)` o `(D)`,
 * así que se recupera de ahí antes de rendirse.
 */
export function valoracionMeta(raw: string | null | undefined): ValoracionMeta {
  const clean = normalizeText(raw).toUpperCase();
  if (clean === '') return SIN_DATO;

  if (VALORACIONES[clean]) return VALORACIONES[clean];

  const marcador = clean.match(/\((S|E|A|I|D)\)\s*\.?\s*$/);
  if (marcador) {
    const mapped = VALORACIONES[MARCADOR_A_VALORACION[marcador[1]]];
    if (mapped) return { ...mapped, label: `${mapped.label} (?)` };
  }

  return { ...SIN_DATO, label: normalizeText(raw) || 'Sin dato' };
}

/** Resumen por tono para mostrar el consolidado del informe. */
export function summarizeValoraciones(
  items: ReadonlyArray<{ valoracion: string }>
): Array<{ meta: ValoracionMeta; total: number }> {
  const buckets = new Map<string, { meta: ValoracionMeta; total: number }>();

  for (const item of items) {
    const meta = valoracionMeta(item.valoracion);
    const key = `${meta.rank}:${meta.label}`;
    const bucket = buckets.get(key);
    if (bucket) bucket.total += 1;
    else buckets.set(key, { meta, total: 1 });
  }

  return [...buckets.values()].sort((a, b) => b.meta.rank - a.meta.rank);
}
