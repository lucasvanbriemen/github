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
  'svelte': 'jsx',
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


export function detectLanguage(filename) {
  if (!filename) return 'text';

  const file_extension = filename.split('.').pop()?.toLowerCase();
  return extensionToLanguage[file_extension] || 'text';
}

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

export async function highlightCodeInline(code, language = 'text', theme = 'github-light') {
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
