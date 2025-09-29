import 'highlight.js/styles/github.css'; // pick your theme

import hljs from 'highlight.js';

function highlightDiff() {
  document.querySelectorAll('.diff-file').forEach(fileElement => {
    fileElement.querySelectorAll('.diff-line-code').forEach(codeElement => {
      const content = codeElement.textContent;
      try {
        const highlighted = hljs.highlightAuto(content);
        codeElement.innerHTML = highlighted.value;
      } catch (e) {
        console.warn('Syntax highlighting failed:', e);
      }
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', highlightDiff);
} else {
  highlightDiff();
}

export { highlightDiff };
