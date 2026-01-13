<script>
  let { children } = $props();
</script>

<div class="sidebar">
  {@render children?.() }
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/sidebar";
</style>
