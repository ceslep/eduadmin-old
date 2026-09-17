/**
 * Notificaciones flotantes.
 *
 * Estado a nivel de módulo (singleton) para que cualquier componente pueda
 * notificar sin recibir props ni depender de un árbol de contexto.
 */

export type ToastTone = 'success' | 'error' | 'info';

export interface Toast {
  id: number;
  tone: ToastTone;
  title: string;
  description?: string;
}

let items = $state<Toast[]>([]);
let nextId = 1;

const timers = new Map<number, ReturnType<typeof setTimeout>>();

/** Límite defensivo: evita que una ráfaga de errores tape la interfaz. */
const MAX_VISIBLE = 4;

function dismiss(id: number): void {
  const timer = timers.get(id);
  if (timer) {
    clearTimeout(timer);
    timers.delete(id);
  }
  items = items.filter((item) => item.id !== id);
}

function push(tone: ToastTone, title: string, description?: string, ttl = 6000): number {
  const id = nextId++;
  items = [...items, { id, tone, title, description }];

  // Descartar los más antiguos si se acumulan.
  while (items.length > MAX_VISIBLE) {
    const oldest = items[0];
    if (!oldest) break;
    dismiss(oldest.id);
  }

  if (ttl > 0) {
    timers.set(
      id,
      setTimeout(() => dismiss(id), ttl)
    );
  }

  return id;
}

export const toasts = {
  get items(): Toast[] {
    return items;
  },
  dismiss,
};

export const toast = {
  success: (title: string, description?: string) => push('success', title, description),
  error: (title: string, description?: string) => push('error', title, description, 9000),
  info: (title: string, description?: string) => push('info', title, description),
};
