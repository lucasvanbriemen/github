<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import Organization from './Organization.svelte';
  import theme from '../theme.js';
  import api from '../api.js';

  const routes = {
    '/': Dashboard,
    '/org/:name': Organization,
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
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--background-color);
    color: var(--text-color);
  }
</style>