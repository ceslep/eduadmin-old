<script lang="ts">
  import { user } from './lib/stores';
  import Particles from './lib/Particles.svelte';
  import Login from './lib/Login.svelte';
  import Dashboard from './lib/Dashboard.svelte';
</script>

<div class="app-container min-h-screen">
  <!-- Background -->
  <div class="fixed inset-0 bg-gradient-to-br from-gray-950 via-purple-950/40 to-gray-950 z-0"></div>
  <div class="fixed inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-900/20 via-transparent to-transparent z-0"></div>

  <!-- Particles -->
  <Particles />

  <!-- Content -->
  {#if $user}
    <Dashboard />
  {:else}
    <Login />
  {/if}
</div>
