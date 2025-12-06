<script>
  import { onMount, untrack } from 'svelte';
  import { marked } from 'marked';
  import 'github-markdown-css/github-markdown-dark.css';

  let { content = '', canEdit = true, change } = $props();
  let rendered = $state('');
  let isEditing = $state(false);

  let editor = $state(null);

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

  function autoSize() {
    editor.style.height = editor.scrollHeight + 'px';
  }

  function saveChange() {
    change?.({ value: content });
  }

  function convertToMarkdown() {
    if (content) {
      return marked.parse(content);
    }

    return '';
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

  onMount(() => {
    rendered = convertToMarkdown();
  });

  $effect(() => {
    void content;
    void isEditing;

    untrack(() => {
      rendered = convertToMarkdown();

      if (isEditing) {
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
        </div>
      {/if}
    </header>
  {/if}

  {#if isEditing && canEdit}
    <textarea
      class="markdown-editor"
      placeholder="Markdown content"
      bind:value={content}
      oninput={autoSize}
      bind:this={editor}
    ></textarea>
  {:else}
    <div class="markdown-body">
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
