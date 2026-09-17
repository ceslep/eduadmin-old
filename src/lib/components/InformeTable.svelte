<script lang="ts">
  import type { InformeData } from '../api';
  import { cleanText, cx, summarizeValoraciones } from '../utils';
  import Icon from './Icon.svelte';
  import StatusBadge from './StatusBadge.svelte';

  interface Props {
    informe: InformeData;
  }

  let { informe }: Props = $props();

  /** En móvil y con descripciones largas, la densidad importa. */
  let compact = $state(true);

  const areas = $derived(informe.parsed_informe ?? []);
  const resumen = $derived(summarizeValoraciones(areas));

  const institucion = $derived(cleanText(informe.p2));
  const director = $derived(cleanText(informe.director_de_grupo));
  const observaciones = $derived(cleanText(informe.observaciones));
</script>

<section class="card card--ruled" aria-labelledby="informe-title">
  <!-- Encabezado del informe -->
  <header class="flex flex-wrap items-start justify-between gap-4 px-5 pt-6 pb-5 sm:px-6">
    <div class="min-w-0">
      <p class="text-xs font-semibold tracking-[0.14em] text-accent uppercase">
        Informe académico
      </p>
      <h2 id="informe-title" class="mt-1.5 text-xl font-semibold tracking-tight text-ink sm:text-2xl">
        {areas.length}
        {areas.length === 1 ? 'área evaluada' : 'áreas evaluadas'}
      </h2>
      <p class="mt-1 text-sm text-ink-muted">
        {#if institucion}{institucion} · {/if}Año lectivo
        <span class="tnum font-medium text-ink">{informe.anio}</span>
      </p>
    </div>

    <dl class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
      {#if informe.aula}
        <div>
          <dt class="text-xs tracking-wide text-ink-subtle uppercase">Aula</dt>
          <dd class="font-medium text-ink">{cleanText(informe.aula)}</dd>
        </div>
      {/if}
      {#if informe.informeno}
        <div>
          <dt class="text-xs tracking-wide text-ink-subtle uppercase">Informe</dt>
          <dd class="tnum font-medium text-ink">Nº {informe.informeno}</dd>
        </div>
      {/if}
      {#if director}
        <div class="max-w-[16rem]">
          <dt class="text-xs tracking-wide text-ink-subtle uppercase">Director de grupo</dt>
          <dd class="font-medium text-ink">{director}</dd>
        </div>
      {/if}
    </dl>
  </header>

  {#if areas.length === 0}
    <div class="border-t border-line">
      <div class="empty-state">
        <Icon name="file" size={28} class="text-ink-subtle" />
        <div>
          <p class="font-medium text-ink">Este informe no tiene áreas registradas</p>
          <p class="mt-1 text-sm">
            El registro existe para el año {informe.anio}, pero el detalle académico está vacío.
          </p>
        </div>
      </div>
    </div>
  {:else}
    <!-- Consolidado por escala de valoración -->
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-line px-5 py-3 sm:px-6">
      <span class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">Consolidado</span>
      {#each resumen as bucket (bucket.meta.label)}
        <span class="flex items-center gap-1.5 text-sm text-ink-muted">
          <StatusBadge valoracion={bucket.meta.label} />
          <span class="tnum font-medium text-ink">{bucket.total}</span>
        </span>
      {/each}

      <button
        type="button"
        class="btn btn--ghost btn--sm ml-auto"
        aria-pressed={compact}
        onclick={() => (compact = !compact)}
      >
        <Icon name={compact ? 'chevron-down' : 'chevron-up'} size={15} />
        {compact ? 'Ver descripciones completas' : 'Resumir descripciones'}
      </button>
    </div>

    <!-- Tabla (pantallas medianas en adelante) -->
    <div class="scroll-area hidden max-h-[34rem] border-t border-line md:block">
      <table class="data-table data-table--fixed">
        <caption class="sr-only">
          Áreas evaluadas, descripción del logro y valoración obtenida
        </caption>
        <thead>
          <tr>
            <th scope="col" class="w-12">#</th>
            <th scope="col" class="w-[20%]">Área</th>
            <th scope="col">Descripción del logro</th>
            <th scope="col" class="w-40">Valoración</th>
          </tr>
        </thead>
        <tbody>
          {#each areas as item, index (index)}
            <tr>
              <td class="tnum text-ink-subtle">{item.numero || index + 1}</td>
              <td class="font-medium break-words">{cleanText(item.area)}</td>
              <td class="text-ink-muted">
                <span
                  class={cx('block break-words', compact && 'line-clamp-2')}
                  title={compact ? cleanText(item.descripcion) : undefined}
                >
                  {cleanText(item.descripcion) || '—'}
                </span>
              </td>
              <td><StatusBadge valoracion={item.valoracion} /></td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

    <!-- Tarjetas (móvil) -->
    <ul class="flex flex-col divide-y divide-line border-t border-line md:hidden">
      {#each areas as item, index (index)}
        <li class="px-5 py-4">
          <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
              <span
                class="tnum mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-sm bg-surface-2 text-xs font-bold text-ink-muted"
              >
                {item.numero || index + 1}
              </span>
              <p class="font-medium text-ink">{cleanText(item.area)}</p>
            </div>
            <StatusBadge valoracion={item.valoracion} size="md" />
          </div>

          {#if cleanText(item.descripcion)}
            <p
              class={cx(
                'mt-2 pl-9 text-sm leading-relaxed text-ink-muted',
                compact && 'line-clamp-2'
              )}
            >
              {cleanText(item.descripcion)}
            </p>
          {/if}
        </li>
      {/each}
    </ul>
  {/if}

  {#if observaciones}
    <footer class="border-t border-line bg-surface-2 px-5 py-4 sm:px-6">
      <h3 class="flex items-center gap-2 text-xs font-semibold tracking-wide text-ink-muted uppercase">
        <Icon name="info" size={15} />
        Observaciones
      </h3>
      <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink">
        {observaciones}
      </p>
    </footer>
  {/if}
</section>
