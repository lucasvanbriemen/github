<script>
  let { files = [], loadingFiles = false } = $props();
</script>

<div class="pr-files">
  {#if !loadingFiles}
    <div class="loading">Loading files...</div>
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/item.scss';
</style>
