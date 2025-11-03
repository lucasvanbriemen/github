<script>
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let {
    name = 'select',
    options = [],
    value = $bindable(),
    placeholder = 'Search...',
    multiple = false
  } = $props();

  let open = $state(false);
  let searchQuery = $state('');
  let inputElement;

  // For single select, display the current selection
  // For multi-select, show count or placeholder
  let displayValue = $derived(() => {
    if (multiple) {
      if (!Array.isArray(value) || value.length === 0) return '';
      if (value.length === 1) {
        const opt = options.find(o => o.value === value[0]);
        return opt?.label || value[0];
      }
      return `${value.length} selected`;
    }
    const opt = options.find(o => o.value === value);
    return opt?.label || '';
  });

  // Filter options based on search query
  let filteredOptions = $derived(() => {
    if (!searchQuery) return options;
    const q = searchQuery.toLowerCase();
    return options.filter(opt =>
      opt.label.toLowerCase().includes(q)
    );
  });

  function handleOpen() {
    open = true;
    searchQuery = multiple ? '' : displayValue();
  }

  function handleClose() {
    open = false;
    searchQuery = '';
  }

  function handleClickOutside(event) {
    if (!event.target.closest('.search-select-wrapper')) {
      handleClose();
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      const q = searchQuery.trim().toLowerCase();

      // Try exact match first
      let target = filteredOptions().find(opt =>
        opt.label.toLowerCase() === q || opt.value.toLowerCase() === q
      );

      // Otherwise use first visible option
      if (!target && filteredOptions().length > 0) {
        target = filteredOptions()[0];
      }

      if (target) {
        selectOption(target.value);
      }
      handleClose();
    } else if (event.key === 'Escape') {
      handleClose();
    }
  }

  function selectOption(optionValue) {
    if (multiple) {
      if (!Array.isArray(value)) {
        value = [];
      }
      const index = value.indexOf(optionValue);
      if (index > -1) {
        value = value.filter(v => v !== optionValue);
      } else {
        value = [...value, optionValue];
      }
    } else {
      value = optionValue;
      handleClose();
    }
    dispatch('change', { value });
  }

  function isSelected(optionValue) {
    if (multiple) {
      return Array.isArray(value) && value.includes(optionValue);
    }
    return value === optionValue;
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
  <select {name} style="display: none;" bind:value>
    {#each options as option}
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
      on:keydown={handleKeydown}
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
