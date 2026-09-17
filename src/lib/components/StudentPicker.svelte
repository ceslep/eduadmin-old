<script lang="ts">
  import { onDestroy } from 'svelte';
  import { searchMatricula, type MatriculaRecord } from '../api';
  import { debounce, displayName, initials } from '../utils';
  import Icon from './Icon.svelte';

  interface Props {
    open: boolean;
    onclose: () => void;
    onselect: (record: MatriculaRecord) => void;
  }

  let { open, onclose, onselect }: Props = $props();

  /** Debajo de 2 caracteres la consulta devuelve demasiado ruido. */
  const MIN_CHARS = 2;

  const listId = 'student-search-results';
  const optionId = (index: number) => `student-option-${index}`;

  let dialogEl = $state<HTMLDialogElement | null>(null);
  let inputEl = $state<HTMLInputElement | null>(null);

  let query = $state('');
  let results = $state<MatriculaRecord[]>([]);
  let status = $state<'idle' | 'loading' | 'done' | 'error'>('idle');
  let errorMessage = $state('');
  let activeIndex = $state(-1);

  let controller: AbortController | null = null;

  const showList = $derived(status === 'done' && results.length > 0);

  async function runSearch(term: string) {
    controller?.abort();
    const value = term.trim();

    if (value.length < MIN_CHARS) {
      results = [];
      status = 'idle';
      activeIndex = -1;
      return;
    }

    controller = new AbortController();
    status = 'loading';
    errorMessage = '';

    try {
      const data = await searchMatricula(value, controller.signal);
      results = data;
      status = 'done';
      activeIndex = data.length > 0 ? 0 : -1;
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') return;
      results = [];
      status = 'error';
      activeIndex = -1;
      errorMessage = error instanceof Error ? error.message : 'No se pudo completar la búsqueda.';
    }
  }

  const scheduleSearch = debounce((term: string) => void runSearch(term), 320);

  function reset() {
    scheduleSearch.cancel();
    controller?.abort();
    controller = null;
    query = '';
    results = [];
    status = 'idle';
    errorMessage = '';
    activeIndex = -1;
  }

  function close() {
    onclose();
  }

  function choose(index = activeIndex) {
    const record = results[index];
    if (!record) return;
    onselect(record);
    onclose();
  }

  function move(delta: number) {
    if (results.length === 0) return;
    const next =
      activeIndex < 0 ? 0 : (activeIndex + delta + results.length) % results.length;
    activeIndex = next;
  }

  function onKeydown(event: KeyboardEvent) {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        move(1);
        break;
      case 'ArrowUp':
        event.preventDefault();
        move(-1);
        break;
      case 'Enter':
        event.preventDefault();
        choose();
        break;
      default:
        break;
    }
  }

  function retry() {
    void runSearch(query);
  }

  // Mantiene el <dialog> nativo sincronizado con la prop `open`.
  $effect(() => {
    const el = dialogEl;
    if (!el) return;

    if (open && !el.open) {
      reset();
      el.showModal();
      queueMicrotask(() => inputEl?.focus());
    } else if (!open && el.open) {
      el.close();
    }
  });

  // La opción activa debe quedar a la vista al navegar con el teclado.
  $effect(() => {
    if (activeIndex < 0) return;
    document.getElementById(optionId(activeIndex))?.scrollIntoView({ block: 'nearest' });
  });

  onDestroy(() => {
    scheduleSearch.cancel();
    controller?.abort();
  });
</script>

<dialog
  bind:this={dialogEl}
  class="app-dialog"
  aria-labelledby="student-picker-title"
  onclose={close}
  oncancel={close}
>
  <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
    <div>
      <h2 id="student-picker-title" class="text-lg font-semibold text-ink">
        Buscar estudiante
      </h2>
      <p class="mt-0.5 text-[0.8125rem] text-ink-muted">
        Por nombre completo o número IND.
      </p>
    </div>
    <button
      type="button"
      class="btn btn--ghost btn--icon shrink-0"
      onclick={close}
      aria-label="Cerrar búsqueda"
    >
      <Icon name="close" size={18} />
    </button>
  </header>

  <div class="border-b border-line px-5 py-4">
    <div class="field__control">
      <span class="field__icon">
        <Icon name="search" size={18} />
      </span>

      <input
        bind:this={inputEl}
        bind:value={query}
        class="field__input field__input--with-icon"
        type="text"
        role="combobox"
        placeholder="Nombre o IND del estudiante…"
        autocomplete="off"
        autocapitalize="off"
        spellcheck="false"
        aria-label="Buscar estudiante"
        aria-autocomplete="list"
        aria-controls={listId}
        aria-expanded={showList}
        aria-activedescendant={activeIndex >= 0 ? optionId(activeIndex) : undefined}
        oninput={(event) => scheduleSearch(event.currentTarget.value)}
        onkeydown={onKeydown}
      />

      {#if status === 'loading'}
        <span class="field__action pointer-events-none" aria-hidden="true">
          <span
            class="spinner block h-4 w-4 rounded-full border-2 border-line-strong border-t-accent"
          ></span>
        </span>
      {/if}
    </div>
  </div>

  <div class="scroll-area min-h-[13rem] flex-1 px-2 py-2">
    <!-- Estado anunciado a lectores de pantalla sin recargar la vista. -->
    <p class="sr-only" aria-live="polite">
      {#if status === 'loading'}
        Buscando…
      {:else if status === 'done'}
        {results.length}
        {results.length === 1 ? 'resultado' : 'resultados'}
      {/if}
    </p>

    {#if status === 'error'}
      <div class="notice notice--danger m-3">
        <Icon name="alert" size={18} class="mt-0.5 shrink-0" />
        <div class="flex-1">
          <p class="font-medium">{errorMessage}</p>
          <button type="button" class="btn btn--outline btn--sm mt-3" onclick={retry}>
            <Icon name="refresh" size={15} />
            Reintentar
          </button>
        </div>
      </div>
    {:else if status === 'loading'}
      <ul class="flex flex-col gap-1 p-1" aria-hidden="true">
        {#each Array(4) as _, i (i)}
          <li class="flex items-center gap-3 p-3">
            <span class="skeleton h-9 w-9 shrink-0 rounded-md"></span>
            <span class="flex-1 space-y-2">
              <span class="skeleton block h-3.5 w-2/3"></span>
              <span class="skeleton block h-3 w-1/3"></span>
            </span>
          </li>
        {/each}
      </ul>
    {:else if showList}
      <ul id={listId} role="listbox" aria-label="Resultados de la búsqueda" class="flex flex-col gap-1">
        {#each results as record, index (record.ind)}
          <!--
            Patrón combobox de ARIA: las opciones no reciben foco (la navegación
            por teclado vive en el input vía aria-activedescendant). El clic es
            solo un atajo de puntero, por eso no lleva manejador de teclado.
          -->
          <!-- svelte-ignore a11y_click_events_have_key_events -->
          <li
            id={optionId(index)}
            role="option"
            aria-selected={index === activeIndex}
            class="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 transition-colors {index ===
            activeIndex
              ? 'bg-accent-soft'
              : 'hover:bg-surface-2'}"
            onmousedown={(event) => event.preventDefault()}
            onmouseenter={() => (activeIndex = index)}
            onclick={() => choose(index)}
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-xs font-bold text-ink-muted"
            >
              {initials(displayName(record.nombres2))}
            </span>

            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-medium text-ink">
                {displayName(record.nombres2) || 'Sin nombre registrado'}
              </span>
              <span class="mt-0.5 block text-xs text-ink-subtle">
                IND <span class="tnum">{record.ind}</span>
                {#if record.identificacion}
                  · D.I. <span class="tnum">{record.identificacion}</span>
                {/if}
              </span>
            </span>

            {#if index === activeIndex}
              <Icon name="chevron-right" size={16} class="shrink-0 text-accent" />
            {/if}
          </li>
        {/each}
      </ul>
    {:else if status === 'done'}
      <div class="empty-state">
        <Icon name="inbox" size={28} class="text-ink-subtle" />
        <div>
          <p class="font-medium text-ink">Sin coincidencias</p>
          <p class="mt-1 text-sm">
            No hay estudiantes que coincidan con «{query.trim()}».
          </p>
        </div>
      </div>
    {:else}
      <div class="empty-state">
        <Icon name="user" size={28} class="text-ink-subtle" />
        <div>
          <p class="font-medium text-ink">Escribe al menos {MIN_CHARS} caracteres</p>
          <p class="mt-1 text-sm">
            Puedes buscar por apellidos y nombres, o por el número IND del estudiante.
          </p>
        </div>
      </div>
    {/if}
  </div>

  <footer
    class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-5 py-3 text-xs text-ink-subtle"
  >
    <span class="flex items-center gap-3">
      <span class="flex items-center gap-1">
        <span class="kbd">↑</span><span class="kbd">↓</span> navegar
      </span>
      <span class="flex items-center gap-1"><span class="kbd">Enter</span> elegir</span>
      <span class="flex items-center gap-1"><span class="kbd">Esc</span> cerrar</span>
    </span>
    {#if status === 'done' && results.length > 0}
      <span class="tnum">{results.length} de 20 máximo</span>
    {/if}
  </footer>
</dialog>
