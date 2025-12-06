<script>
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  let { content = '' } = $props();
  let rendered = marked.parse(content);
</script>

<div class="markdown-body">
  {#if content}
    {@html rendered}
  {:else}
    <p class="empty-state">No content</p>
  {/if}
</div>

<style>
  @import '../../scss/components/markdown.scss';
</style>
