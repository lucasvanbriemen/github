import { createHighlighter } from 'shiki';

let highlighterInstance = null;
let highlighterPromise = null;

// Map file extensions to Shiki language identifiers
const extensionToLanguage = {
  'js': 'javascript',
  'jsx': 'jsx',
  'ts': 'typescript',
  'tsx': 'tsx',
  'vue': 'vue',
  'svelte': 'svelte',
  'php': 'php',
  'py': 'python',
  'rb': 'ruby',
  'go': 'go',
  'rs': 'rust',
  'java': 'java',
  'c': 'c',
  'cpp': 'cpp',
  'cs': 'csharp',
  'css': 'css',
  'svelte': 'javascript',
  'scss': 'scss',
  'sass': 'sass',
  'less': 'less',
  'html': 'html',
  'xml': 'xml',
  'json': 'json',
  'yaml': 'yaml',
  'yml': 'yaml',
  'md': 'markdown',
  'sql': 'sql',
  'sh': 'bash',
  'bash': 'bash',
  'zsh': 'bash',
};

/**
 * Detect language from filename
 */
export function detectLanguage(filename) {
  if (!filename) return 'text';

  const file_extension = filename.split('.').pop()?.toLowerCase();
  return extensionToLanguage[file_extension] || 'text';
}

/**
 * Initialize the Shiki highlighter (singleton)
 */
async function getHighlighter() {
  if (highlighterInstance) {
    return highlighterInstance;
  }

  if (!highlighterPromise) {
    highlighterPromise = createHighlighter({
      themes: ['github-light', 'github-dark'],
      langs: Object.values(extensionToLanguage),
    }).then(h => {
      highlighterInstance = h;
      return h;
    });
  }

  return highlighterPromise;
}

/**
 * Highlight code with syntax highlighting
 * @param {string} code - The code to highlight
 * @param {string} language - The language identifier
 * @param {string} theme - Theme name (github-light or github-dark)
 * @returns {Promise<string>} HTML string with syntax highlighting
 */
export async function highlightCode(code, language = 'text', theme = 'github-light') {
  try {
    const highlighter = await getHighlighter();

    // Check if language is supported
    const loadedLanguages = highlighter.getLoadedLanguages();
    const lang = loadedLanguages.includes(language) ? language : 'text';

    return highlighter.codeToHtml(code, {
      lang,
      theme,
    });
  } catch (error) {
    console.error('Syntax highlighting error:', error);
    // Fallback to plain text
    return `<pre><code>${escapeHtml(code)}</code></pre>`;
  }
}

/**
 * Get just the token spans without the pre/code wrapper
 * Useful for inline highlighting in diff views
 */
export async function highlightCodeInline(code, language = 'text', theme = 'github-light') {
  try {
    const highlighter = await getHighlighter();

    const loadedLanguages = highlighter.getLoadedLanguages();
    const lang = loadedLanguages.includes(language) ? language : 'text';

    // Get the tokens
    const tokens = highlighter.codeToTokens(code, {
      lang,
      theme,
    });

    // Build HTML from tokens
    let html = '';
    for (const line of tokens.tokens) {
      for (const token of line) {
        const style = token.color ? `color: ${token.color}` : '';
        html += `<span style="${style}">${escapeHtml(token.content)}</span>`;
      }
    }

    return html;
  } catch (error) {
    console.error('Syntax highlighting error:', error);
    return escapeHtml(code);
  }
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}
