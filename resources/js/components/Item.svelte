<script>
  import { onMount } from 'svelte';
  import Sidebar from './sidebar/Sidebar.svelte';
  import SidebarGroup from './sidebar/group.svelte';
  import Markdown from './Markdown.svelte';
  import Comment from './Comment.svelte';
  import ItemSkeleton from './ItemSkeleton.svelte';

  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
  let repository = $derived(params.repository || '');
  let number = $derived(params.number || '');

  let item = $state({});
  let isPR = $state(false);
  let files = $state([]);
  let loadingFiles = $state(false);
  let collapsedFiles = $state(new Set());
  let activeTab = $state('conversation'); // 'conversation' or 'files'
  let isLoading = $state(true);

  onMount(async () => {
    isLoading = true;
    const res = await fetch(route(`organizations.repositories.item.show`, { organization, repository, number }));
    item = await res.json();

    try {
      item.labels = JSON.parse(item.labels);
    } catch (e) {
      item.labels = [];
    }

    isPR = item.type === 'pull_request';

    // If it's a PR, load the file diffs
    if (isPR) {
      loadFiles();
    }

    isLoading = false;
  });

  async function loadFiles() {
    loadingFiles = true;
    try {
      const res = await fetch(route(`organizations.repositories.item.files`, { organization, repository, number }));
      const data = await res.json();
      files = data.files || [];
    } catch (e) {
      console.error('Failed to load files:', e);
      files = [];
    } finally {
      loadingFiles = false;
    }
  }

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  // Toggle functions for different comment types
  function toggleItemComment(comment) {
    comment.resolved = !comment.resolved;

    fetch(route(`organizations.repositories.item.comment`, { organization, repository, number, comment_id: comment.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: comment.resolved,
      }),
    });
  }

  function toggleItemReview(review) {
    review.resolved = !review.resolved;

    fetch(route(`organizations.repositories.item.review`, { organization, repository, number, review_id: review.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: review.resolved,
      }),
    });
  }

  function toggleItemReviewComment(comment) {
    comment.resolved = !comment.resolved;

    fetch(route(`organizations.repositories.item.review.comment`, { organization, repository, number, comment_id: comment.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        resolved: comment.resolved,
      }),
    });
  }

  // Diff view helper functions
  function toggleFile(fileName) {
    if (collapsedFiles.has(fileName)) {
      collapsedFiles.delete(fileName);
    } else {
      collapsedFiles.add(fileName);
    }
    collapsedFiles = new Set(collapsedFiles);
  }

  function getFileStatus(file) {
    if (file.from === '/dev/null') return 'added';
    if (file.to === '/dev/null') return 'deleted';
    if (file.from !== file.to) return 'renamed';
    return 'modified';
  }

  function getFileName(file) {
    return file.to === '/dev/null' ? file.from : file.to;
  }

  function processChunk(chunk) {
    const lines = [];
    let leftIndex = chunk.oldStart;
    let rightIndex = chunk.newStart;

    for (const change of chunk.changes) {
      if (change.type === 'normal') {
        lines.push({
          left: { lineNumber: leftIndex++, content: change.content, type: 'normal' },
          right: { lineNumber: rightIndex++, content: change.content, type: 'normal' }
        });
      } else if (change.type === 'del') {
        lines.push({
          left: { lineNumber: leftIndex++, content: change.content, type: 'del' },
          right: { lineNumber: null, content: '', type: 'empty' }
        });
      } else if (change.type === 'add') {
        const prevLine = lines.length > 0 ? lines[lines.length - 1] : null;
        if (prevLine && prevLine.right.type === 'empty' && prevLine.left.type === 'del') {
          lines[lines.length - 1].right = { lineNumber: rightIndex++, content: change.content, type: 'add' };
        } else {
          lines.push({
            left: { lineNumber: null, content: '', type: 'empty' },
            right: { lineNumber: rightIndex++, content: change.content, type: 'add' }
          });
        }
      }
    }

    return lines;
  }

  function getLinePrefix(type) {
    return type === 'add' ? '+' : (type === 'del' ? '-' : ' ');
  }

  // Check if file is too large to render (similar to GitHub)
  const MAX_DIFF_LINES = 400;
  function isFileTooLarge(file) {
    const totalLines = (file.additions ?? 0) + (file.deletions ?? 0);
    return totalLines > MAX_DIFF_LINES;
  }
</script>

<div class="item-overview">
  <!-- SIDEBAR: Assignees, Labels, and Reviewers -->
  <Sidebar {params} selectedDropdownSection="Issues">
    {#if !isLoading}
      <SidebarGroup title="Assignees">
        {#each item.assignees as assignee}
          <div class="assignee">
            <img src={assignee.avatar_url} alt={assignee.name} />
            <span>{assignee.display_name}</span>
          </div>
        {/each}
      </SidebarGroup>

      <SidebarGroup title="Labels">
        <div class="labels">
          {#each item.labels as label}
            <span class="label" style={getLabelStyle(label)}>
              {label.name}
            </span>
          {/each}
        </div>
      </SidebarGroup>

      {#if isPR}
        <SidebarGroup title="Reviewers">
          {#each item.requested_reviewers as reviewer}
            <div class="reviewer">
              <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
              <span>{reviewer.user.display_name}</span>
              <span>{reviewer.state}</span>
            </div>
          {/each}
        </SidebarGroup>
      {/if}
    {/if}
  </Sidebar>

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main">
    {#if isLoading}
      <ItemSkeleton />
    {:else}
      <!-- Item Header: Title and Metadata -->
      <div class="item-header">
        <h2>{item.title}</h2>
        <div>
          created {item.created_at_human} by
          <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
          {item.opened_by?.display_name}
          <span class="item-state item-state-{item.state}">{item.state}</span>
        </div>
      </div>

    <!-- PR Header: Branch Information (PR only) -->
    {#if isPR}
      <div class="item-header-pr">
        <span class="item-header-pr-title">
          <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
          {item.opened_by?.display_name} wants to merge
          {item.details.head_branch} into {item.details.base_branch}
        </span>
      </div>

      <!-- Tab Navigation -->
      <div class="tab-navigation">
        <button
          class="tab-button"
          class:active={activeTab === 'conversation'}
          onclick={() => activeTab = 'conversation'}
        >
          Conversation
        </button>
        <button
          class="tab-button"
          class:active={activeTab === 'files'}
          onclick={() => activeTab = 'files'}
        >
          Files changed
        </button>
      </div>
    {/if}

    <!-- Conversation Tab Content -->
    {#if !isPR || activeTab === 'conversation'}
      <!-- Item Body: Main Description -->
      <div class="item-body">
        <Markdown content={item.body} />
      </div>

      <!-- Regular Comments -->
      {#each item.comments as comment}
        <Comment {comment} onToggle={toggleItemComment} />
      {/each}

      <!-- PR Reviews and Review Comments (PR only) -->
      {#if isPR}
        {#each item.pull_request_reviews as review}
          <!-- Only render if review has a body or comments -->
          {#if (review.body !== null && review.body !== '') || (review.comments && review.comments.length > 0)}
            <div class="review-block" class:review-resolved={review.resolved}>
              <!-- Review Summary (shown if review has a body) -->
              {#if review.body !== null && review.body !== ''}
                <div class="review-header">
                  <button class="item-comment-header" onclick={() => toggleItemReview(review)}>
                    <img src={review.user?.avatar_url} alt={review.user?.name} />
                    <span>{review.user?.name} {review.created_at_human} (review)</span>
                  </button>
                </div>
                <div class="review-body">
                  <div class="item-comment-content">
                    <Markdown content={review.body} />
                  </div>
                </div>
              {/if}

              <!-- Review Line Comments with Replies -->
              <div class="review-comments">
                {#each review.comments as comment}
                  <Comment
                    comment={comment}
                    onToggle={toggleItemReviewComment}
                    onToggleReply={toggleItemReviewComment}
                    indent={review.body !== null && review.body !== ''}
                    showReplies={true}
                  />
                {/each}
              </div>
            </div>
          {/if}
        {/each}
      {/if}
    {/if}

    <!-- Files Changed Tab Content (PR only) -->
    {#if isPR && activeTab === 'files'}
      <div class="pr-files">
        {#if loadingFiles}
          <div class="loading">Loading files...</div>
        {:else if files.length === 0}
          <div class="diff-empty">No changes</div>
        {:else}
          {#each files as file}
            {@const fileName = getFileName(file)}
            {@const fileStatus = getFileStatus(file)}
            {@const isCollapsed = collapsedFiles.has(fileName)}

            <div class="diff-file">
              <!-- File Header -->
              <button class="diff-file-header" onclick={() => toggleFile(fileName)}>
                <div class="diff-file-header-left">
                  <span class="diff-file-status diff-file-status-{fileStatus}">{fileStatus}</span>
                  <span class="diff-file-name">{fileName}</span>
                </div>
                <div class="diff-file-stats">
                  <span class="diff-stats-additions">+{file.additions ?? 0}</span>
                  <span class="diff-stats-deletions">-{file.deletions ?? 0}</span>
                </div>
              </button>

              <!-- Diff Content -->
              {#if !isCollapsed}
                {#if isFileTooLarge(file)}
                  <div class="diff-too-large">
                    Large diffs are not rendered.
                  </div>
                {:else}
                  <div class="diff-table-container">
                    <table class="diff-table diff-table-side-by-side">
                      <tbody>
                        {#each file.chunks as chunk}
                          {@const lines = processChunk(chunk)}

                          {#each lines as linePair}
                            <tr class="diff-line-row">
                              <!-- Left side -->
                              {#if !linePair.left || linePair.left.type === 'empty'}
                                <td class="diff-line-number diff-line-number-empty"></td>
                                <td class="diff-line-content diff-line-empty"></td>
                              {:else}
                                {@const line = linePair.left}
                                {@const typeClass = line.type === 'normal' ? '' : `diff-line-${line.type}`}
                                {@const prefix = getLinePrefix(line.type)}

                                <td class="diff-line-number {typeClass}">
                                  {line.lineNumber}
                                </td>
                                <td class="diff-line-content {typeClass}">
                                  <span class="diff-line-prefix">{prefix}</span>
                                  <span class="diff-line-code">{line.content}</span>
                                </td>
                              {/if}

                              <!-- Right side -->
                              {#if !linePair.right || linePair.right.type === 'empty'}
                                <td class="diff-line-number diff-line-number-empty"></td>
                                <td class="diff-line-content diff-line-empty"></td>
                              {:else}
                                {@const line = linePair.right}
                                {@const typeClass = line.type === 'normal' ? '' : `diff-line-${line.type}`}
                                {@const prefix = getLinePrefix(line.type)}

                                <td class="diff-line-number {typeClass}">
                                  {line.lineNumber}
                                </td>
                                <td class="diff-line-content {typeClass}">
                                  <span class="diff-line-prefix">{prefix}</span>
                                  <span class="diff-line-code">{line.content}</span>
                                </td>
                              {/if}
                            </tr>
                          {/each}
                        {/each}
                      </tbody>
                    </table>
                  </div>
                {/if}
              {/if}
            </div>
          {/each}
        {/if}
      </div>
    {/if}
    {/if}
  </div>
</div>

<style lang="scss">
  @import '../../scss/components/item.scss';
</style>
