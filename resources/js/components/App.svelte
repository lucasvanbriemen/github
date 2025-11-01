<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import Organization from './Organization.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import ItemOverview from './ItemOverview.svelte';
  import Item from './Item.svelte';
  import theme from '../theme.js';

  const routes = {
    '/': Dashboard,
    '/:organization': Organization,
    '/:organization/:repository': RepositoryDashboard,
    '/:organization/:repository/issues': ItemOverview,
    '/:organization/:repository/issues/:number': Item,
    '/:organization/:repository/prs': ItemOverview,
    '/:organization/:repository/prs/:number': Item,
  };

  onMount(async () => {
    theme.applyTheme();
  });
</script>

<Header />
<Router {routes} />

<style lang="scss">
  :global(body) {
    margin: 0;
    font-family: Roboto, sans-serif;
    background-color: var(--background-color);
    min-height: 100vh;
    min-width: 100vw;
  }

  /* Ensure the SPA root fills the viewport */
  :global(#app) {
    height: 100vh;
    width: 100vw;
    display: flex;
    flex-direction: column;
  }

  :global(*) {
    font-family: var(--font-family);
    color: var(--text-color);
    transition: all 0.3s ease;
  }
</style>
