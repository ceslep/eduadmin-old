<script lang="ts">
  import { generateInformeFile, saveFile } from '../api';
  import { toast } from '../toast.svelte';
  import Icon from './Icon.svelte';

  interface Props {
    /** Identificador del estudiante (campo `ind` de la matrícula). */
    estudiante: string;
    anio: string;
    /** Nombre tal como debe quedar impreso en el certificado. */
    nombre: string;
  }

  let { estudiante, anio, nombre }: Props = $props();

  let documento = $state('');
  let touched = $state(false);
  let generating = $state(false);

  const value = $derived(documento.trim());

  const error = $derived(
    value === ''
      ? 'Ingresa el número de documento para emitir el certificado.'
      : !/^[0-9A-Za-z.\-]{4,20}$/.test(value)
        ? 'Usa entre 4 y 20 caracteres (números, letras, puntos o guiones).'
        : ''
  );

  const showError = $derived(touched && error !== '');

  async function generate() {
    touched = true;
    if (error !== '' || generating) return;

    generating = true;
    try {
      const file = await generateInformeFile(estudiante, anio, value, nombre);
      saveFile(file);
      toast.success('Certificado generado', `Se descargó «${file.filename}».`);
    } catch (caught) {
      toast.error(
        'No se pudo generar el certificado',
        caught instanceof Error ? caught.message : 'Intenta de nuevo en unos segundos.'
      );
    } finally {
      generating = false;
    }
  }
</script>

<section class="card p-5 sm:p-6" aria-labelledby="certificado-title">
  <div class="flex items-start gap-3">
    <span
      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent-soft text-accent"
      aria-hidden="true"
    >
      <Icon name="award" size={20} />
    </span>
    <div class="min-w-0">
      <h2 id="certificado-title" class="text-lg font-semibold text-ink">
        Emitir certificado
      </h2>
      <p class="mt-0.5 text-sm text-ink-muted">
        Se genera el archivo Word con el formato institucional a partir de este informe.
      </p>
    </div>
  </div>

  <form class="mt-5" onsubmit={(event) => { event.preventDefault(); generate(); }} novalidate>
    <div class="field">
      <label class="field__label" for="documento">Documento de identidad</label>
      <div class="field__control">
        <span class="field__icon">
          <Icon name="hash" size={18} />
        </span>
        <input
          id="documento"
          class="field__input field__input--with-icon tnum"
          type="text"
          inputmode="text"
          autocomplete="off"
          placeholder="Ej. 1234567890"
          bind:value={documento}
          onblur={() => (touched = true)}
          disabled={generating}
          aria-invalid={showError}
          aria-describedby={showError ? 'documento-error' : 'documento-hint'}
        />
      </div>

      {#if showError}
        <p class="field__error" id="documento-error">
          <Icon name="alert" size={14} class="shrink-0" />
          {error}
        </p>
      {:else}
        <p class="field__hint" id="documento-hint">
          Aparecerá como «D.I.» junto al nombre de <strong>{nombre}</strong>.
        </p>
      {/if}
    </div>

    <button type="submit" class="btn btn--accent btn--lg mt-5 w-full" disabled={generating}>
      {#if generating}
        <span
          class="spinner h-4 w-4 rounded-full border-2 border-current border-t-transparent"
          aria-hidden="true"
        ></span>
        Generando certificado…
      {:else}
        <Icon name="download" size={18} />
        Descargar certificado Word
      {/if}
    </button>
  </form>

  <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-ink-subtle">
    <Icon name="info" size={14} class="mt-0.5 shrink-0" />
    El archivo se arma con la plantilla oficial del colegio. Verifica el documento antes de
    imprimirlo o radicarlo.
  </p>
</section>
