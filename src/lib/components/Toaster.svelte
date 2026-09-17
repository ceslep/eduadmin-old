<script lang="ts">
  import { toasts, type Toast } from '../toast.svelte';
  import Icon from './Icon.svelte';

  const toneIcon = { success: 'check', error: 'alert', info: 'info' } as const;
  const toneColor = {
    success: 'text-success',
    error: 'text-danger',
    info: 'text-info',
  } as const;

  const errors = $derived(toasts.items.filter((t) => t.tone === 'error'));
  const rest = $derived(toasts.items.filter((t) => t.tone !== 'error'));
</script>

{#snippet toastCard(item: Toast)}
  <div class="toast toast--{item.tone} animate-scale-in">
    <span class="mt-0.5 shrink-0 {toneColor[item.tone]}">
      <Icon name={toneIcon[item.tone]} size={18} />
    </span>
    <div class="min-w-0 flex-1">
      <p class="font-semibold break-words text-ink">{item.title}</p>
      {#if item.description}
        <p class="mt-0.5 text-[0.8125rem] leading-snug break-words text-ink-muted">
          {item.description}
        </p>
      {/if}
    </div>
    <button
      type="button"
      class="shrink-0 rounded-sm p-1 text-ink-subtle transition-colors hover:text-ink"
      onclick={() => toasts.dismiss(item.id)}
      aria-label="Cerrar notificación"
    >
      <Icon name="close" size={16} />
    </button>
  </div>
{/snippet}

<div class="toast-region">
  <!-- Los errores se anuncian de inmediato; el resto, cuando el lector termina. -->
  {#if errors.length > 0}
    <div class="flex flex-col gap-3" aria-live="assertive">
      {#each errors as item (item.id)}
        {@render toastCard(item)}
      {/each}
    </div>
  {/if}

  {#if rest.length > 0}
    <div class="flex flex-col gap-3" aria-live="polite">
      {#each rest as item (item.id)}
        {@render toastCard(item)}
      {/each}
    </div>
  {/if}
</div>
