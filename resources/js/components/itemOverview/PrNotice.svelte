<script>
  import { organization, repository } from "../stores";

  let { item } = $props();
</script>

<div class="pr-notice">
  <div>
    <b>{item.name}</b> had recent pushes 

    {#if item.last_commit}
      {item.last_commit.created_at_human}
    {/if}
  </div>

  <a href="#/{$organization}/{$repository}/new/pr/{item.name}" class="create-pr button-primary">Create PR</a>
</div>

<style lang="scss">
  @import '../../../scss/components/pr-notice';
</style>
