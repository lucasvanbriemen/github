<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});

  onMount(async () => {
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();
  });
</script>

<div class="item-overview">
  <Sidebar {params} selectedSection="Issues" />

  <div class="item-main">
    <div class="item-header">
      <h2>{item.title}</h2>
      <p>Created by {item.opened_by?.name} {item.created_at_human}</p>
    </div>
  </div>
</div>

<style>
  .item-overview {
    height: 100%;
    width: 100%;
    display: flex;
    gap: 1rem;
    overflow: auto;
    .item-main {
      width: calc(85vw - 3rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;
    }
  }
</style>
