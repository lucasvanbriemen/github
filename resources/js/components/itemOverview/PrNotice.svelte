<script>
  let { item } = $props();
</script>

<div class="pr-notice">
  <div>
    <b>{item.name}</b> had recent pushes 

    {#if item.last_commit}
      {item.last_commit.created_at_human}
    {/if}
  </div>

  <button type="button" class="button-primary create-pr">Create PR</button>
</div>

<style lang="scss">
  @import '../../../scss/components/pr-notice';
</style>
