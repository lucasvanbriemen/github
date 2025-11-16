<script>
  let { item } = $props();
</script>

<div class="pr-notice">
  <b>{item.name}</b> had recent pushes 

  {#if item.last_commit}
    {item.last_commit.created_at_human}
  {/if}
</div>


<style lang="scss">
  @import '../../../scss/components/pr-notice';
</style>
