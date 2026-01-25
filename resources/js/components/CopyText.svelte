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
    border: 2px solid var(--primary-color);
    padding: 0.5rem 1rem;
    border-radius: 1rem;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
      background-color: var(--primary-color);
    }

    &.copied {
      border-color: var(--success-color, #22c55e);
      color: var(--success-color, #22c55e);

      &:hover {
        background-color: var(--success-color, #22c55e);
      }
    }
  }
</style>
