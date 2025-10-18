<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import Organization from './Organization.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import theme from '../theme.js';

  const routes = {
    '/': Dashboard,
    '/:name': Organization,
    '/:name/:repository': RepositoryDashboard,
  };

  onMount(async () => {
    await theme.applyTheme();
  });
</script>

<Header />
<Router {routes} />


<style lang="scss">
  :global(body) {
    margin: 0;
    font-family: Roboto, sans-serif;
    background-color: var(--background-color);
  }

  :global(*) {
    font-family: var(--font-family);
    color: var(--text-color);
    transition: all 0.3s ease;
  }
</style>