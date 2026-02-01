<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import EmailNotification from './EmailNotification.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import ItemOverview from './itemOverview/ItemOverview.svelte';
  import ProjectsOverview from './ProjectsOverview.svelte';
  import Project from './Project.svelte';
  import Item from './item/Item.svelte';
  import NewItem from './item/NewItem.svelte';
  import BranchTreeView from './branches/BranchTreeView.svelte';
  import theme from '../lib/theme.js';
  import api from '../lib/api.js';

  const routes = {
    '/': Dashboard,
    '/notification/:id': EmailNotification,
    '/:organization/:repository': RepositoryDashboard,

    '/:organization/:repository/projects': ProjectsOverview,
    '/:organization/:repository/projects/:number': Project,

    '/:organization/:repository/branches': BranchTreeView,

    // Item Related
    '/:organization/:repository/:type': ItemOverview,
    '/:organization/:repository/new/:type/:branch?': NewItem,
    '/:organization/:repository/:type/:number/:tab?': Item,
  };

  onMount(async () => {
    theme.applyTheme();
  });

  window.api = api;
</script>

<Header />
<Router {routes} />
