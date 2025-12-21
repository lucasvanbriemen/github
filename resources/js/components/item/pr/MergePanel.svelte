<script>
  let { item, params } = $props();

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  function close() {
    api.post(route(`organizations.repositories.pr.close`, { organization, repository, number }));
    item.state = 'closed';
  }

  function merge() {
    api.post(route(`organizations.repositories.pr.merge`, { organization, repository, number }));
    item.state = 'merged';
  }
</script>

{#if item.state === 'open'}
  <div class="merge-panel">
    <button class="button-error-outline" onclick={() => close()}>Close Pull Request</button>
    <button class="button-primary" onclick={() => merge()}>Merge Pull Request</button>
  </div>
{/if}
  
<style lang="scss">
  @import '../../../../scss/components/item/pr/merge-panel';
</style>
