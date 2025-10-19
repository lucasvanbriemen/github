<script>
  let { links = [], onSelect = null } = $props();

  function handleClick(link) {
    if (!link || link.page == null) return;
    if (typeof onSelect === 'function') onSelect(link.page, link);
  }
</script>

<nav class="pagination">
  {#each links as link}
    {#if link.page !== null}
      <a on:click={() => handleClick(link)} class:active={link.active}>
        {@html link.label}
      </a>
    {/if}
  {/each}
</nav>

<style>
  .pagination {
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
    width: 25%;
    margin: 1rem auto;
    padding: 1rem;
    display: flex;
    justify-content: center;
    gap: 0.5rem;
  }

  .pagination a {
    font-weight: bold;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 5rem;
    text-decoration: none;
    border: 2px solid var(--primary-color-dark);
  }

  .pagination a:hover {
    background-color: var(--primary-color-dark);
  }

  .pagination a.active {
    background-color: var(--primary-color);
  }
</style>
