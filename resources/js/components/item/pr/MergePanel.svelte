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

<div class="merge-panel">
  {#if item.state === 'open'}
    <button class="button-primary" onclick={() => close()}>Close Pull Request</button>
    <button class="button-primary" onclick={() => merge()}>Merge Pull Request</button>
  {/if}
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/merge-panel';
</style>
