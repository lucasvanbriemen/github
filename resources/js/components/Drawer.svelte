<script>
  import { onMount } from 'svelte';

  let { isOpen = false, onClose, title = 'Drawer', children } = $props();

  function handleBackdropClick(e) {
    if (e.target === e.currentTarget) {
      onClose?.();
    }
  }

  onMount(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.body.style.overflow = '';
    };
  });

  $effect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  });
</script>

{#if isOpen}
  <div class="drawer-backdrop" onclick={handleBackdropClick}>
    <div class="drawer">
      <div class="drawer-header">
        <h2 class="drawer-title">{title}</h2>
        <button class="drawer-close" onclick={onClose} aria-label="Close drawer">
          ✕
        </button>
      </div>
      <div class="drawer-content">
        {@render children?.()}
      </div>
    </div>
  </div>
{/if}

<style lang="scss">
  @import '../../scss/components/drawer';
</style>
