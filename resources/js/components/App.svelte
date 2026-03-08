<script>
  import { onMount } from 'svelte';
  import Router from 'svelte-spa-router';
  import Header from './Header.svelte';
  import Dashboard from './Dashboard.svelte';
  import EmailNotification from './EmailNotification.svelte';
  import NotificationOverview from './NotificationOverview.svelte';
  import RepositoryDashboard from './RepositoryDashboard.svelte';
  import ItemOverview from './itemOverview/ItemOverview.svelte';
  import ProjectsOverview from './ProjectsOverview.svelte';
  import Project from './Project.svelte';
  import Item from './item/Item.svelte';
  import NewItem from './item/NewItem.svelte';
  import theme from '../lib/theme.js';
  import api from '../lib/api.js';
  import { toast } from '../lib/toast.js';
  import Toast from './Toast.svelte';

  const routes = {
    '/': Dashboard,
    '/notification/:id': EmailNotification,
    '/notifications/:date': NotificationOverview,
    '/:organization/:repository': RepositoryDashboard,

    '/:organization/:repository/projects': ProjectsOverview,
    '/:organization/:repository/projects/:number': Project,

    // Item Related
    '/:organization/:repository/:type': ItemOverview,
    '/:organization/:repository/new/:type/:branch?': NewItem,
    '/:organization/:repository/:type/:number/:tab?': Item,
  };

  onMount(async () => {
    theme.applyTheme();
  });

  window.api = api;
  window.toast = toast;
</script>

<Header />
<Router {routes} />
<Toast />
