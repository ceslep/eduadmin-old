import { writable } from 'svelte/store';

interface User {
  id: number;
  username: string;
  email: string;
  role: string;
}

function createAuthStore() {
  const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('user') : null;
  const { subscribe, set, update } = writable<User | null>(stored ? JSON.parse(stored) : null);

  return {
    subscribe,
    login: (user: User, token: string) => {
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      set(user);
    },
    logout: () => {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      set(null);
    },
    set,
  };
}

export const user = createAuthStore();

type Theme = 'light' | 'dark';

function createThemeStore() {
  const stored = (typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null) as Theme | null;
  const initial: Theme = stored || 'dark';
  const { subscribe, set, update } = writable<Theme>(initial);

  if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('dark', initial === 'dark');
  }

  return {
    subscribe,
    toggle: () => {
      update((current) => {
        const next: Theme = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        document.documentElement.classList.toggle('dark', next === 'dark');
        return next;
      });
    },
    set: (value: Theme) => {
      localStorage.setItem('theme', value);
      document.documentElement.classList.toggle('dark', value === 'dark');
      set(value);
    },
  };
}

export const theme = createThemeStore();
