<script>
  import { organization, repository } from "../stores";
  import ConfirmationModal from "../ConfirmationModal.svelte";

  let { item } = $props();
  let number = item.number;
  let closeConfirmOpen = $state(false);

  function close() {
    api.post(route(`organizations.repositories.item.update`, { $organization, $repository, number }), {
      state: 'closed',
    });
    item.state = 'closed';
  }
</script>

{#if item.state === 'open'}
  <div class="merge-panel">
    <button class="button-primary" onclick={() => closeConfirmOpen = true}>Close issue</button>
  </div>
{/if}

{#if closeConfirmOpen}
  <ConfirmationModal
    isOpen={closeConfirmOpen}
    onClose={() => closeConfirmOpen = false}
    onConfirm={close}
    title="Close Issue"
    message="Are you sure you want to close this issue? The issue can be reopened later."
    confirmText="Close"
    cancelText="Cancel"
    variant="primary"
  />
{/if}

<style lang="scss">
  @import '../../../scss/components/item/pr/merge-panel';
</style>
