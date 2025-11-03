<script>
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  export let content = ''; // default to empty string

  let rendered = '';

  $: rendered = content ? marked.parse(content) : '';
</script>

<div class="markdown-body">
  {@html rendered}
</div>

<style>
  @import '../../scss/components/markdown.scss';
</style>
