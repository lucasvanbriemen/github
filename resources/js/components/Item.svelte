<script>
  import { onMount } from 'svelte';

  let { params = {} } = $props();
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  onMount(async () => {
    const res = await fetch(`/api/org/${name}/repo/${repository}/issues/${number}`);
    let json = await res.json();
    console.log(json);
  });
</script>

<div class="repo-dashboard">

</div>
  
<style>
</style>
