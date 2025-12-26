<script>
  let { activeItem, params = {}, children } = $props();

  let organization = $derived(params.organization);
  let repository = $derived(params.repository);

  let dropdownOpen = $state(false);
</script>

<div class="sidebar">
  {@render children?.() }

  <div class="nav">
    <div class="dropdown-menu" class:open={dropdownOpen}>
      <a class="item" href="#/{organization}/{repository}" onclick={() => dropdownOpen = false}>Overview</a>
      <a class="item" href="#/{organization}/{repository}/issues" onclick={() => dropdownOpen = false}>Issues</a>
      <a class="item" href="#/{organization}/{repository}/prs" onclick={() => dropdownOpen = false}>Pull Requests</a>
      <a class="item" href="#/{organization}/{repository}/projects" onclick={() => dropdownOpen = false}>Projects</a>
    </div>
    <button class="dropdown" onclick={() => (dropdownOpen = !dropdownOpen)} aria-expanded={dropdownOpen}>
      {activeItem}
    </button>
  </div>
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/sidebar";
</style>
