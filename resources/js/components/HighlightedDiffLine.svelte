<script>
  import { onMount } from 'svelte';
  import { highlightCodeInline } from '../utils/syntaxHighlighter.js';

  let { code = '', language = 'text' } = $props();

  let highlightedHtml = $state('');
  let currentTheme = $state('github-light');

  // Detect theme from document attribute
  function detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    return theme === 'dark' ? 'github-dark' : 'github-light';
  }

  async function highlight() {
    currentTheme = detectTheme();
    highlightedHtml = await highlightCodeInline(code, language, currentTheme);
  }

  onMount(() => {
    highlight();
  });
</script>

<span class="code">{@html highlightedHtml}</span>
