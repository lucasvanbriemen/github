<script>
  import Router from 'svelte-spa-router';
  import HelloWorld from './HelloWorld.svelte';
  import Organization from './Organization.svelte';

  const routes = {
    '/': HelloWorld,
    '/org/:name': Organization,
  };
</script>

<Router {routes} />
