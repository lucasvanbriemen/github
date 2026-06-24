<script>
  import { onMount } from "svelte";

  let { title, children } = $props();

  let maxHeight = $state(0);
  let body;

  onMount(() => {
    maxHeight = body.scrollHeight;
    body.style.maxHeight = maxHeight + 'px';
  });
</script>

<div class="group">
  <button class="group-title">{title}</button>
  
  <div class="body" bind:this={body}>{@render children?.() }</div>
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/group";
</style>