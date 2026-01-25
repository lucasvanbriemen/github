<script>
  import Icon from './Icon.svelte';

  let { text, label = 'Copy' } = $props();
  let isCopied = $state(false);

  function handleCopy() {
    navigator.clipboard.writeText(text);
    isCopied = true;
    setTimeout(() => {
      isCopied = false;
    }, 2000);
  }
</script>

<button onclick={handleCopy} class="copy-text-button button-primary-outline" class:copied={isCopied}>
  <Icon name='copy' size="1rem" />
  {isCopied ? 'Copied!' : label}
</button>

<style>
  .copy-text-button {
    display: flex;
    gap: 0.5rem;
    border-radius: 0.5rem;
    font-size: 14px;
    color: var(--text-color-secondary);
    
    &:not(.copied):hover {
      cursor: pointer;
      background-color: var(--primary-color-dark);
    }

    &.copied {
      opacity: 0.25;

      &:hover {
        cursor: default;
        background-color: transparent;
      }
    }
  }
</style>
