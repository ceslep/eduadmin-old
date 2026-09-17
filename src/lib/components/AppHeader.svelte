<script lang="ts">
  import { session, theme } from '../stores.svelte';
  import { initials } from '../utils';
  import Icon from './Icon.svelte';

  const roleLabels: Record<string, string> = {
    admin: 'Administrador',
    profesor: 'Profesor',
    alumno: 'Alumno',
  };

  let menuOpen = $state(false);
  let menuEl = $state<HTMLDivElement | null>(null);
  let triggerEl = $state<HTMLButtonElement | null>(null);

  const user = $derived(session.user);
  const roleLabel = $derived(roleLabels[user?.role ?? ''] ?? 'Usuario');

  function closeMenu(returnFocus = false) {
    menuOpen = false;
    if (returnFocus) queueMicrotask(() => triggerEl?.focus());
  }

  function onPointerDown(event: PointerEvent) {
    if (!menuOpen) return;
    const target = event.target as Node | null;
    if (!target) return;
    if (menuEl?.contains(target) || triggerEl?.contains(target)) return;
    closeMenu();
  }

  function onKeydown(event: KeyboardEvent) {
    if (event.key !== 'Escape' || !menuOpen) return;
    closeMenu(true);
  }

  function signOut() {
    closeMenu();
    session.signOut();
  }
</script>

<svelte:window onpointerdown={onPointerDown} onkeydown={onKeydown} />

<header class="sticky top-0 z-40 border-b border-line bg-canvas/85 backdrop-blur-md">
  <div class="app-container flex h-16 items-center justify-between gap-4">
    <!-- Marca -->
    <div class="flex min-w-0 items-center gap-3">
      <span
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-navy-900 text-gold-400 shadow-xs"
        aria-hidden="true"
      >
        <Icon name="graduation" size={19} stroke={1.7} />
      </span>
      <span class="min-w-0">
        <span class="block text-base leading-tight font-semibold text-ink">
          EduAdmin
        </span>
        <span class="block truncate text-[0.6875rem] tracking-[0.12em] text-ink-subtle uppercase">
          Certificados académicos
        </span>
      </span>
    </div>

    <div class="flex items-center gap-2">
      <!-- Tema -->
      <button
        type="button"
        class="btn btn--ghost btn--icon"
        onclick={() => theme.toggle()}
        aria-label={theme.isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'}
        title={theme.isDark ? 'Tema claro' : 'Tema oscuro'}
      >
        <Icon name={theme.isDark ? 'sun' : 'moon'} size={18} />
      </button>

      <!-- Usuario -->
      <div class="relative">
        <button
          bind:this={triggerEl}
          type="button"
          class="btn btn--ghost gap-2 pr-3 pl-1.5"
          aria-expanded={menuOpen}
          aria-controls="user-panel"
          onclick={() => (menuOpen = !menuOpen)}
        >
          <span
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-sm bg-accent-soft text-[0.6875rem] font-bold text-accent"
            aria-hidden="true"
          >
            {initials(user?.username ?? '')}
          </span>
          <span class="hidden max-w-[10rem] truncate text-sm font-medium sm:block">
            {user?.username ?? 'Usuario'}
          </span>
          <Icon name="chevron-down" size={15} class="text-ink-subtle" />
        </button>

        {#if menuOpen}
          <div
            bind:this={menuEl}
            id="user-panel"
            class="absolute right-0 z-50 mt-2 w-72 origin-top-right animate-scale-in rounded-lg border border-line bg-surface p-1.5 shadow-lg"
          >
            <div class="border-b border-line px-3 py-3">
              <p class="truncate font-semibold text-ink">{user?.username ?? 'Usuario'}</p>
              <p class="mt-0.5 truncate text-xs text-ink-muted">{user?.email ?? ''}</p>
              <p
                class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-accent bg-accent-soft px-2 py-0.5 text-[0.6875rem] font-bold tracking-wide text-accent uppercase"
              >
                <Icon name="shield" size={12} />
                {roleLabel}
              </p>
            </div>

            <button
              type="button"
              class="btn btn--ghost mt-1 w-full justify-start"
              onclick={() => theme.followSystem()}
              disabled={!theme.isOverridden}
            >
              <Icon name="refresh" size={16} />
              Seguir el tema del sistema
            </button>

            <button
              type="button"
              class="btn btn--ghost w-full justify-start text-danger hover:bg-danger-soft hover:text-danger"
              onclick={signOut}
            >
              <Icon name="logout" size={16} />
              Cerrar sesión
            </button>
          </div>
        {/if}
      </div>
    </div>
  </div>
</header>
