<script>
  import { onMount } from 'svelte';
  let { selectedDropdownSection, params = {}, children } = $props();

  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');

  let dropdownOpen = $state(false);

  function parseHash() {
    const hash = (window.location.hash || '').replace(/^#\/?/, '');
    const parts = hash.split('/').filter(Boolean);
    // parts: [organization, repository, section?, id?]
    organization = parts[0] || organization;
    repository = parts[1] || repository;
  }

  onMount(() => {
    parseHash();
    window.addEventListener('hashchange', parseHash);
  });
</script>

<div class="sidebar">
  {@render children?.() }

  <div class="nav">
    <div class="dropdown-menu" class:open={dropdownOpen}>
      <a class="item" href="#/{organization}/{repository}" onclick={() => dropdownOpen = false}>Overview</a>
      <a class="item" href="#/{organization}/{repository}/issues" onclick={() => dropdownOpen = false}>Issues</a>
      <a class="item" href="#/{organization}/{repository}/prs" onclick={() => dropdownOpen = false}>Pull Requests</a>
    </div>
    <button class="dropdown" onclick={() => (dropdownOpen = !dropdownOpen)} aria-expanded={dropdownOpen}>
      {selectedDropdownSection}
    </button>
  </div>
</div>

<style lang="scss">
  @import "../../scss/components/sidebar";
</style>
