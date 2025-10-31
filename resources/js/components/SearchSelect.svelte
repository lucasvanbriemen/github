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

<style>
  .search-select-wrapper {
    position: relative;
    display: inline-block;
  }

  .select-ui-wrapper {
    position: relative;
    width: 220px;
  }

  .search-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--background-color-one);
    color: var(--text-color);
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;

    &:focus {
      border-color: var(--primary-color);
    }
  }

  .option-wrapper {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--background-color-one);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    max-height: 240px;
    overflow: auto;
    padding: 4px;
    z-index: 20;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .option-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s;

    &:hover {
      background: var(--background-color-two);
    }

    &.active {
      background: var(--primary-color-dark);
      outline: 1px solid var(--primary-color);
    }

    .main-text {
      font-weight: 600;
    }

    .checkmark {
      color: var(--primary-color);
      font-weight: bold;
    }
  }

  .no-results {
    padding: 12px 8px;
    text-align: center;
    color: var(--subtext-color);
    font-size: 14px;
  }
</style>
