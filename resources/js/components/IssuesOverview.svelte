<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';

  let { params = {} } = $props();
  let name = $derived(params.name || '');
  let repository = $derived(params.repository || '');
  let issues = $state([]);
  let paginationLinks = $state([]);

  async function getIssues(pageNr) {
    const res = await fetch(`/api/org/${name}/repo/${repository}/issues?page=${pageNr}`);
    let json = await res.json();
    issues = json.data;
    paginationLinks = json.links;
  }

  onMount(async () => {
    await getIssues(1);
  });

</script>

<div class="repo-dashboard">
  <Sidebar {params} selectedSection="Issues" />
  <div class="repo-main">
    {#each issues as issue}
      <div class="issue">
        <h3>{issue.title}</h3>
      </div>
    {/each}

    {#if paginationLinks.length > 3}
      <div class="pagination">
       {#each paginationLinks as link}
          {#if link.page !== null}
            <a on:click={() => getIssues(link.page)} class:active={link.active}>
              {@html link.label}
            </a>
          {/if}
        {/each}
      </div>
    {/if}
  </div>
</div>
  
<style>
  .repo-dashboard {
    height: 100%;
    width: 100%;

    display: flex;
    gap: 0.5rem;
    overflow: auto;

    .repo-main {
      width: calc(85vw - 2rem);
      .pagination {
        border: 1px solid var(--border-color);
        border-radius: 0.25rem;
        width: 25%;
        margin: 1rem auto;
        padding: 1rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;

        a {
          font-weight: bold;
          cursor: pointer;
          padding: 0.25rem 0.5rem;
          border-radius: 5rem;
          text-decoration: none;
          border: 2px solid var(--primary-color-dark);

          &:hover {
            background-color: var(--primary-color-dark);
          }

          &.active {
            background-color: var(--primary-color);
          }
        }
      }
    }
  }
</style>
