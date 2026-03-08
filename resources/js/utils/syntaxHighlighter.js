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
  'erb': 'erb',
  'go': 'go',
  'rs': 'rust',
  'java': 'java',
  'c': 'c',
  'cpp': 'cpp',
  'cs': 'csharp',
  'css': 'css',
  'svelte': 'jsx',
  'svg': 'html',
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

/**
 * Tokenize all lines at once so Shiki maintains grammar state across lines.
 * Returns an array of token arrays (one per input line).
 */
export async function tokenizeAllLines(lines, language, theme) {
  if (!lines || lines.length === 0) return null;

  const highlighter = await getHighlighter();
  const loadedLanguages = highlighter.getLoadedLanguages();
  const lang = loadedLanguages.includes(language) ? language : 'text';

  if (lang === 'text') return null;

  const code = lines.join('\n');
  const result = highlighter.codeToTokens(code, { lang, theme });
  return result.tokens;
}

/**
 * Build HTML from pre-computed tokens for a single line, optionally mapping
 * tokens onto intra-line diff segments so that change highlights and syntax
 * colors are both correct.
 */
export function buildLineHtmlFromTokens(tokens, segments = null, lineType = 'normal', showWhitespace = true) {
  if (!tokens) return null;

  // No segments: simple token → HTML conversion
  if (!segments || segments.length === 0) {
    let html = '';
    for (const token of tokens) {
      const style = token.color ? `color: ${token.color}` : '';
      html += `<span style="${style}">${escapeHtml(token.content)}</span>`;
    }
    return html;
  }

  // Walk tokens and segments simultaneously, splitting tokens at segment boundaries
  let html = '';
  let tokenIndex = 0;
  let tokenOffset = 0;

  for (const seg of segments) {
    let remaining = seg.text.length;
    const isChange = seg.type === 'change';
    const isWhitespaceOnly = /^\s*$/.test(seg.text);
    let segHtml = '';

    while (remaining > 0 && tokenIndex < tokens.length) {
      const token = tokens[tokenIndex];
      const available = token.content.length - tokenOffset;
      const take = Math.min(remaining, available);

      const content = token.content.substring(tokenOffset, tokenOffset + take);
      const style = token.color ? `color: ${token.color}` : '';
      segHtml += `<span style="${style}">${escapeHtml(content)}</span>`;

      remaining -= take;
      tokenOffset += take;

      if (tokenOffset >= token.content.length) {
        tokenIndex++;
        tokenOffset = 0;
      }
    }

    if (isChange && !(isWhitespaceOnly && !showWhitespace)) {
      const isAdd = lineType === 'add';
      const isDel = lineType === 'del';
      const bgColor = isAdd ? 'rgba(34, 197, 94, 0.25)' : isDel ? 'rgba(239, 68, 68, 0.25)' : 'rgba(234, 179, 8, 0.25)';
      html += `<span class="segment-change" style="background-color: ${bgColor}; border-radius: 0.25rem;">${segHtml}</span>`;
    } else {
      html += segHtml;
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
