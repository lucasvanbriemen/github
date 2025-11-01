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

<style>
  .sidebar {
    width: 15vw;
    background-color: var(--background-color-one);
    border-right: 1px solid var(--color-border);
    position: relative;
    height: 100%;
    position: sticky;
    top: 0;

    &::before {
      content: '';
      position: absolute;
      top: 0;
      right: -1rem;
      height: 1rem;
      width: 1rem;
      background: radial-gradient(circle at bottom right, transparent 1rem, var(--background-color-one) 1rem);
    }

    .filter-section {
      padding: 1rem 0.5rem;
      border-bottom: 1px solid var(--border-color);
    }

    .nav {
      position: absolute;
      bottom: 0.5rem;
      left: 0.5rem;
      width: calc(15vw - 1rem);

      .dropdown {
        display: flex;
        background-color: transparent;
        background-color: var(--background-color);
        border: 2px solid var(--primary-color-dark);
        border-radius: 0.5rem;
        padding: 1rem;
        width: 100%;
        height: 3rem;
        align-items: center;
        cursor: pointer;
        font-size: 1rem;

        &:hover {
          background-color: var(--primary-color-dark);
        }
      }

      .dropdown-menu {
        background-color: var(--background-color-two);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        height: 0;
        overflow: hidden;
        padding: 0 0.5rem;

        .item {
          padding: 0.5rem;
          border-radius: 0.25rem;
          cursor: pointer;

          &:hover {
            background-color: var(--primary-color);
          }
        }

        &.open {
          height: 6rem;
          padding: 1rem 0.5rem;
          box-shadow: 0 0 3rem -1rem var(--primary-color);
        }
      }
    }
  }
</style>
