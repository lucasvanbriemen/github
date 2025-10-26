<script>
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';
  import { onMount } from 'svelte';

  export let content = ''; // default to empty string

  let rendered = '';

  $: rendered = content ? marked.parse(content) : '';
</script>

<div class="markdown-body">
  {@html rendered}
</div>

<style>
  .markdown-body {
    padding: 1rem;
    background-color: var(--background-color);
    border: 2px solid var(--border-color);
    border-radius: 1rem;

    :global(p), :global(li) {
      color: var(--text-color-secondary);
    }

    :global(blockquote) {
      opacity: 0.8;
    }

    :global(a) {
      color: var(--primary-color);
    }
  }
</style>
