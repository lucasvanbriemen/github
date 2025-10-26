<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import ItemIcon from './ItemIcon.svelte';
  import Markdown from './Markdown.svelte';

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
      <span><ItemIcon itemType={item.type} state={item.state} /> {item.state}</span>
    </div>

    <div class="item-body">
      <div>created  {item.created_at_human} by <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} /> {item.opened_by?.name}</div>

      <Markdown content={item.body} />
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

      .item-header {
        background-color: var(--background-color-one);
        padding: 1rem;
        border-radius: 0.5rem;

        h2 {
          margin: 0;
        }

        div {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-top: 0.5rem;
          color: var(--text-color-secondary);
        }

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }
    }
  }
</style>
