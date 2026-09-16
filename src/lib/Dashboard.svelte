<script lang="ts">
  import { user, theme } from './stores';
  import { api, getInforme, downloadInforme, type InformeData } from './api';

  const roleLabels: Record<string, string> = {
    admin: 'Administrador',
    profesor: 'Profesor',
    alumno: 'Alumno',
  };

  const roleColors: Record<string, string> = {
    admin: 'from-purple-500 to-indigo-600',
    profesor: 'from-blue-500 to-cyan-600',
    alumno: 'from-green-500 to-emerald-600',
  };

  const years = [2004, 2005, 2006, 2007, 2008, 2009];
  let selectedYear: number | null = $state(null);

  let showModal = $state(false);
  let searchQuery = $state('');
  let searchResults: any[] = $state([]);
  let selectedRecord: any | null = $state(null);
  let isSearching = $state(false);

  let informeData: InformeData | null = $state(null);
  let isLoadingInforme = $state(false);
  let informeError = $state('');

  let documentoInput = $state('');
  let isDownloading = $state(false);

  function handleLogout() {
    user.logout();
  }

  async function loadInforme() {
    if (!selectedYear || !selectedRecord) {
      informeData = null;
      return;
    }
    isLoadingInforme = true;
    informeError = '';
    try {
      informeData = await getInforme(String(selectedRecord.ind), String(selectedYear));
    } catch (error: any) {
      informeData = null;
      informeError = error.message || 'No se encontró informe';
    } finally {
      isLoadingInforme = false;
    }
  }

  function selectYear(year: number) {
    selectedYear = year;
    loadInforme();
  }

  async function searchMatricula() {
    if (searchQuery.length < 1) {
      searchResults = [];
      return;
    }
    isSearching = true;
    try {
      const response = await api('/matricula/search', {
        method: 'POST',
        body: JSON.stringify({ query: searchQuery }),
      });
      searchResults = response.data;
    } catch (error) {
      searchResults = [];
    } finally {
      isSearching = false;
    }
  }

  function selectRecord(record: any) {
    selectedRecord = record;
    showModal = false;
    searchQuery = '';
    searchResults = [];
    loadInforme();
  }

  function clearSelection() {
    selectedRecord = null;
    informeData = null;
  }

  function openModal() {
    showModal = true;
  }

  function closeModal() {
    showModal = false;
    searchQuery = '';
    searchResults = [];
  }

  function getColorClass(valoracion: string): string {
    const v = valoracion.toUpperCase();
    if (v.includes('SOBRESALIENTE')) return 'text-emerald-500 dark:text-emerald-400';
    if (v.includes('EXCELENTE')) return 'text-green-500 dark:text-green-400';
    if (v.includes('ACEPTABLE')) return 'text-yellow-500 dark:text-yellow-400';
    if (v.includes('INSUFICIENTE')) return 'text-red-500 dark:text-red-400';
    return 'text-gray-500 dark:text-white/60';
  }

  async function handleDownload() {
    if (!selectedYear || !selectedRecord || !documentoInput.trim()) return;
    isDownloading = true;
    try {
      await downloadInforme(String(selectedRecord.ind), String(selectedYear), documentoInput.trim(), selectedRecord.nombres2 || '');
    } catch (error: any) {
      alert(error.message || 'Error al descargar el informe');
    } finally {
      isDownloading = false;
    }
  }
</script>

<div class="relative z-10 min-h-screen">
  <!-- Navbar -->
  <nav class="glass-nav border-b border-black/5 dark:border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/20">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
            </svg>
          </div>
          <span class="text-gray-900 dark:text-white font-bold text-lg tracking-tight">EduAdmin</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-black/5 dark:bg-white/5 border border-black/5 dark:border-white/5">
            <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
            <span class="text-gray-500 dark:text-white/60 text-xs">En linea</span>
          </div>
          <button
            onclick={() => theme.toggle()}
            class="theme-toggle p-2 rounded-lg bg-black/5 dark:bg-white/5 border border-black/5 dark:border-white/5 hover:bg-black/10 dark:hover:bg-white/10 transition-all duration-200"
            title="Cambiar tema"
          >
            {#if $theme === 'dark'}
              <svg class="w-4 h-4 text-gray-600 dark:text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
              </svg>
            {:else}
              <svg class="w-4 h-4 text-gray-600 dark:text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
              </svg>
            {/if}
          </button>
          <button
            onclick={handleLogout}
            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-black/5 dark:bg-white/5 border border-black/5 dark:border-white/10 text-gray-600 dark:text-white/70 hover:text-gray-900 dark:hover:text-white hover:bg-black/10 dark:hover:bg-white/10 transition-all duration-200 text-sm"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
            </svg>
            Cerrar sesion
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Welcome Card -->
    <div class="glass-card rounded-3xl p-8 md:p-12 border border-black/10 dark:border-white/10 mb-8">
      <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
        <!-- Avatar -->
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br {roleColors[$user?.role || 'admin']} flex items-center justify-center shadow-xl flex-shrink-0">
          <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" />
          </svg>
        </div>

        <div class="flex-1">
          <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
            Bienvenido, <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-500 to-indigo-500 dark:from-purple-400 dark:to-indigo-400">{$user?.username || 'Usuario'}</span>
          </h1>
          <div class="flex flex-wrap items-center gap-3 mt-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gradient-to-r {roleColors[$user?.role || 'admin']} text-white text-xs font-medium shadow-lg">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              {roleLabels[$user?.role || 'admin']}
            </span>
            <span class="text-gray-300 dark:text-white/30 text-sm">|</span>
            <span class="text-gray-500 dark:text-white/40 text-sm">{$user?.email || ''}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificate Generator -->
    <div class="glass-card rounded-3xl p-8 border border-black/10 dark:border-white/10">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/20">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
          </svg>
        </div>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generar Certificados</h2>
          <p class="text-gray-500 dark:text-white/40 text-sm">Selecciona el año académico</p>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {#each years as year}
          <button
            onclick={() => selectYear(year)}
            class="group relative p-5 rounded-2xl border-2 transition-all duration-300 {selectedYear === year
              ? 'border-purple-500 bg-purple-500/10 shadow-lg shadow-purple-500/10'
              : 'border-black/5 dark:border-white/5 bg-black/5 dark:bg-white/5 hover:border-purple-500/30 hover:bg-purple-50 dark:hover:bg-white/10'}"
          >
            {#if selectedYear === year}
              <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-purple-500 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
              </div>
            {/if}
            <p class="text-2xl font-bold {selectedYear === year ? 'text-purple-500 dark:text-purple-400' : 'text-gray-900 dark:text-white group-hover:text-purple-500 dark:group-hover:text-purple-400'} transition-colors">{year}</p>
          </button>
        {/each}
      </div>

      <!-- Selected Student -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <span class="text-gray-500 dark:text-white/60 text-sm font-medium">Estudiante seleccionado</span>
          {#if selectedRecord}
            <button onclick={clearSelection} class="text-purple-500 dark:text-purple-400 text-xs hover:text-purple-600 dark:hover:text-purple-300 transition-colors">
              Cambiar
            </button>
          {/if}
        </div>
        {#if selectedRecord}
          <div class="p-4 rounded-2xl border-2 border-purple-500 bg-purple-500/10">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" />
                </svg>
              </div>
              <div>
                <p class="text-gray-900 dark:text-white font-bold">{selectedRecord.nombres2}</p>
                <p class="text-gray-500 dark:text-white/40 text-sm">IND: {selectedRecord.ind}</p>
              </div>
            </div>
          </div>
        {:else}
          <button
            onclick={openModal}
            class="w-full p-4 rounded-2xl border-2 border-dashed border-black/10 dark:border-white/10 hover:border-purple-500/30 transition-all duration-300 text-left"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-black/5 dark:bg-white/5 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400 dark:text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
              </div>
              <div>
                <p class="text-gray-900 dark:text-white font-medium">Buscar estudiante</p>
                <p class="text-gray-400 dark:text-white/30 text-sm">Buscar por nombres2 o IND</p>
              </div>
            </div>
          </button>
        {/if}
      </div>

      <button
        onclick={loadInforme}
        disabled={!selectedYear || !selectedRecord}
        class="w-full py-4 rounded-2xl font-bold text-white text-lg transition-all duration-300 {selectedYear && selectedRecord
          ? 'bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 shadow-lg shadow-purple-500/25 cursor-pointer'
          : 'bg-black/5 dark:bg-white/5 text-gray-400 dark:text-white/20 cursor-not-allowed'}"
      >
        {#if selectedYear && selectedRecord}
          Ver Informe {selectedRecord.nombres2} - {selectedYear}
        {:else if selectedYear}
          Selecciona un estudiante
        {:else}
          Selecciona un año y estudiante
        {/if}
      </button>

      {#if informeData}
        <div class="mt-6 p-4 rounded-2xl border border-black/10 dark:border-white/10 bg-black/5 dark:bg-white/5">
          <label for="documento" class="block text-sm font-medium text-gray-700 dark:text-white/60 mb-2">
            Documento de Identidad (D.I.)
          </label>
          <div class="flex gap-3">
            <input
              id="documento"
              type="text"
              bind:value={documentoInput}
              placeholder="Ingrese el número de documento..."
              class="flex-1 px-4 py-3 rounded-xl bg-white dark:bg-white/10 border border-black/10 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-white/30 focus:outline-none focus:border-purple-500/50 transition-colors"
            />
            <button
              onclick={handleDownload}
              disabled={!documentoInput.trim() || isDownloading}
              class="px-6 py-3 rounded-xl font-bold text-white transition-all duration-300 {documentoInput.trim() && !isDownloading
                ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 shadow-lg shadow-green-500/25 cursor-pointer'
                : 'bg-black/5 dark:bg-white/5 text-gray-400 dark:text-white/20 cursor-not-allowed'}"
            >
              {#if isDownloading}
                <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              {:else}
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                  </svg>
                  Descargar Word
                </div>
              {/if}
            </button>
          </div>
        </div>
      {/if}
    </div>

    <!-- Informe Table -->
    {#if isLoadingInforme}
      <div class="glass-card rounded-3xl p-8 border border-black/10 dark:border-white/10 mt-8">
        <div class="flex items-center justify-center py-12">
          <div class="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
      </div>
    {:else if informeError}
      <div class="glass-card rounded-3xl p-8 border border-red-200 dark:border-red-500/20 mt-8">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center">
            <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
          </div>
          <div>
            <p class="text-gray-900 dark:text-white font-medium">No se encontró informe</p>
            <p class="text-gray-500 dark:text-white/40 text-sm">{informeError}</p>
          </div>
        </div>
      </div>
    {:else if informeData}
      <div class="glass-card rounded-3xl p-8 border border-black/10 dark:border-white/10 mt-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Informe Académico</h2>
            <p class="text-gray-500 dark:text-white/40 text-sm">{informeData.p2} - {informeData.p3} - Año {informeData.anio}</p>
          </div>
          <div class="text-right">
            <p class="text-gray-500 dark:text-white/60 text-sm">Estudiante</p>
            <p class="text-gray-900 dark:text-white font-bold">{selectedRecord?.nombres2}</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-black/10 dark:border-white/10">
                <th class="text-left py-3 px-4 text-gray-500 dark:text-white/40 text-sm font-medium">#</th>
                <th class="text-left py-3 px-4 text-gray-500 dark:text-white/40 text-sm font-medium">Área</th>
                <th class="text-left py-3 px-4 text-gray-500 dark:text-white/40 text-sm font-medium">Descripción del Logro</th>
                <th class="text-left py-3 px-4 text-gray-500 dark:text-white/40 text-sm font-medium">Valoración</th>
              </tr>
            </thead>
            <tbody>
              {#each informeData.parsed_informe as item}
                <tr class="border-b border-black/5 dark:border-white/5 hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
                  <td class="py-3 px-4 text-gray-500 dark:text-white/60 text-sm">{item.numero}</td>
                  <td class="py-3 px-4 text-gray-900 dark:text-white font-medium text-sm">{item.area}</td>
                  <td class="py-3 px-4 text-gray-700 dark:text-white/80 text-sm">{item.descripcion}</td>
                  <td class="py-3 px-4 text-sm font-bold {getColorClass(item.valoracion)}">{item.valoracion}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        {#if informeData.observaciones}
          <div class="mt-6 p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-black/5 dark:border-white/5">
            <p class="text-gray-500 dark:text-white/40 text-sm font-medium mb-2">Observaciones</p>
            <p class="text-gray-700 dark:text-white/80 text-sm">{informeData.observaciones}</p>
          </div>
        {/if}
      </div>
    {/if}
  </main>
</div>

<!-- Search Modal -->
{#if showModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick={closeModal}></div>
    <div class="relative w-full max-w-lg glass-card rounded-3xl p-6 border border-black/10 dark:border-white/10 shadow-2xl">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Buscar estudiante</h3>
        <button onclick={closeModal} class="w-8 h-8 rounded-lg bg-black/5 dark:bg-white/5 hover:bg-black/10 dark:hover:bg-white/10 flex items-center justify-center transition-colors">
          <svg class="w-5 h-5 text-gray-500 dark:text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="relative mb-4">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        <input
          type="text"
          bind:value={searchQuery}
          oninput={searchMatricula}
          placeholder="Buscar por nombres2 o IND..."
          class="w-full pl-12 pr-4 py-3 rounded-xl bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-white/30 focus:outline-none focus:border-purple-500/50 transition-colors"
        />
      </div>

      <div class="max-h-80 overflow-y-auto">
        {#if isSearching}
          <div class="flex items-center justify-center py-8">
            <div class="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
          </div>
        {:else if searchResults.length === 0}
          <div class="text-center py-8">
            <p class="text-gray-400 dark:text-white/30 text-sm">{searchQuery ? 'No se encontraron resultados' : 'Escribe para buscar'}</p>
          </div>
        {:else}
          <div class="space-y-2">
            {#each searchResults as record}
              <button
                onclick={() => selectRecord(record)}
                class="w-full p-4 rounded-xl bg-black/5 dark:bg-white/5 hover:bg-purple-50 dark:hover:bg-purple-500/10 border border-black/5 dark:border-white/5 hover:border-purple-500/30 transition-all duration-200 text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-gray-900 dark:text-white font-medium truncate">{record.nombres2}</p>
                    <p class="text-gray-500 dark:text-white/40 text-sm">IND: {record.ind}</p>
                  </div>
                </div>
              </button>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
