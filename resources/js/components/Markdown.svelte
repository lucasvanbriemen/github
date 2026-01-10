<script>
  import { onMount, untrack } from 'svelte';
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  let { content = $bindable(''), canEdit = true, isEditing = false, change } = $props();
  let rendered = $state('');

  let editor = $state(null);
  let improvedText = $state(null);
  let isImproving = $state(false);

  const shortcutMap = {
    heading: {
      title: "Heading",
      key: 'heading',
      placement: 'line_start',
      content: '# ',
    },

    bold: {
      title: "Bold",
      key: 'bold',
      placement: 'around_selection',
      content: '**',
    },

    italic: {
      title: "Italic",
      key: 'italic',
      placement: 'around_selection',
      content: '_',
    },

    qoute: {
      title: "Quote",
      key: 'qoute',
      placement: 'line_start',
      content: '> ',
    },

    code: {
      title: "Code",
      key: 'code',
      placement: 'around_selection',
      content: '`',
    },
  };

  const renderer = new marked.Renderer();
  let checkboxRenderIndex = $state(0);

  async function uploadFiles(files) {
    const form = new FormData();
    for (const file of files) {
      form.append('files[]', file, file.name);
    }

    const res = await fetch('/api/uploads', {
      method: 'POST',
      body: form,
      headers: {
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
    });

    const data = await res.json();
    return data.files ?? [];
  }

  async function handlePaste(e) {
    const items = e.clipboardData?.items || [];
    const files = [];

    for (const item of items) {
      if (item.kind === 'file') {
        const file = item.getAsFile();
        if (file && (file.type.startsWith('image/') || file.type.startsWith('video/'))) {
          files.push(file);
        }
      }
    }

    if (files.length === 0) {
      return; // allow normal paste
    }

    e.preventDefault();

    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const before = editor.value.slice(0, start);
    const after = editor.value.slice(end);

    // Upload files and get back URLs
    const uploaded = await uploadFiles(files);

    const parts = [];
    for (const item of uploaded) {
      const safeName = item.name || 'uploaded-file';
      if (item.type?.startsWith('image/')) {
        parts.push(`\n<img src="${item.url}" alt="${safeName}" style="max-width: 100%; height: auto;" /><br/>\n`);
      } else if (item.type?.startsWith('video/')) {
        parts.push(`\n<video controls src="${item.url}" style="max-width: 100%; height: auto;"></video><br/>\n`);
      }
    }

    if (parts.length === 0) return;

    const insertText = parts.join('\n');
    const updatedValue = `${before}${insertText}${after}`;
    editor.value = updatedValue;
    content = updatedValue;

    const newCursor = before.length + insertText.length;
    editor.selectionStart = editor.selectionEnd = newCursor;
    editor.focus();

    requestAnimationFrame(() => autoSize());
  }

  function autoSize() {
    editor.style.height = editor.scrollHeight + 'px';
  }

  function saveChange() {
    change?.({ value: content });
  }

  function handleCheckboxClick(e) {
    const target = e.target;

    if (target.type !== 'checkbox') {
      return;
    }

    // Find which checkbox was clicked by matching against all checkboxes in the DOM
    const checkboxes = document.querySelectorAll('.markdown-body input[type="checkbox"]');
    let clickedIndex = -1;

    checkboxes.forEach((checkbox, index) => {
      if (checkbox === target) {
        clickedIndex = index;
      }
    });

    const lines = content.split('\n');
    let currentCheckbox = 0;

    lines.forEach((line, index) => {
      const hasCheckbox = line.includes('- [ ]') || line.includes('- [x]') || line.includes('- [X]');

      if (hasCheckbox) {
        if (currentCheckbox === clickedIndex) {
          lines[index] = line.replace(/- \[[ xX]\]/, `- [${target.checked ? 'x' : ' '}]`);
        }
        currentCheckbox++;
      }
    });

    content = lines.join('\n');
    saveChange();
  }

  function handleKeyDown(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
      e.preventDefault();
      isEditing = false;
      saveChange();
    }

    if (e.key === 'Escape') {
      e.preventDefault();
      isEditing = false;
    }
  }

  function convertToMarkdown() {
    if (!content) {
      return '';
    }

    checkboxRenderIndex = 0;
    return marked.parse(content);
  }

  function insertShortcut(type) {
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const value = editor.value;

    const lineStart = value.lastIndexOf('\n', start - 1) + 1;

    const shortcut = shortcutMap[type];

    let updatedValue = editor.value;
    let cursorOffset = shortcut.content.length;

    if (shortcut.placement === 'line_start') {
      updatedValue = value.slice(0, lineStart) + shortcut.content + value.slice(lineStart);
    }

    if (shortcut.placement === 'around_selection') {
      const before = value.slice(0, start);
      const selectedText = value.slice(start, end);
      const after = value.slice(end);

      updatedValue = `${before} ${shortcut.content}${selectedText}${shortcut.content} ${after}`;
      cursorOffset = shortcut.content.length + 1;
    }

    editor.value = updatedValue;
    editor.selectionStart = start + cursorOffset;
    editor.selectionEnd = end + cursorOffset;
    content = updatedValue;

    editor.focus();
  }

  async function improveComment() {
    isImproving = true;
    const data = await api.post(route('comment.improve'), { text: content });
    improvedText = data.improved;
    isImproving = false;
  }

  function acceptImprovement() {
    content = improvedText;
    improvedText = null;
    editor?.focus();
  }

  function rejectImprovement() {
    improvedText = null;
  }

  onMount(() => {
    renderer.checkbox = function (data) {
      const isChecked = data.checked;
      const currentIndex = checkboxRenderIndex;
      checkboxRenderIndex++;

      return `<input type="checkbox" data-index="${currentIndex}" ${isChecked ? ' checked' : ''}> `;
    };

    marked.setOptions({renderer, gfm: true, breaks: false});

    rendered = convertToMarkdown();
  });

  $effect(() => {
    void content;
    void isEditing;

    untrack(() => {
      rendered = convertToMarkdown();

      if (isEditing) {
        autoSize();
        editor.focus();
      }
    });
  });
</script>

<div class="markdown-container" class:can-edit={canEdit}>
  {#if canEdit}
    <header>
      <nav class="markdown-nav">
        <button class="preview-button button-primary-outline" onclick={() => { isEditing = false; saveChange(); }}>Preview</button>
        <button class="edit-button button-primary-outline" onclick={() => isEditing = true}>Edit</button>
      </nav>

      {#if isEditing}
        <div class="markdown-shortcuts">
          {#each Object.entries(shortcutMap) as [key, shortcut]}
            <button class="markdown-shortcut button-primary-outline" onclick={() => insertShortcut(shortcut.key)}>{shortcut.title}</button>
          {/each}
          <button class="markdown-shortcut button-primary-outline" onclick={improveComment} disabled={isImproving || !content.trim()} >{isImproving ? '✨ Improving...' : '✨ Improve'}</button>
        </div>
      {/if}
    </header>
  {/if}

  {#if improvedText}
    <div class="improvement-panel">
      <div class="improvement-header">AI Suggestion</div>
      <div class="improvement-comparison">
        <div class="original">
          <strong>Original:</strong>
          <div class="text">{content}</div>
        </div>
        <div class="improved">
          <strong>Improved:</strong>
          <div class="text">{improvedText}</div>
        </div>
      </div>
      <div class="improvement-actions">
        <button class="accept-button button-primary-outline" onclick={acceptImprovement}>Accept</button>
        <button class="reject-button button-primary-outline" onclick={rejectImprovement}>Reject</button>
      </div>
    </div>
  {/if}

  {#if isEditing && canEdit}
    <textarea
      class="markdown-editor"
      placeholder="Markdown content"
      bind:value={content}
      oninput={autoSize}
      onpaste={handlePaste}
      onkeydown={handleKeyDown}
      bind:this={editor}
    ></textarea>
  {:else}
    <div class="markdown-body" onclick={handleCheckboxClick}>
      {#if content}
        {@html rendered}
      {:else}
        <p class="empty-state">No content</p>
      {/if}
    </div>
  {/if}
</div>

<style>
  @import '../../scss/components/markdown.scss';
</style>
