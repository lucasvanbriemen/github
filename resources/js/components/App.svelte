<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import ItemOverview from './itemOverview/ItemOverview.svelte';
  import Item from './item/Item.svelte';
  import NewPullRequest from './item/pr/NewPullRequest.svelte';
  import theme from '../lib/theme.js';
  import api from '../lib/api.js';

  const routes = {
    '/': Dashboard,
    '/:organization/:repository': RepositoryDashboard,

    // Item Related
    '/:organization/:repository/:type': ItemOverview,
    '/:organization/:repository/new/:type/:branch?': NewPullRequest,
    '/:organization/:repository/:type/:number/:tab?': Item,
  };

  onMount(async () => {
    theme.applyTheme();
  });

  window.api = api;
</script>

<Header />
<Router {routes} />
