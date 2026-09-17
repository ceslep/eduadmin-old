<script lang="ts">
  import { login as apiLogin } from './api';
  import { session, theme } from './stores.svelte';
  import Icon from './components/Icon.svelte';

  let username = $state('');
  let password = $state('');
  let showPassword = $state(false);
  let loading = $state(false);
  let error = $state('');
  let usernameEl = $state<HTMLInputElement | null>(null);

  async function submit() {
    if (loading) return;

    if (username.trim() === '' || password === '') {
      error = 'Completa usuario y contraseña para continuar.';
      return;
    }

    loading = true;
    error = '';

    try {
      const data = await apiLogin(username.trim(), password);
      session.signIn(data.user, data.token);
    } catch (caught) {
      error = caught instanceof Error ? caught.message : 'No se pudo iniciar sesión.';
    } finally {
      loading = false;
    }
  }

  // El campo de usuario recibe el foco al entrar.
  $effect(() => {
    usernameEl?.focus();
  });
</script>

<div class="flex min-h-screen items-center justify-center py-10">
  <div class="w-full max-w-5xl">
    <div
      class="grid overflow-hidden rounded-xl border border-line bg-surface shadow-lg lg:grid-cols-[1.05fr_1fr]"
    >
      <!-- Panel institucional -->
      <aside class="panel-navy relative hidden flex-col justify-between gap-10 p-10 lg:flex">
        <div class="flex items-center gap-3">
          <span
            class="flex h-10 w-10 items-center justify-center rounded-md border border-gold-400/40 bg-gold-400/10 text-gold-300"
            aria-hidden="true"
          >
            <Icon name="graduation" size={21} stroke={1.7} />
          </span>
          <span>
            <span class="block text-lg leading-tight font-semibold text-white">
              EduAdmin
            </span>
            <span class="block text-[0.6875rem] tracking-[0.16em] text-gold-300/80 uppercase">
              Secretaría académica
            </span>
          </span>
        </div>

        <div>
          <h2 class="max-w-[24ch] text-3xl leading-tight font-semibold text-white">
            Certificados académicos del archivo histórico.
          </h2>
          <p class="mt-4 max-w-[38ch] text-sm leading-relaxed text-white/60">
            Consulta los informes por año lectivo y emite el certificado oficial en Word con los
            datos del estudiante.
          </p>

          <ul class="mt-8 flex flex-col gap-3 text-sm text-white/70">
            <li class="flex items-center gap-2.5">
              <Icon name="calendar" size={16} class="shrink-0 text-gold-400" />
              Informes desde 2004 hasta hoy
            </li>
            <li class="flex items-center gap-2.5">
              <Icon name="search" size={16} class="shrink-0 text-gold-400" />
              Búsqueda por nombre o número IND
            </li>
            <li class="flex items-center gap-2.5">
              <Icon name="file" size={16} class="shrink-0 text-gold-400" />
              Plantilla institucional en Word
            </li>
          </ul>
        </div>

        <p class="text-xs text-white/35">
          Uso restringido al personal autorizado de la institución.
        </p>
      </aside>

      <!-- Formulario -->
      <div class="flex flex-col p-8 sm:p-10">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold text-ink">Iniciar sesión</h1>
            <p class="mt-1.5 text-sm text-ink-muted">
              Ingresa con tu cuenta institucional.
            </p>
          </div>
          <button
            type="button"
            class="btn btn--ghost btn--icon shrink-0"
            onclick={() => theme.toggle()}
            aria-label={theme.isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'}
          >
            <Icon name={theme.isDark ? 'sun' : 'moon'} size={18} />
          </button>
        </div>

        {#if error}
          <div class="notice notice--danger mt-6" role="alert">
            <Icon name="alert" size={18} class="mt-0.5 shrink-0" />
            <p>{error}</p>
          </div>
        {/if}

        <form
          class="mt-6 flex flex-1 flex-col gap-5"
          onsubmit={(event) => {
            event.preventDefault();
            submit();
          }}
          novalidate
        >
          <div class="field">
            <label class="field__label" for="username">Usuario</label>
            <div class="field__control">
              <span class="field__icon"><Icon name="user" size={18} /></span>
              <input
                bind:this={usernameEl}
                id="username"
                class="field__input field__input--with-icon"
                type="text"
                bind:value={username}
                placeholder="usuario.institucional"
                autocomplete="username"
                autocapitalize="off"
                spellcheck="false"
                disabled={loading}
              />
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="password">Contraseña</label>
            <div class="field__control">
              <span class="field__icon"><Icon name="lock" size={18} /></span>
              <input
                id="password"
                class="field__input field__input--with-icon field__input--with-action"
                type={showPassword ? 'text' : 'password'}
                bind:value={password}
                placeholder="••••••••"
                autocomplete="current-password"
                disabled={loading}
              />
              <button
                type="button"
                class="field__action"
                onclick={() => (showPassword = !showPassword)}
                aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                aria-pressed={showPassword}
                tabindex={-1}
              >
                <Icon name={showPassword ? 'eye-off' : 'eye'} size={18} />
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn--accent btn--lg mt-1 w-full" disabled={loading}>
            {#if loading}
              <span
                class="spinner h-4 w-4 rounded-full border-2 border-current border-t-transparent"
                aria-hidden="true"
              ></span>
              Verificando…
            {:else}
              <Icon name="logout" size={17} />
              Entrar al panel
            {/if}
          </button>
        </form>

        <p class="mt-8 border-t border-line pt-5 text-xs leading-relaxed text-ink-subtle">
          ¿Problemas para ingresar? Contacta a la coordinación académica para restablecer tu
          contraseña.
        </p>
      </div>
    </div>
  </div>
</div>
