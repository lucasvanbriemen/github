<script>
  import { onMount } from "svelte";

  let { title, children } = $props();
  let isOpen = $state(true);

  let maxHeight = $state(0);
  let body;

  function toggleOpen() {
    isOpen = !isOpen;

    if (isOpen) {
      maxHeight = body.scrollHeight;
      body.style.maxHeight = maxHeight + 'px';
    } else {
      body.style.maxHeight = 0;
    }
  }

  onMount(() => {
    maxHeight = body.scrollHeight;
    body.style.maxHeight = maxHeight + 'px';
  });
</script>

<div class="group" class:open={isOpen}>
  <button class="group-title" onclick={toggleOpen}>{title}</button>
  
  <div class="body" bind:this={body}>{@render children?.() }</div>
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/group";
</style>