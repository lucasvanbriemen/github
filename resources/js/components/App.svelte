<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import ItemOverview from './itemOverview/ItemOverview.svelte';
  import Item from './item/Item.svelte';
  import NewPullRequest from './item/pr/NewPullRequest.svelte';
  import theme from '../theme.js';

  const routes = {
    '/': Dashboard,
    '/:organization/:repository': RepositoryDashboard,
    '/:organization/:repository/issues': ItemOverview,
    '/:organization/:repository/issues/:number': Item,
    '/:organization/:repository/prs': ItemOverview,
    '/:organization/:repository/new/pr/:branch': NewPullRequest,
    '/:organization/:repository/prs/:number': Item,
  };

  onMount(async () => {
    theme.applyTheme();
  });
</script>

<Header />
<Router {routes} />
