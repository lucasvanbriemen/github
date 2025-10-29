<script>
  import { onMount } from 'svelte';
  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  let { selectedDropdownSection, showDetailsFrom, params = {} } = $props();

  let organization = $derived(params.organization || '');
  let repo = $derived(params.repository || '');

  let dropdownOpen = $state(false);
  let state = $state('open');

  function handleFilterChange(e) {
    state = e.target.value;
    dispatch('filterChange', { state });
  }

  function parseHash() {
    const hash = (window.location.hash || '').replace(/^#\/?/, '');
    const parts = hash.split('/').filter(Boolean);
    // parts: [organization, repo, section?, id?]
    organization = parts[0] || organization;
    repo = parts[1] || repo;
  }

  function linkTo(path = '') {
    // path examples: '', 'issues', 'issues/123', 'prs', 'prs/45'
    const route = `#/${organization}/${repo}${path ? '/' + path : ''}`;
    window.location.hash = route;
    dropdownOpen = false;
  }

  onMount(() => {
    parseHash();
    const onHash = () => parseHash();
    window.addEventListener('hashchange', onHash);
    return () => window.removeEventListener('hashchange', onHash);
  });
</script>

<div class="sidebar">
  {#if showDetailsFrom === 'item-list'}
    <select name="state" bind:value={state} on:change={handleFilterChange}>
      <option value="open">Open</option>
      <option value="closed">Closed</option>
      <option value="all">All</option>
    </select>
  {/if}

  <div class="nav">
    <div class="dropdown-menu" class:open={dropdownOpen}>
      <a class="item" on:click={() => linkTo('')}>Home</a>
      <a class="item" on:click={() => linkTo('issues')}>Issues</a>
      <a class="item" on:click={() => linkTo('prs')}>PRs</a>
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

    .nav {
      position: absolute;
      bottom: 0.5rem;
      left: 0.5rem;
      width: calc(15vw - 1rem);

      .dropdown {
        display: flex;
        background-color: transparent;
        background-color: var(--primary-color);
        border: none;
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
