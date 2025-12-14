<script>
    import { on } from "svelte/events";
  import Icon from "./Icon.svelte";

  let { name = 'select', selectableItems = [], selectedValue = $bindable(), placeholder = 'Search...', searchable = true, multiple = false, onChange } = $props();

  let menuOpen = $state(false);
  let searchQuery = $state('');
  let inputElement;

  let displayValue = $derived(() => {
    const selectedItem = selectableItems.find(o => o.value === selectedValue);
    return selectedItem?.label || '';
  });

  let visableOptions = $derived(() => {
    if (!searchQuery || !searchable) return selectableItems;

    const query = searchQuery.toLowerCase();
    return selectableItems.filter(option => option.label.toLowerCase().includes(query));
  });

  function handleClickOutside(event) {
    if (!event.target.closest('.search-select-wrapper')) {
      menuOpen = false;
      
      if (multiple) {
        const selectedItems = selectableItems.filter(o => o.selected);
        let selectedValues = [];
        selectedItems.forEach(item => selectedValues.push(item.value));
        selectedValue = selectedValues;
        
        onChange?.({ selectedValue });
      }
    }
  }

  function selectOption(optionValue) {
    
    if (multiple) {
      const option = selectableItems.find(o => o.value === optionValue);
      option.selected = !option.selected;
      
      return;
    }
    
    selectedValue = optionValue;
    menuOpen = false;
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
    <option value="" disabled selected hidden></option>
    {#each selectableItems as option}
      <option value={option.value}>{option.label}</option>
    {/each}
  </select>

  <input
    bind:this={inputElement}
    class="search-input"
    type="text"
    {placeholder}
    readonly={searchable === false}
    value={(menuOpen && searchable) ? searchQuery : displayValue()}
    oninput={(event) => searchQuery = event.target.value}
    onclick={() => menuOpen = true}
  />

  {#if menuOpen}
    <div class="option-wrapper">
      {#each visableOptions() as option (option.value)}
        <button class="option-item" class:active={selectedValue == option.value || option.selected} onclick={() => selectOption(option.value)} type="button">
          <div>
            {#if option.image}
              <img src={option.image} alt={option.label} class="option-image" />
            {/if}
            {option.label}
          </div>

          {#if multiple && option.selected}
            <Icon name="checkmark" className="icon checkmark" />
          {/if}
        </button>
      {/each}
      {#if visableOptions().length === 0}
        <div class="no-results">No results found</div>
      {/if}
    </div>
  {/if}
</div>

<style lang="scss">
  @import "../../scss/components/search-select";
</style>
