<script>
  import { organization, repository } from "../stores";

  let { item } = $props();
  let number = item.number;

  function close() {
    api.post(route(`organizations.repositories.item.update`, { $organization, $repository, number }), {
      state: 'closed',
    });
    item.state = 'closed';
  }
</script>

{#if item.state === 'open'}
  <div class="merge-panel">
    <button class="button-primary" onclick={() => close()}>Close issue</button>
  </div>
{/if}
  
<style lang="scss">
  @import '../../../scss/components/item/pr/merge-panel';
</style>
