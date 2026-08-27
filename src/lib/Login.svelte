<script lang="ts">
  import { login as apiLogin } from './api';
  import { user } from './stores';

  let username = $state('');
  let password = $state('');
  let loading = $state(false);
  let error = $state('');
  let showPassword = $state(false);
  let shakeError = $state(false);

  async function handleLogin() {
    if (!username || !password) {
      error = 'Completa todos los campos';
      shakeError = true;
      setTimeout(() => (shakeError = false), 500);
      return;
    }

    loading = true;
    error = '';

    try {
      const data = await apiLogin(username, password);
      user.login(data.user, data.token);
    } catch (e: unknown) {
      error = e instanceof Error ? e.message : 'Error al iniciar sesion';
      shakeError = true;
      setTimeout(() => (shakeError = false), 500);
    } finally {
      loading = false;
    }
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') handleLogin();
  }
</script>

<div class="relative z-10 flex min-h-screen items-center justify-center px-4">
  <div class="w-full max-w-md {shakeError ? 'animate-shake' : ''}">
    <!-- Card -->
    <div class="glass-card rounded-3xl p-8 shadow-2xl backdrop-blur-xl border border-white/10">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 shadow-lg shadow-purple-500/30 mb-4 animate-float">
          <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white tracking-tight">EduAdmin</h1>
        <p class="text-white/50 mt-2 text-sm">Panel de administracion educativa</p>
      </div>

      <!-- Error -->
      {#if error}
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm text-center">
          {error}
        </div>
      {/if}

      <!-- Form -->
      <form onsubmit={(e) => { e.preventDefault(); handleLogin(); }} class="space-y-5">
        <!-- Username -->
        <div class="group">
          <label for="username" class="block text-xs font-medium text-white/40 uppercase tracking-wider mb-2 ml-1">Usuario</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-white/30 group-focus-within:text-purple-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" />
              </svg>
            </div>
            <input
              id="username"
              type="text"
              bind:value={username}
              onkeydown={handleKeydown}
              placeholder="Ingresa tu usuario"
              class="input-glass w-full pl-12 pr-4 py-3.5 rounded-xl text-white placeholder-white/30 outline-none text-sm"
              disabled={loading}
              autocomplete="username"
            />
          </div>
        </div>

        <!-- Password -->
        <div class="group">
          <label for="password" class="block text-xs font-medium text-white/40 uppercase tracking-wider mb-2 ml-1">Contrasena</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-white/30 group-focus-within:text-purple-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
              </svg>
            </div>
            <input
              id="password"
              type={showPassword ? 'text' : 'password'}
              bind:value={password}
              onkeydown={handleKeydown}
              placeholder="Ingresa tu contrasena"
              class="input-glass w-full pl-12 pr-12 py-3.5 rounded-xl text-white placeholder-white/30 outline-none text-sm"
              disabled={loading}
              autocomplete="current-password"
            />
            <button
              type="button"
              onclick={() => (showPassword = !showPassword)}
              class="absolute inset-y-0 right-0 pr-4 flex items-center text-white/30 hover:text-white/60 transition-colors"
            >
              {#if showPassword}
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
              {:else}
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              {/if}
            </button>
          </div>
        </div>

        <!-- Button -->
        <button
          type="submit"
          disabled={loading}
          class="btn-gradient w-full py-3.5 rounded-xl text-white font-semibold text-sm tracking-wide transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {#if loading}
            <span class="inline-flex items-center gap-2">
              <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Ingresando...
            </span>
          {:else}
            Iniciar Sesion
          {/if}
        </button>
      </form>

      <!-- Footer -->
      <div class="mt-6 text-center">
        <p class="text-white/20 text-xs">&copy; 2026 EduAdmin. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</div>
