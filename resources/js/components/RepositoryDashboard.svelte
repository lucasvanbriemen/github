<script>
  import { onMount } from 'svelte';

  const { params = {} } = $props();
  let organizations = [];
  let name = params.name || '';
  let repository = params.repository || '';

  onMount(async () => {
    const res = await fetch('/api/organizations');
    organizations = await res.json();
  });

</script>

<div class="repo-header">
  <span class="org">{name}</span>
  <span class="sep">/</span>
  <span class="repo">{repository}</span>
  <!-- Extend this area to render repo-specific dashboard data -->
  
</div>

<style>
  .repo-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    font-size: 1.25rem;
    font-weight: 600;
  }

  .org, .repo { color: var(--text-color); }
  .sep { opacity: 0.7; }
</style>
