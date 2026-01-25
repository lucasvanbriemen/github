<script>
  import Icon from './Icon.svelte';

  let { text, label = 'Copy' } = $props();
  let isCopied = $state(false);

  async function handleCopy() {
    try {
      await navigator.clipboard.writeText(text);
      isCopied = true;
      setTimeout(() => {
        isCopied = false;
      }, 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  }
</script>

<button
  onclick={handleCopy}
  class="copy-text-button"
  class:copied={isCopied}
  title={isCopied ? 'Copied!' : 'Copy to clipboard'}
>
  <Icon name={isCopied ? 'checkmark' : 'copy'} size="1rem" />
  <span>{isCopied ? 'Copied!' : label}</span>
</button>

<style>
  .copy-text-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: transparent;
    border: 2px solid var(--primary-color-dark);
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 14px;
    transition: all 0.3s ease;
    width: fit-content;
    
    &:not(.copied):hover {
      cursor: pointer;
      background-color: var(--primary-color-dark);
    }

    &.copied {
      opacity: 0.25;
    }
  }
</style>
