<script>
  import { onMount, untrack } from 'svelte';
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  let { content = '', canEdit = true } = $props();
  let rendered = $state('');
  let isEditing = $state(false);

  onMount(async () => {
    rendered = content ? marked.parse(content) : '';
  });

  $effect(() => {
    void content;
    untrack(() => {
      rendered = content ? marked.parse(content) : '';
    });
  });

</script>

<div class="markdown-container" class:can-edit={canEdit}>
  {#if canEdit}
    <nav class="markdown-nav">
      <button class="edit-button button-primary-outline" onclick={() => isEditing = true}>Edit</button>
      <button class="preview-button button-primary-outline" onclick={() => isEditing = false}>Preview</button>
    </nav>
  {/if}

  {#if isEditing && canEdit}
    <textarea
      class="markdown-editor"
      bind:value={content}
    ></textarea>
  {:else}
    <div class="markdown-body">
      {#if content}
        {@html rendered}
      {:else}
        <p class="empty-state">No content</p>
      {/if}
    </div>
  {/if}
</div>

<style>
  @import '../../scss/components/markdown.scss';
</style>
