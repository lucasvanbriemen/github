<script>
  import { onMount } from 'svelte';

  let { textarea, users = [] } = $props();

  let isOpen = $state(false);
  let query = $state('');
  let selectedIndex = $state(0);
  let triggerPosition = $state(null);
  let filteredUsers = $derived.by(() => {
    const validUsers = users.filter(user => user != null);
    if (!query) return validUsers;
    const lowerQuery = query.toLowerCase();
    return validUsers.filter(user =>
      user.login.toLowerCase().includes(lowerQuery) ||
      (user.display_name && user.display_name.toLowerCase().includes(lowerQuery))
    );
  });

  $effect(() => {
    console.log('[MentionAutocomplete] Users prop updated:', users.length, 'valid users');
  });

  function findMentionStart(text, cursorPos) {
    // Find the last @ before cursor position
    const atIndex = text.lastIndexOf('@', cursorPos - 1);
    if (atIndex === -1) return null;

    // Check if @ is at start of line or after whitespace
    const beforeAt = atIndex === 0 ? '' : text[atIndex - 1];
    if (atIndex > 0 && beforeAt !== ' ' && beforeAt !== '\n' && beforeAt !== '\t') {
      return null;
    }

    // Extract text between @ and cursor
    const afterAt = text.slice(atIndex + 1, cursorPos);

    // Check if it contains only valid mention characters (alphanumeric, dash, underscore)
    if (!/^[a-zA-Z0-9\-_]*$/.test(afterAt)) {
      return null;
    }

    return { start: atIndex, query: afterAt };
  }

  function calculateDropdownPosition(textarea, atPosition) {
    const textBeforeCursor = textarea.value.slice(0, atPosition + 1);
    const lines = textBeforeCursor.split('\n');
    const currentLine = lines[lines.length - 1];

    const rect = textarea.getBoundingClientRect();
    const style = window.getComputedStyle(textarea);

    // Handle lineHeight - if "normal", use a sensible default (usually 1.2 * fontSize)
    let lineHeight = parseInt(style.lineHeight);
    if (isNaN(lineHeight)) {
      const fontSize = parseInt(style.fontSize);
      lineHeight = fontSize * 1.5; // Default multiplier for "normal" line height
    }

    const paddingLeft = parseInt(style.paddingLeft);
    const paddingTop = parseInt(style.paddingTop);

    // Calculate vertical position (line number * line height)
    const lineNumber = lines.length - 1;
    const top = rect.top + paddingTop + lineNumber * lineHeight + lineHeight;

    // Calculate horizontal position (character width approximation)
    const charWidth = 8; // Approximate for monospace
    const left = rect.left + paddingLeft + currentLine.length * charWidth;

    console.log('[MentionAutocomplete] Position calculated:', {
      textareaTop: rect.top,
      textareaLeft: rect.left,
      lineNumber,
      lineHeight,
      paddingTop,
      paddingLeft,
      top,
      left,
      viewportHeight: window.innerHeight,
      viewportWidth: window.innerWidth
    });

    return { top, left };
  }

  function insertMention(user) {
    console.log('[MentionAutocomplete] Inserting mention:', user.login);
    const textarea_elem = textarea;
    const cursorPos = textarea_elem.selectionStart;
    const text = textarea_elem.value;

    // Find the @ position
    const mention = findMentionStart(text, cursorPos);
    if (!mention) {
      console.log('[MentionAutocomplete] Could not find mention start position');
      return;
    }

    // Replace @query with @login
    const before = text.slice(0, mention.start);
    const after = text.slice(cursorPos);
    const insertText = `@${user.login} `;

    const newValue = before + insertText + after;
    textarea_elem.value = newValue;
    textarea_elem.selectionStart = textarea_elem.selectionEnd = before.length + insertText.length;

    console.log('[MentionAutocomplete] Mention inserted:', { before: before.length, insertText, after: after.length });

    // Trigger input event for Svelte binding
    textarea_elem.dispatchEvent(new Event('input', { bubbles: true }));

    // Close dropdown
    isOpen = false;
    query = '';
    selectedIndex = 0;
  }

  function handleTextareaInput() {
    if (!textarea) return;
    const cursorPos = textarea.selectionStart;
    const text = textarea.value;

    const mention = findMentionStart(text, cursorPos);

    if (mention) {
      console.log('[MentionAutocomplete] @ found:', { query: mention.query, position: mention.start });
      query = mention.query;
      triggerPosition = calculateDropdownPosition(textarea, mention.start);
      isOpen = true;
      selectedIndex = 0;
      console.log('[MentionAutocomplete] Dropdown opened, filtered users:', filteredUsers.length, 'isOpen:', isOpen, 'triggerPosition:', triggerPosition);
    } else {
      isOpen = false;
      query = '';
    }
  }

  function handleKeyDown(e) {
    if (!isOpen || filteredUsers.length === 0) return;

    switch (e.key) {
      case 'ArrowUp':
        e.preventDefault();
        selectedIndex = (selectedIndex - 1 + filteredUsers.length) % filteredUsers.length;
        break;
      case 'ArrowDown':
        e.preventDefault();
        selectedIndex = (selectedIndex + 1) % filteredUsers.length;
        break;
      case 'Enter':
        e.preventDefault();
        insertMention(filteredUsers[selectedIndex]);
        break;
      case 'Escape':
        e.preventDefault();
        isOpen = false;
        query = '';
        break;
    }
  }

  function handleClickOutside(e) {
    if (textarea && !textarea.contains(e.target)) {
      isOpen = false;
    }
  }

  onMount(() => {
    if (!textarea) {
      console.log('[MentionAutocomplete] No textarea provided');
      return;
    }

    console.log('[MentionAutocomplete] Mounted, users available:', users.length);
    textarea.addEventListener('input', handleTextareaInput);
    textarea.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleClickOutside);

    return () => {
      textarea.removeEventListener('input', handleTextareaInput);
      textarea.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('click', handleClickOutside);
    };
  });
</script>

{#if isOpen && triggerPosition && filteredUsers.length > 0}
  <div
    class="mention-dropdown"
    style="top: {triggerPosition.top}px; left: {triggerPosition.left}px;"
  >
    {#each filteredUsers as user, index}
      {#if user != null}
        <button
          class="mention-item"
          class:selected={index === selectedIndex}
          onclick={() => insertMention(user)}
          type="button"
        >
          <img src={user.avatar_url} alt={user.login} class="mention-avatar" />
          <div class="mention-content">
            <div class="mention-name">{user.display_name || user.login}</div>
            <div class="mention-login">@{user.login}</div>
          </div>
        </button>
      {/if}
    {/each}
  </div>
{/if}

<style>
  @import '../../scss/components/mention-autocomplete.scss';
</style>
