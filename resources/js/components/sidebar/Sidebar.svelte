<script>
  let { children, class: className = '' } = $props();
</script>

<div class="sidebar {className}">
  {@render children?.() }
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/sidebar";
</style>
