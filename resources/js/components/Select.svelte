<script>
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let { name = 'select', selectableItems = [], selectedValue = $bindable(), placeholder = 'Search...', searchable = false, onChange } = $props();

  let menuOpen = $state(false);
  let searchQuery = $state('');
  let inputElement;

  let displayValue = $derived(() => {
    const selectedItem = selectableItems.find(o => o.value === selectedValue);
    return selectedItem?.label || '';
  });

  // Filter selectableItems based on search query if we can search
  let filteredOptions = $derived(() => {
    if (!searchQuery || !searchable) return selectableItems;

    const query = searchQuery.toLowerCase();
    return selectableItems.filter(option => option.label.toLowerCase().includes(query));
  });

  function handleOpen() {
    menuOpen = true;
  }

  function handleClose() {
    menuOpen = false;
  }

  function handleClickOutside(event) {
    if (!event.target.closest('.search-select-wrapper')) {
      handleClose();
    }
  }

  function selectOption(optionValue) {
    selectedValue = optionValue;
    handleClose();
    onChange?.({ selectedValue });
  }

  $effect(() => {
    if (menuOpen) {
      document.addEventListener('click', handleClickOutside);
      inputElement?.focus();
      return () => {
        document.removeEventListener('click', handleClickOutside);
      };
    }
  });
</script>

<div class="search-select-wrapper">
  <select {name} style="display: none;" bind:value={selectedValue}>
    {#each selectableItems as option}
      <option value={option.value}>{option.label}</option>
    {/each}
  </select>

  <div class="select-ui-wrapper" class:open={menuOpen}>
    <input
      bind:this={inputElement}
      class="search-input"
      type="text"
      {placeholder}
      readonly={searchable === false}
      value={(menuOpen && searchable) ? searchQuery : displayValue()}
      oninput={(event) => searchQuery = event.target.value}
      onclick={handleOpen}
    />

    {#if menuOpen}
      <div class="option-wrapper">
        {#each filteredOptions() as option (option.value)}
          <button
            class="option-item"
            class:active={selectedValue == option.value}
            onclick={() => selectOption(option.value)}
            type="button"
          >
            <span class="main-text">{option.label}</span>
          </button>
        {/each}
        {#if filteredOptions().length === 0}
          <div class="no-results">No results found</div>
        {/if}
      </div>
    {/if}
  </div>
</div>

<style lang="scss">
  @import "../../scss/components/search-select";
</style>
