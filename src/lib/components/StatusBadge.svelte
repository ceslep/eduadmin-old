<script lang="ts">
  import { valoracionMeta } from '../utils';

  interface Props {
    valoracion: string;
    /** `lg` para la vista de tarjetas en móvil. */
    size?: 'sm' | 'md';
  }

  let { valoracion, size = 'sm' }: Props = $props();

  const meta = $derived(valoracionMeta(valoracion));
</script>

<span
  class="badge badge--{meta.tone} {size === 'md' ? 'px-3 py-1 text-xs' : ''}"
  title={valoracion?.trim() || 'Sin dato'}
>
  {meta.label}
</span>
