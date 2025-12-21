<script>
  import { onMount } from 'svelte';

  let { item, params } = $props();

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  function close() {
    api.post(route(`organizations.repositories.pr.update`, { organization, repository, number }, {
      state: 'closed',
    }));
    item.state = 'closed';
  }

  function merge() {
    api.post(route(`organizations.repositories.pr.merge`, { organization, repository, number }));
    item.state = 'merged';
  }

  function ready_for_review() {
    api.post(route(`organizations.repositories.pr.update`, { organization, repository, number }), {
      draft: false,
    });

    item.state = 'open';
  }

  onMount(() => {
    console.log(item.latest_commit.workflow);
  });
</script>

<div class="merge-panel">

  {#if item.latest_commit.workflow}
    workflow
  {/if}

  {#if item.state === 'open'}
    <button class="button-error-outline" onclick={() => close()}>Close Pull Request</button>
    <button class="button-primary" onclick={() => merge()}>Merge Pull Request</button>
  {/if}
  
  {#if item.state === 'draft'}
    <button class="button-primary ready-for-review" onclick={ready_for_review}>Ready for Review</button>
  {/if}
</div>
  
<style lang="scss">
  @import '../../../../scss/components/item/pr/merge-panel';
</style>
