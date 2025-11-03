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

  function linkTo(path = '') {
    // path examples: '', 'issues', 'issues/123', 'prs', 'prs/45'
    const route = `#/${organization}/${repository}${path ? '/' + path : ''}`;
    window.location.hash = route;
    dropdownOpen = false;
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
      <a class="item" on:click={() => linkTo('')}>Home</a>
      <a class="item" on:click={() => linkTo('issues')}>Issues</a>
      <a class="item" on:click={() => linkTo('prs')}>Pull Requests</a>
    </div>
    <button class="dropdown" on:click={() => (dropdownOpen = !dropdownOpen)} aria-expanded={dropdownOpen}>
      {selectedDropdownSection}
    </button>
  </div>
</div>

<style lang="scss">
  @import "../../scss/components/sidebar";
</style>
