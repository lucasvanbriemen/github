<script>
  import { onMount } from 'svelte';
  import Sidebar from './Sidebar.svelte';
  import Markdown from './Markdown.svelte';
  import Comment from './Comment.svelte';

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

  onMount(async () => {
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
</script>

<div class="item-overview">
  <!-- SIDEBAR: Assignees, Labels, and Reviewers -->
  <Sidebar {params} selectedDropdownSection="Issues">
    <!-- Assignees Section -->
    <div class="group">
      <span class="group-title">Assignees</span>
      {#each item.assignees as assignee}
        <div class="assignee">
          <img src={assignee.avatar_url} alt={assignee.name} />
          <span>{assignee.name}</span>
        </div>
      {/each}
    </div>

    <!-- Labels Section -->
    <div class="group">
      <span class="group-title">Labels</span>
      <div class="labels">
        {#each item.labels as label}
          <span class="label" style={getLabelStyle(label)}>
            {label.name}
          </span>
        {/each}
      </div>
    </div>

    <!-- Reviewers Section (PR only) -->
    {#if isPR}
      <div class="group">
        <span class="group-title">Reviewers</span>
        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.name}</span>
            <span>{reviewer.state}</span>
          </div>
        {/each}
      </div>
    {/if}
  </Sidebar>

  <!-- MAIN CONTENT: Header, Body, and Comments -->
  <div class="item-main">
    <!-- Item Header: Title and Metadata -->
    <div class="item-header">
      <h2>{item.title}</h2>
      <div>
        created {item.created_at_human} by
        <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
        {item.opened_by?.name}
        <span class="item-state item-state-{item.state}">{item.state}</span>
      </div>
    </div>

    <!-- PR Header: Branch Information (PR only) -->
    {#if isPR}
      <div class="item-header-pr">
        <span class="item-header-pr-title">
          <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
          {item.opened_by?.name} wants to merge
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
            </div>
          {/each}
        {/if}
      </div>
    {/if}
  </div>
</div>

<style>
  .review-block {
    display: flex;
    flex-direction: column;
  }

  .review-header .item-comment-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-color-secondary);
    background-color: var(--background-color-one);
    padding: 1rem;
    border-radius: 1rem 1rem 0 0;
    border: none;
    cursor: pointer;
    font-size: 14px;
    width: 100%;
  }

  .review-header .item-comment-header img {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
  }

  .review-body .item-comment-content {
    border: 2px solid var(--background-color-one);
    border-radius: 0 0 1rem 1rem;
  }

  .review-body :global(.markdown-body) {
    height: auto;
    border: none !important;
  }

  .review-body :global(.markdown-body p),
  .review-body :global(.markdown-body li),
  .review-body :global(.markdown-body strong) {
    color: var(--text-color);
  }

  .review-comments {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .review-resolved .review-header .item-comment-header {
    border-radius: 1rem;
  }

  .review-resolved .review-body,
  .review-resolved .review-comments {
    height: 0;
    overflow: hidden;
  }

  .group {
    border: 1px solid var(--border-color);
    background-color: var(--background-color);
    border-radius: 0.5rem;
    width: calc(95% - 1rem);
    margin: 1rem auto -0.5rem auto;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;

    .group-title {
      font-size: 0.75rem;
      color: var(--text-color-secondary);
    }

    .assignee, .reviewer {
      display: flex;
      align-items: center;
      gap: 0.5rem;

      img {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
      }
    }

    .labels {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;

        .label {
          margin: 0.25rem 0;
          padding: 0.25rem 0.5rem;
          border-radius: 1rem;
          font-size: 0.75rem;
        }
    }
  }

  .edit-button {
    margin-top: 1rem;
    margin-left: 2.5%;
  }

  .item-overview {
    height: 100%;
    width: 100%;
    display: flex;
    gap: 1rem;
    overflow: auto;

    .item-main {
      width: calc(85vw - 3rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;

      .item-header {
        background-color: var(--background-color-one);
        padding: 1rem;
        border-radius: 0.5rem;

        h2 {
          margin: 0;
        }

        .item-state {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: white;
          text-transform: capitalize;
          
          width: fit-content;
          padding: 0.25rem;
          background-color: var(--success-color);
          border-radius: 0.5rem;
          
          &.item-state-closed {
            background-color: var(--error-color);
          }
        }

        div {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-top: 0.5rem;
          color: var(--text-color-secondary);
        }

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }

      .item-header-pr-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-color-secondary);
        margin: 0.5rem 0;

        img {
          width: 1rem;
          height: 1rem;
          border-radius: 50%;
        }
      }

    }
  }

  .tab-navigation {
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    margin-top: 1rem;
    margin-bottom: 1rem;

    .tab-button {
      padding: 0.75rem 1rem;
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--text-color-secondary);
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s;
      margin-bottom: -1px;

      &:hover {
        color: var(--text-color);
        border-bottom-color: var(--border-color);
      }

      &.active {
        color: var(--text-color);
        border-bottom-color: var(--primary-color);
      }
    }
  }

  .pr-files {
    .loading {
      padding: 2rem;
      text-align: center;
      color: var(--text-color-secondary);
    }

    .diff-empty {
      padding: 2rem;
      text-align: center;
      color: var(--text-color-secondary);
      background-color: var(--background-color-one);
      border: 1px solid var(--border-color);
      border-radius: 1rem;
    }

    .diff-file {
      border: 1px solid var(--border-color);
      border-radius: 1rem;
      margin-bottom: 1rem;
      background-color: var(--background-color-one);
      overflow: hidden;
    }

    .diff-file-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background-color: var(--background-color-one);
      border-bottom: 1px solid var(--border-color);
      cursor: pointer;
      width: 100%;
      border: none;
      text-align: left;

      &:hover {
        background-color: var(--background-color-two);
      }
    }

    .diff-file-header-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .diff-file-status {
      padding: 0.25rem 0.5rem;
      border-radius: 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      background-color: var(--background-color-two);
      color: var(--text-color);
    }

    .diff-file-status-added {
      background-color: rgba(34, 197, 94, 0.15);
      color: #22c55e;
    }

    .diff-file-status-deleted {
      background-color: rgba(239, 68, 68, 0.15);
      color: #ef4444;
    }

    .diff-file-status-modified {
      background-color: rgba(234, 179, 8, 0.15);
      color: #eab308;
    }

    .diff-file-status-renamed {
      background-color: rgba(59, 130, 246, 0.15);
      color: #3b82f6;
    }

    .diff-file-name {
      font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--text-color);
    }

    .diff-file-stats {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.875rem;
      font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    }

    .diff-stats-additions {
      color: #22c55e;
      font-weight: 600;
    }

    .diff-stats-deletions {
      color: #ef4444;
      font-weight: 600;
    }

    .diff-table-container {
      background-color: var(--background-color-one);
      overflow-x: auto;
    }

    .diff-table {
      width: 100%;
      border-collapse: collapse;
      font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
      font-size: 0.8125rem;
      line-height: 1.5;
      background-color: var(--background-color-one);

      tr {
        background-color: var(--background-color-one);
      }
    }

    .diff-line-number {
      padding: 0.125rem 0.5rem;
      text-align: right;
      vertical-align: top;
      user-select: none;
      color: var(--text-color-secondary);
      background-color: var(--background-color-two);
      border-right: 1px solid var(--border-color);
      min-width: 50px;
      width: 1%;
      white-space: nowrap;
    }

    .diff-line-number-empty {
      background-color: var(--background-color-two);
      border-right: 1px solid var(--border-color);
    }

    .diff-line-content {
      padding: 0.125rem 0.5rem;
      vertical-align: top;
      white-space: pre-wrap;
      word-wrap: break-word;
      color: var(--text-color);
      background-color: var(--background-color-one);
    }

    .diff-line-prefix {
      user-select: none;
      margin-right: 0.5rem;
      opacity: 0.5;
    }

    .diff-line-code {
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .diff-line-empty {
      background-color: var(--background-color-two);
      opacity: 0.3;
    }

    .diff-line-add {
      background-color: rgba(34, 197, 94, 0.1);

      &.diff-line-number {
        background-color: rgba(34, 197, 94, 0.15);
        border-right-color: rgba(34, 197, 94, 0.3);
      }

      .diff-line-prefix {
        color: #22c55e;
        opacity: 1;
        font-weight: 700;
      }
    }

    .diff-line-del {
      background-color: rgba(239, 68, 68, 0.1);

      &.diff-line-number {
        background-color: rgba(239, 68, 68, 0.15);
        border-right-color: rgba(239, 68, 68, 0.3);
      }

      .diff-line-prefix {
        color: #ef4444;
        opacity: 1;
        font-weight: 700;
      }
    }

    .diff-table-side-by-side {
      td {
        width: 50%;
      }

      .diff-line-number {
        width: 1%;
      }
    }
  }
</style>
