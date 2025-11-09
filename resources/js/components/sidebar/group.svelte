<script>
  let { title, children } = $props();
</script>

<div class="group">
  <span class="group-title">{title}</span>
  {@render children?.() }
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/group";
</style>