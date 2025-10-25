<script>
  import { onMount } from 'svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  onMount(async () => {
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    let json = await res.json();
    console.log(json);
  });
</script>

<div class="repo-dashboard">

</div>
  
<style>
</style>
