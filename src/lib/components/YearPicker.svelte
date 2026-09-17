<script lang="ts">
  import type { YearOption } from '../api';
  import { cx, formatInt } from '../utils';
  import Icon from './Icon.svelte';

  interface Props {
    years: YearOption[];
    value: string | null;
    loading?: boolean;
    onselect: (anio: string) => void;
  }

  let { years, value, loading = false, onselect }: Props = $props();

  /** Años con muy pocos informes cargados: se muestran, pero señalados. */
  const THIN_THRESHOLD = 10;

  function countLabel(year: YearOption): string {
    const base = `${formatInt(year.total)} ${year.total === 1 ? 'informe' : 'informes'}`;
    return year.total < THIN_THRESHOLD ? `${base} · parcial` : base;
  }
</script>

<div role="group" aria-label="Año lectivo" aria-busy={loading}>
  {#if loading}
    <div class="year-grid">
      {#each Array(5) as _, i (i)}
        <div class="skeleton h-[4.4rem] rounded-md"></div>
      {/each}
    </div>
  {:else if years.length === 0}
    <p class="text-sm text-ink-muted">
      No hay años lectivos con informes cargados. Revisa la conexión con la base de datos.
    </p>
  {:else}
    <div class="year-grid">
      {#each years as year (year.anio)}
        {@const selected = value === year.anio}
        {@const thin = year.total < THIN_THRESHOLD}

        <button
          type="button"
          class="chip"
          aria-pressed={selected}
          onclick={() => onselect(year.anio)}
        >
          <span class="flex w-full items-center gap-1.5">
            <span class={cx('tnum text-lg leading-none font-semibold', selected ? 'text-accent' : 'text-ink')}>
              {year.anio}
            </span>

            {#if thin}
              <span
                class="h-1.5 w-1.5 shrink-0 rounded-full bg-warning"
                title="Pocos informes cargados para este año"
                aria-hidden="true"
              ></span>
            {/if}

            {#if selected}
              <span class="ml-auto text-accent" aria-hidden="true">
                <Icon name="check" size={16} stroke={2.4} />
              </span>
            {/if}
          </span>

          <span class="text-[0.6875rem] leading-tight text-ink-subtle">{countLabel(year)}</span>
        </button>
      {/each}
    </div>
  {/if}
</div>
