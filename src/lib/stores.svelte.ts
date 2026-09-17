import { readStored, writeStored } from './utils';

export interface AuthUser {
  id: number;
  username: string;
  email: string;
  role: string;
}

const USER_KEY = 'eduadmin.user';
const TOKEN_KEY = 'eduadmin.token';
const THEME_KEY = 'eduadmin.theme';

/* ==========================================================================
   Sesión
   ========================================================================== */

let currentUser = $state<AuthUser | null>(readStored<AuthUser | null>(USER_KEY, null));
let currentToken = $state<string | null>(readStored<string | null>(TOKEN_KEY, null));

export const session = {
  get user(): AuthUser | null {
    return currentUser;
  },
  get token(): string | null {
    return currentToken;
  },
  get isAuthenticated(): boolean {
    return currentUser !== null && currentToken !== null;
  },
  signIn(user: AuthUser, token: string): void {
    currentUser = user;
    currentToken = token;
    writeStored(USER_KEY, user);
    writeStored(TOKEN_KEY, token);
  },
  signOut(): void {
    currentUser = null;
    currentToken = null;
    writeStored(USER_KEY, null);
    writeStored(TOKEN_KEY, null);
  },
};

/* ==========================================================================
   Tema
   ========================================================================== */

export type ThemeMode = 'light' | 'dark';

function systemPrefersDark(): boolean {
  return (
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-color-scheme: dark)').matches
  );
}

function storedTheme(): ThemeMode | null {
  const stored = readStored<ThemeMode | null>(THEME_KEY, null);
  return stored === 'light' || stored === 'dark' ? stored : null;
}

function applyTheme(mode: ThemeMode): void {
  if (typeof document === 'undefined') return;
  document.documentElement.classList.toggle('dark', mode === 'dark');

  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute('content', mode === 'dark' ? '#0a1626' : '#f7f4ed');
}

const initialTheme: ThemeMode = storedTheme() ?? (systemPrefersDark() ? 'dark' : 'light');
let currentTheme = $state<ThemeMode>(initialTheme);

export const theme = {
  get current(): ThemeMode {
    return currentTheme;
  },
  get isDark(): boolean {
    return currentTheme === 'dark';
  },
  /** true cuando el usuario eligió un tema explícitamente. */
  get isOverridden(): boolean {
    return storedTheme() !== null;
  },
  set(mode: ThemeMode): void {
    currentTheme = mode;
    writeStored(THEME_KEY, mode);
    applyTheme(mode);
  },
  toggle(): void {
    theme.set(currentTheme === 'dark' ? 'light' : 'dark');
  },
  /** Vuelve a seguir la preferencia del sistema operativo. */
  followSystem(): void {
    writeStored(THEME_KEY, null);
    currentTheme = systemPrefersDark() ? 'dark' : 'light';
    applyTheme(currentTheme);
  },
};

// Si el usuario no ha elegido tema, acompañar los cambios del sistema.
if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (theme.isOverridden) return;
    currentTheme = event.matches ? 'dark' : 'light';
    applyTheme(currentTheme);
  });
}

// El script de `index.html` ya puso la clase antes del primer pintado; aquí
// solo se sincroniza la meta `theme-color`.
applyTheme(initialTheme);
