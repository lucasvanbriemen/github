<script>
  import { onMount } from 'svelte';

  let { params = {} } = $props();

  let id = params.id;
  let notification = $state([]);


  onMount(async () => {
    notification = await api.get(route('notification.show', { id }));
  });
</script>

test

<style>
  @import "../../scss/components/email-notification.scss";
</style>
