<script>
  import { onMount } from 'svelte';
  import { highlightCodeInline } from '../utils/syntaxHighlighter.js';

  let { code = '', language = 'text' } = $props();

  let highlightedHtml = $state('');
  let isLoading = $state(true);
  let currentTheme = $state('github-light');

  // Detect theme from document attribute
  function detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    return theme === 'dark' ? 'github-dark' : 'github-light';
  }

  async function highlight() {
    currentTheme = detectTheme();
    highlightedHtml = await highlightCodeInline(code, language, currentTheme);
    isLoading = false;
  }

  onMount(() => {
    highlight();
  });
</script>

{#if isLoading}
  <span class="code">{code}</span>
{:else}
  <span class="code highlighted">
    {@html highlightedHtml}
  </span>
{/if}

<style>
  .code.highlighted {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    line-height: inherit;
  }

  .code.highlighted :global(span) {
    /* Inherit diff background colors from parent */
    background: transparent !important;
  }

  /* Ensure syntax colors have good contrast with diff backgrounds */
  :global([data-theme="dark"]) .diff-line-add .code.highlighted :global(span),
  :global([data-theme="dark"]) .diff-line-del .code.highlighted :global(span) {
    /* Slightly increase brightness of syntax colors on diff backgrounds in dark mode */
    filter: brightness(1.1) saturate(1.1);
  }

  /* Light mode - slightly reduce brightness on green/red backgrounds for better contrast */
  :global([data-theme="light"]) .diff-line-add .code.highlighted :global(span),
  :global([data-theme="light"]) .diff-line-del .code.highlighted :global(span) {
    filter: brightness(0.85) saturate(1.2);
  }
</style>
