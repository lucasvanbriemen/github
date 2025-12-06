<script>
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  let { content = '', canEdit = true } = $props();
  let rendered = content ? marked.parse(content) : '';

  let isEditing = $state(false);
</script>

<div class="markdown-container" class:can-edit={canEdit}>
  {#if canEdit}
    <nav class="markdown-nav">
      <button class="edit-button button-primary-outline" onclick={() => isEditing = true}>Edit</button>
      <button class="preview-button button-primary-outline" onclick={() => isEditing = false}>Preview</button>
    </nav>
  {/if}

  {#if isEditing && canEdit}
    editing
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
