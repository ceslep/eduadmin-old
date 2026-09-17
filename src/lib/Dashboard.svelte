<script lang="ts">
  import { onMount } from 'svelte';
  import { getInforme, getYears, type InformeData, type MatriculaRecord, type YearOption } from './api';
  import { toast } from './toast.svelte';
  import { displayName, formatInt, initials, readSession, writeSession } from './utils';
  import CertificateForm from './components/CertificateForm.svelte';
  import Icon from './components/Icon.svelte';
  import InformeTable from './components/InformeTable.svelte';
  import StudentPicker from './components/StudentPicker.svelte';
  import YearPicker from './components/YearPicker.svelte';

  const YEAR_KEY = 'eduadmin.workspace.year';
  const STUDENT_KEY = 'eduadmin.workspace.student';

  /* --- Estado ------------------------------------------------------------- */

  let years = $state<YearOption[]>([]);
  let yearsStatus = $state<'loading' | 'ready' | 'error'>('loading');
  let yearsError = $state('');

  let selectedYear = $state<string | null>(readSession<string | null>(YEAR_KEY, null));
  let selectedStudent = $state<MatriculaRecord | null>(
    readSession<MatriculaRecord | null>(STUDENT_KEY, null)
  );
  let pickerOpen = $state(false);

  let informe = $state<InformeData | null>(null);
  let informeStatus = $state<'idle' | 'loading' | 'ready' | 'error'>('idle');
  let informeError = $state('');

  /**
   * Descarta respuestas que llegan tarde: si el usuario cambia de estudiante
   * mientras una petición está en vuelo, solo la última puede escribir estado.
   */
  let requestId = 0;

  const studentName = $derived(selectedStudent ? displayName(selectedStudent.nombres2) : '');
  const selectedYearOption = $derived(years.find((year) => year.anio === selectedYear) ?? null);

  /* --- Carga inicial ------------------------------------------------------ */

  onMount(async () => {
    try {
      const data = await getYears();
      years = data;
      yearsStatus = 'ready';

      // Si no hay año guardado (o ya no existe), abrir el más reciente.
      if (!selectedYear || !data.some((year) => year.anio === selectedYear)) {
        selectedYear = data[0]?.anio ?? null;
      }
    } catch (caught) {
      yearsStatus = 'error';
      yearsError = caught instanceof Error ? caught.message : 'No se pudieron cargar los periodos.';
    }
  });

  /* --- Informe ------------------------------------------------------------ */

  async function loadInforme(year: string, estudiante: string) {
    const current = ++requestId;
    informeStatus = 'loading';
    informeError = '';

    try {
      const data = await getInforme(estudiante, year);
      if (current !== requestId) return;
      informe = data;
      informeStatus = 'ready';
    } catch (caught) {
      if (current !== requestId) return;
      informe = null;
      informeStatus = 'error';
      informeError = caught instanceof Error ? caught.message : 'No se pudo cargar el informe.';
    }
  }

  // El informe se recarga solo cuando cambia el periodo o el estudiante.
  $effect(() => {
    const year = selectedYear;
    const student = selectedStudent;

    if (!year || !student) {
      requestId += 1;
      informe = null;
      informeStatus = 'idle';
      return;
    }

    void loadInforme(year, String(student.ind));
  });

  // La selección sobrevive a una recarga accidental de la pestaña.
  $effect(() => {
    writeSession(YEAR_KEY, selectedYear);
    writeSession(STUDENT_KEY, selectedStudent);
  });

  /* --- Acciones ----------------------------------------------------------- */

  function chooseYear(anio: string) {
    if (selectedYear === anio) return;
    selectedYear = anio;
  }

  function chooseStudent(record: MatriculaRecord) {
    selectedStudent = record;
    toast.success('Estudiante seleccionado', displayName(record.nombres2) || `IND ${record.ind}`);
  }

  function clearStudent() {
    selectedStudent = null;
  }

  function openPicker() {
    if (!selectedYear) {
      toast.info('Elige un periodo', 'Primero selecciona el año lectivo que quieres consultar.');
      return;
    }
    pickerOpen = true;
  }

  function retryInforme() {
    if (selectedYear && selectedStudent) {
      void loadInforme(selectedYear, String(selectedStudent.ind));
    }
  }

  function retryYears() {
    yearsStatus = 'loading';
    yearsError = '';
    void getYears()
      .then((data) => {
        years = data;
        yearsStatus = 'ready';
      })
      .catch((caught) => {
        yearsStatus = 'error';
        yearsError = caught instanceof Error ? caught.message : 'No se pudieron cargar los periodos.';
      });
  }

  /** Atajo de teclado: ⌘/Ctrl + K o “/” abren la búsqueda de estudiante. */
  function onGlobalKeydown(event: KeyboardEvent) {
    const target = event.target as HTMLElement | null;
    const typing =
      target !== null &&
      (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

    const isCommandK = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';
    const isSlash = event.key === '/' && !typing && !event.metaKey && !event.ctrlKey;

    if (!isCommandK && !isSlash) return;
    if (pickerOpen) return;

    event.preventDefault();
    openPicker();
  }
</script>

<svelte:window onkeydown={onGlobalKeydown} />

<StudentPicker
  open={pickerOpen}
  onclose={() => (pickerOpen = false)}
  onselect={chooseStudent}
/>

<header class="mb-6">
  <h1>Certificados académicos</h1>
  <p class="mt-1.5 max-w-[68ch] text-sm text-ink-muted">
    Elige el año lectivo, busca al estudiante y emite el certificado con el formato oficial de la
    institución.
  </p>
</header>

<!-- Consulta: a todo el ancho, para que el selector de años respire -->
<section class="card mb-6" aria-labelledby="consulta-title">
  <h2 id="consulta-title" class="sr-only">Consulta de certificados</h2>

  <!-- 1. Periodo -->
  <div class="border-b border-line px-5 py-5 sm:px-6">
    <div class="mb-3.5 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
      <h3 class="flex items-baseline gap-2 text-xs font-semibold tracking-[0.09em] text-ink-muted uppercase">
        <span class="tnum text-accent">1</span>
        Periodo escolar
      </h3>

      {#if selectedYearOption}
        <p class="text-xs text-ink-subtle">
          <span class="tnum font-semibold text-ink">{formatInt(selectedYearOption.total)}</span>
          informes cargados en {selectedYearOption.anio}
        </p>
      {/if}
    </div>

    {#if yearsStatus === 'error'}
      <div class="notice notice--danger">
        <Icon name="alert" size={18} class="mt-0.5 shrink-0" />
        <div class="flex-1">
          <p class="font-medium">No se pudieron cargar los periodos</p>
          <p class="mt-1 text-[0.8125rem]">{yearsError}</p>
          <button type="button" class="btn btn--outline btn--sm mt-3" onclick={retryYears}>
            <Icon name="refresh" size={15} />
            Reintentar
          </button>
        </div>
      </div>
    {:else}
      <YearPicker
        years={years}
        value={selectedYear}
        loading={yearsStatus === 'loading'}
        onselect={chooseYear}
      />
    {/if}
  </div>

  <!-- 2. Estudiante -->
  <div class="px-5 py-5 sm:px-6">
    <div class="mb-3.5 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
      <h3 class="flex items-baseline gap-2 text-xs font-semibold tracking-[0.09em] text-ink-muted uppercase">
        <span class="tnum text-accent">2</span>
        Estudiante
      </h3>

      {#if selectedStudent}
        <button
          type="button"
          class="text-xs font-medium text-accent transition-colors hover:text-accent-hover"
          onclick={clearStudent}
        >
          Quitar selección
        </button>
      {/if}
    </div>

    {#if selectedStudent}
      <div class="flex flex-wrap items-center gap-4 rounded-md border border-line bg-surface-2 p-4">
        <span
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-accent-soft text-sm font-bold text-accent"
          aria-hidden="true"
        >
          {initials(studentName)}
        </span>

        <div class="min-w-[12rem] flex-1">
          <p class="truncate font-semibold text-ink">
            {studentName || 'Sin nombre registrado'}
          </p>
          <p class="mt-0.5 text-xs text-ink-subtle">
            IND <span class="tnum">{selectedStudent.ind}</span>
            {#if selectedStudent.identificacion}
              · D.I. <span class="tnum">{selectedStudent.identificacion}</span>
            {/if}
          </p>
        </div>

        <button type="button" class="btn btn--outline btn--sm" onclick={openPicker}>
          <Icon name="refresh" size={15} />
          Cambiar estudiante
        </button>
      </div>
    {:else}
      <button
        type="button"
        class="flex w-full items-center gap-4 rounded-md border border-dashed border-line-strong px-4 py-3.5 text-left transition-colors hover:border-accent hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-55"
        onclick={openPicker}
        disabled={!selectedYear}
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-ink-subtle"
          aria-hidden="true"
        >
          <Icon name="search" size={18} />
        </span>

        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-ink">
            {selectedYear ? 'Buscar estudiante' : 'Primero elige el año lectivo'}
          </span>
          <span class="block text-xs text-ink-subtle">
            {selectedYear
              ? 'Por apellidos y nombres, o por número IND'
              : 'El buscador se activa con el periodo'}
          </span>
        </span>

        <span class="hidden shrink-0 items-center gap-1 sm:flex">
          <span class="kbd">⌘</span><span class="kbd">K</span>
        </span>
      </button>
    {/if}
  </div>
</section>

<!-- Resultado: la tabla toma el ancho disponible y el certificado va al costado -->
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem] xl:items-start">
  <div class="min-w-0">
    {#if informeStatus === 'loading'}
      <div class="card p-6" aria-busy="true" aria-live="polite">
        <span class="sr-only">Cargando informe…</span>
        <div class="skeleton h-4 w-32"></div>
        <div class="skeleton mt-3 h-7 w-64"></div>
        <div class="skeleton mt-2 h-4 w-48"></div>
        <div class="mt-7 flex flex-col gap-4">
          {#each Array(7) as _, i (i)}
            <div class="flex items-center gap-4">
              <div class="skeleton h-4 w-8"></div>
              <div class="skeleton h-4 flex-1"></div>
              <div class="skeleton h-5 w-28 rounded-full"></div>
            </div>
          {/each}
        </div>
      </div>
    {:else if informeStatus === 'error'}
      <div class="card p-6">
        <div class="notice notice--danger">
          <Icon name="alert" size={18} class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-medium">No se pudo cargar el informe</p>
            <p class="mt-1 text-[0.8125rem]">{informeError}</p>
            <button type="button" class="btn btn--outline btn--sm mt-3" onclick={retryInforme}>
              <Icon name="refresh" size={15} />
              Reintentar
            </button>
          </div>
        </div>
      </div>
    {:else if informe && selectedStudent}
      <InformeTable {informe} />
    {:else}
      <div class="card">
        <div class="empty-state">
          <span
            class="flex h-12 w-12 items-center justify-center rounded-lg bg-accent-soft text-accent"
            aria-hidden="true"
          >
            <Icon name="file" size={24} />
          </span>
          <div>
            <p class="font-semibold text-ink">
              {selectedYear ? 'Falta elegir al estudiante' : 'Empieza por el año lectivo'}
            </p>
            <p class="mt-1 max-w-[48ch] text-sm">
              {selectedYear
                ? `Selecciona un estudiante para ver su informe académico del año ${selectedYear}.`
                : 'Los informes están organizados por año. Elige uno para consultar los estudiantes.'}
            </p>
          </div>
        </div>
      </div>
    {/if}
  </div>

  <div class="flex flex-col gap-6">
    {#if informeStatus === 'ready' && informe && selectedStudent}
      <CertificateForm
        estudiante={String(selectedStudent.ind)}
        anio={informe.anio}
        nombre={studentName}
      />
    {:else}
      <section class="card p-5" aria-label="Certificado">
        <div class="flex items-start gap-3 opacity-60">
          <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-ink-subtle"
            aria-hidden="true"
          >
            <Icon name="award" size={20} />
          </span>
          <div>
            <p class="font-semibold text-ink">Emitir certificado</p>
            <p class="mt-0.5 text-sm text-ink-muted">
              Se habilita cuando el informe esté cargado.
            </p>
          </div>
        </div>
      </section>
    {/if}
  </div>
</div>
