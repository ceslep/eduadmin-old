<script lang="ts">
  import { onMount } from 'svelte';
  import { ApiError, getProfile } from './lib/api';
  import { session } from './lib/stores.svelte';
  import { toast } from './lib/toast.svelte';
  import AppHeader from './lib/components/AppHeader.svelte';
  import Toaster from './lib/components/Toaster.svelte';
  import Dashboard from './lib/Dashboard.svelte';
  import Login from './lib/Login.svelte';

  /**
   * Antes de mostrar el panel se valida el token guardado: si ya expiró,
   * el usuario ve el login en vez de un panel que falla en cada petición.
   */
  let booting = $state(session.isAuthenticated);

  onMount(async () => {
    if (!session.isAuthenticated) {
      booting = false;
      return;
    }

    const token = session.token;
    if (!token) {
      booting = false;
      return;
    }

    try {
      const user = await getProfile();
      session.signIn(user, token);
    } catch (error) {
      if (error instanceof ApiError && error.status === 0) {
        // El servidor no responde: no se cierra la sesión, se avisa.
        toast.error('Sin conexión con el servidor', error.message);
      } else if (!session.isAuthenticated) {
        toast.info('Tu sesión expiró', 'Vuelve a iniciar sesión para continuar.');
      }
    } finally {
      booting = false;
    }
  });
</script>

<div class="paper-backdrop"></div>

<a class="skip-link" href="#contenido">Saltar al contenido</a>

{#if booting}
  <div class="relative z-10 flex min-h-screen flex-col items-center justify-center gap-3">
    <span
      class="spinner h-7 w-7 rounded-full border-2 border-line-strong border-t-accent"
      aria-hidden="true"
    ></span>
    <p class="text-sm text-ink-muted">Verificando sesión…</p>
  </div>
{:else}
  {#if session.isAuthenticated}
    <AppHeader />
  {/if}

  <main
    id="contenido"
    class="app-container relative z-10 py-8 lg:py-10"
  >
    {#if session.isAuthenticated}
      <Dashboard />
    {:else}
      <Login />
    {/if}
  </main>

  {#if session.isAuthenticated}
    <footer class="app-container relative z-10 pb-8">
      <p class="border-t border-line pt-4 text-xs text-ink-subtle">
        EduAdmin · Sistema de certificados académicos. Los datos provienen del archivo histórico
        institucional.
      </p>
    </footer>
  {/if}
{/if}

<Toaster />
