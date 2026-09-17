/**
 * Set de iconos de línea, 24x24, trazo uniforme.
 *
 * Se guardan como datos de trazado (`d`) en vez de como componentes SVG
 * sueltos para que `Icon.svelte` sea el único punto de renderizado: así el
 * grosor, el tamaño y el color se controlan en un solo lugar.
 */
export const ICON_PATHS = {
  search: ['M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z', 'm21 21-4.35-4.35'],
  close: ['M6 6l12 12', 'M18 6 6 18'],
  'chevron-down': ['m6 9 6 6 6-6'],
  'chevron-up': ['m6 15 6-6 6 6'],
  'chevron-right': ['m9 6 6 6-6 6'],
  check: ['m5 13 4 4 10-10'],
  sun: [
    'M12 4V2',
    'M12 22v-2',
    'M4 12H2',
    'M22 12h-2',
    'M6.3 6.3 4.9 4.9',
    'M19.1 19.1l-1.4-1.4',
    'M17.7 6.3l1.4-1.4',
    'M4.9 19.1l1.4-1.4',
    'M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
  ],
  moon: ['M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z'],
  user: [
    'M20 21v-2a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v2',
    'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  ],
  lock: ['M7 11V8a5 5 0 0 1 10 0v3', 'M5 11h14v10H5z'],
  eye: [
    'M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z',
    'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
  ],
  'eye-off': [
    'M3 3l18 18',
    'M10.6 5.7A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.4 4.2',
    'M6.3 7.4A17.4 17.4 0 0 0 2.5 12S6 18.5 12 18.5c1.4 0 2.6-.3 3.7-.8',
    'M9.9 9.9a3 3 0 0 0 4.2 4.2',
  ],
  download: ['M12 3v12', 'm7 10 5 5 5-5', 'M4 20h16'],
  file: [
    'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z',
    'M14 3v5h5',
    'M9 13h6',
    'M9 17h4',
  ],
  calendar: ['M4 6h16v15H4z', 'M4 10h16', 'M8 3v4', 'M16 3v4'],
  graduation: ['m2 9 10-5 10 5-10 5z', 'M6 11.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5'],
  logout: ['M15 12H4', 'm8 8-4 4 4 4', 'M12 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5'],
  alert: ['M12 3 2 20h20L12 3Z', 'M12 10v4', 'M12 17h.01'],
  info: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M12 11v5', 'M12 8h.01'],
  refresh: ['M20 12a8 8 0 1 1-2.3-5.6', 'M20 4v5h-5'],
  copy: ['M8 8h11v11H8z', 'M5 16V5a1 1 0 0 1 1-1h11'],
  hash: ['M4 9h16', 'M4 15h16', 'M10 3 8 21', 'M16 3l-2 18'],
  award: ['M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z', 'm8.2 13.9-1.2 7 5-3 5 3-1.2-7'],
  shield: ['M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6Z'],
  'arrow-left': ['M20 12H4', 'm10 6-6 6 6 6'],
  inbox: ['M4 13h4l1.5 3h5L16 13h4', 'M4 13 6.5 4h11L20 13v7H4z'],
} as const;

export type IconName = keyof typeof ICON_PATHS;
