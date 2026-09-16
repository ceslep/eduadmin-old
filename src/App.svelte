<script lang="ts">
  import { user } from './lib/stores';
  import Particles from './lib/Particles.svelte';
  import Login from './lib/Login.svelte';
  import Dashboard from './lib/Dashboard.svelte';
</script>

<div class="app-container min-h-screen bg-[var(--surface-base)]">
  <!-- Background -->
  <div class="fixed inset-0 bg-gradient-to-br from-gray-100 via-purple-50 to-gray-100 dark:from-gray-950 dark:via-purple-950/40 dark:to-gray-950 z-0"></div>
  <div class="fixed inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-100/30 via-transparent to-transparent dark:from-purple-900/20 dark:via-transparent dark:to-transparent z-0"></div>

  <!-- Particles -->
  <Particles />

  <!-- Content -->
  {#if $user}
    <Dashboard />
  {:else}
    <Login />
  {/if}
</div>
