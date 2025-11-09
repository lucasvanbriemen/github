<script>
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let {
    name = 'select',
    selectableItems = [],
    selectedValue = $bindable(),
    placeholder = 'Search...',
    multiple = false,
    searchable = false
  } = $props();

  let open = $state(false);
  let searchQuery = $state('');
  let inputElement;

  // For single select, display the current selection
  // For multi-select, show count or placeholder
  let displayValue = $derived(() => {
    const opt = selectableItems.find(o => o.value === selectedValue);
    return opt?.label || '';
  });

  // Filter selectableItems based on search query
  let filteredOptions = $derived(() => {
    if (!searchQuery) return selectableItems;
    if (!searchable) return selectableItems;
    const q = searchQuery.toLowerCase();
    return selectableItems.filter(opt =>
      opt.label.toLowerCase().includes(q)
    );
  });

  function handleOpen() {
    open = true;
  }

  function handleClose() {
    open = false;
  }

  function handleClickOutside(event) {
    if (!event.target.closest('.search-select-wrapper')) {
      handleClose();
    }
  }
  function selectOption(optionValue) {
      selectedValue = optionValue;
      handleClose();
    dispatch('change', { selectedValue });
  }

  function isSelected(optionValue) {
    return selectedValue === optionValue;
  }

  $effect(() => {
    if (open) {
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

  <div class="select-ui-wrapper" class:open>
    <input
      bind:this={inputElement}
      class="search-input"
      type="text"
      {placeholder}
      value={open ? searchQuery : displayValue()}
      on:input={(e) => searchQuery = e.target.value}
      on:focus={handleOpen}
      on:click={handleOpen}
    />

    {#if open}
      <div class="option-wrapper">
        {#each filteredOptions() as option (option.value)}
          <div
            class="option-item"
            class:active={isSelected(option.value)}
            on:click={() => selectOption(option.value)}
            role="option"
            aria-selected={isSelected(option.value)}
          >
            <span class="main-text">{option.label}</span>
            {#if multiple && isSelected(option.value)}
              <span class="checkmark">✓</span>
            {/if}
          </div>
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
