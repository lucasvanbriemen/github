export default {
  editors: new Map(),

  init() {
    const editors = document.querySelectorAll('.markdown-editor');
    editors.forEach(editor => {
      const editorId = editor.getAttribute('data-editor-id');
      const textarea = editor.querySelector('.markdown-textarea');
      const previewDiv = editor.querySelector('.markdown-preview');
      const tabButtons = editor.querySelectorAll('.tab-button');
      const tabContents = editor.querySelectorAll('.tab-content');

      // Store editor reference
      this.editors.set(editorId, {
        editor,
        textarea,
        previewDiv,
        tabButtons,
        tabContents
      });

      // Set up tab switching
      tabButtons.forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const tab = button.getAttribute('data-tab');
          this.switchTab(editorId, tab);
        });
      });

      // Set up toolbar buttons
      const toolbarButtons = editor.querySelectorAll('.toolbar-btn');
      toolbarButtons.forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const action = button.getAttribute('data-action');
          this.applyFormat(editorId, action);
        });
      });

      // Auto-resize textarea
      textarea.addEventListener('input', () => {
        this.autoResize(textarea);
      });

      // Initial resize
      this.autoResize(textarea);
    });
  },

  switchTab(editorId, tab) {
    const editorData = this.editors.get(editorId);
    if (!editorData) return;

    const { tabButtons, tabContents, textarea, previewDiv } = editorData;

    // Update active states
    tabButtons.forEach(button => {
      button.classList.toggle('active', button.getAttribute('data-tab') === tab);
    });

    tabContents.forEach(content => {
      content.classList.toggle('active', content.getAttribute('data-tab') === tab);
    });

    // If switching to preview, render markdown
    if (tab === 'preview') {
      this.renderPreview(editorId);
    }
  },

  autoResize(textarea) {
    textarea.style.height = 'auto';
    const scrollHeight = textarea.scrollHeight;
    const minHeight = 15 * 16; // 15rem in pixels
    textarea.style.height = Math.max(scrollHeight, minHeight) + 'px';
  },

  getValue(editorId) {
    const editorData = this.editors.get(editorId);
    return editorData ? editorData.textarea.value : '';
  },

  setValue(editorId, value) {
    const editorData = this.editors.get(editorId);
    if (editorData) {
      editorData.textarea.value = value;
      this.autoResize(editorData.textarea);
    }
  },

  getTextarea(editorId) {
    const editorData = this.editors.get(editorId);
    return editorData ? editorData.textarea : null;
  },

  applyFormat(editorId, action) {
    const editorData = this.editors.get(editorId);
    if (!editorData) return;

    const textarea = editorData.textarea;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const beforeText = textarea.value.substring(0, start);
    const afterText = textarea.value.substring(end);

    let newText = '';
    let cursorOffset = 0;

    switch(action) {
      case 'bold':
        if (selectedText) {
          newText = `**${selectedText}**`;
          cursorOffset = newText.length;
        } else {
          newText = '****';
          cursorOffset = 2; // Position cursor inside
        }
        break;

      case 'italic':
        if (selectedText) {
          newText = `*${selectedText}*`;
          cursorOffset = newText.length;
        } else {
          newText = '**';
          cursorOffset = 1;
        }
        break;

      case 'heading':
        const lineStart = beforeText.lastIndexOf('\n') + 1;
        const line = textarea.value.substring(lineStart, end);
        const headingMatch = line.match(/^(#{1,6})\s/);

        if (headingMatch) {
          // Cycle through heading levels or remove
          const currentLevel = headingMatch[1].length;
          if (currentLevel < 6) {
            newText = '#'.repeat(currentLevel + 1) + ' ' + line.substring(headingMatch[0].length);
          } else {
            newText = line.substring(headingMatch[0].length);
          }
        } else {
          newText = '# ' + line;
        }

        textarea.value = textarea.value.substring(0, lineStart) + newText + afterText;
        textarea.selectionStart = textarea.selectionEnd = lineStart + newText.length;
        this.autoResize(textarea);
        return;

      case 'link':
        if (selectedText) {
          const url = prompt('Enter URL:', 'https://');
          if (url) {
            newText = `[${selectedText}](${url})`;
            cursorOffset = newText.length;
          } else {
            return;
          }
        } else {
          const url = prompt('Enter URL:', 'https://');
          if (url) {
            newText = `[link text](${url})`;
            cursorOffset = 1; // Position cursor at "link text"
          } else {
            return;
          }
        }
        break;

      case 'code':
        if (selectedText.includes('\n')) {
          newText = `\`\`\`\n${selectedText}\n\`\`\``;
          cursorOffset = newText.length;
        } else if (selectedText) {
          newText = `\`${selectedText}\``;
          cursorOffset = newText.length;
        } else {
          newText = '``';
          cursorOffset = 1;
        }
        break;

      case 'quote':
        const lines = (selectedText || '').split('\n');
        newText = lines.map(line => `> ${line}`).join('\n');
        cursorOffset = newText.length;
        break;

      case 'ul':
        this.insertList(textarea, start, end, '- ');
        return;

      case 'ol':
        this.insertList(textarea, start, end, '1. ', true);
        return;

      case 'task':
        this.insertList(textarea, start, end, '- [ ] ');
        return;

      default:
        return;
    }

    textarea.value = beforeText + newText + afterText;
    textarea.selectionStart = textarea.selectionEnd = start + cursorOffset;
    textarea.focus();
    this.autoResize(textarea);
  },

  insertList(textarea, start, end, prefix, numbered = false) {
    const selectedText = textarea.value.substring(start, end);
    const beforeText = textarea.value.substring(0, start);
    const afterText = textarea.value.substring(end);

    let newText;
    if (selectedText) {
      const lines = selectedText.split('\n');
      newText = lines.map((line, i) => {
        if (numbered) {
          return `${i + 1}. ${line}`;
        }
        return `${prefix}${line}`;
      }).join('\n');
    } else {
      newText = prefix + 'List item';
    }

    textarea.value = beforeText + newText + afterText;
    textarea.selectionStart = textarea.selectionEnd = start + newText.length;
    textarea.focus();
    this.autoResize(textarea);
  }
};
