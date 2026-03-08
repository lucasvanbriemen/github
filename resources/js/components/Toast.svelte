<script>
  import { toasts } from '../lib/toast.js';
</script>

{#if $toasts.length > 0}
  <div class="toast-container">
    {#each $toasts as t (t.id)}
      <div class="toast {t.type}">
        <p>{t.message}</p>
      </div>
    {/each}
  </div>
{/if}

<style lang="scss">
  @import '../../scss/components/toast';
</style>
