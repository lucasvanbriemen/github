<script>
  let { item, params } = $props();

  const org = params.organization;
  const repo = params.repository;
</script>

<div class="pr-notice">
  <div>
    <b>{item.name}</b> had recent pushes 

    {#if item.last_commit}
      {item.last_commit.created_at_human}
    {/if}
  </div>

  <a href="#/{org}/{repo}/pr/{item.name}" class="create-pr button-primary">Create PR</a>
</div>

<style lang="scss">
  @import '../../../scss/components/pr-notice';
</style>
