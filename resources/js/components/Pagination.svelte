<script>
  let { links = [], onSelect = null } = $props();

  function handleClick(link) {
    if (!link || link.page == null) return;
    if (typeof onSelect === 'function') onSelect(link.page, link);
  }
</script>

<nav class="pagination">
  {#each links as link}
    <!-- If there is no url or if the label isnt a number -->
    {#if link.page !== null && /^\d+$/.test(link.label)}
      <a on:click={() => handleClick(link)} class:active={link.active}>
        {@html link.label}
      </a>
    {/if}
  {/each}
</nav>

<style lang="scss">
  @import '../../scss/components/pagination.scss';
</style>
