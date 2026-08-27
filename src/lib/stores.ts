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
