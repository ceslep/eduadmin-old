<script lang="ts">
  import { ICON_PATHS, type IconName } from '../icons';

  interface Props {
    name: IconName;
    /** Lado del cuadrado en px. */
    size?: number;
    /** Grosor del trazo; sube un poco en iconos pequeños. */
    stroke?: number;
    class?: string;
  }

  let { name, size = 20, stroke = 1.6, class: className = '' }: Props = $props();

  const paths = $derived(ICON_PATHS[name] ?? []);
</script>

<svg
  class={className}
  width={size}
  height={size}
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width={stroke}
  stroke-linecap="round"
  stroke-linejoin="round"
  aria-hidden="true"
  focusable="false"
>
  {#each paths as d}
    <path {d} />
  {/each}
</svg>
