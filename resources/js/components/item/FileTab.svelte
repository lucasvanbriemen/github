<script>
  import { onMount } from 'svelte';
  import HighlightedDiffLine from '../HighlightedDiffLine.svelte';
  import { detectLanguage } from '../../utils/syntaxHighlighter.js';
  import Comment from '../Comment.svelte';

  let { files = [], item = {}, loadingFiles = false } = $props();

  function prefix(type) {
    if (type === 'add') return '+';
    if (type === 'del') return '-';
    return ' ';
  }

  let comments = $state([]);
  let fileLanguages = [];


  onMount(async () => {
    files.forEach((file) => {
      fileLanguages[file.filename] = detectLanguage(file.filename);
    });

    // We need the item comments from the reivews and the standard comments
    comments =  comments = item.pull_request_reviews
      .filter(review => review.body !== null)
      .map(review => review.child_comments)
      .flat();

    comments = comments.concat(item.pull_request_comments);

    // Remove the diff_hunks from the comments
    comments.forEach(comment => {
      delete comment.diff_hunk;
    });

    console.log(comments);
  });
</script>

<div class="pr-files">
  {#if loadingFiles}
    <div class="loading">Loading files...</div>
  {:else}
    {#if !files || files.length === 0}
      <div class="diff-empty">No file changes</div>
    {:else}
      {#each files as file}
        <div class="file">
          <button class="header" type="button">
            <span class="file-status file-status-{file.status}">{file.status}</span>
            <span class="file-name">{file.filename}</span>
          </button>

          <div class="file-changes">
            {#each file.changes as hunk}
              {#each (hunk.rows || []) as changedLinePair}
                <div class="changed-line-pair">
                  <div class="side-wrapper">
                    <div class="side left-side">
                      <span class="line-number diff-line-{changedLinePair.left.type}">{changedLinePair.left.number}</span>
                      <div class="diff-line-content diff-line-{changedLinePair.left.type}">
                        {#if changedLinePair.left.type !== 'empty'}
                          <span class="prefix">{prefix(changedLinePair.left.type)}</span>
                          <HighlightedDiffLine code={changedLinePair.left.content} language={fileLanguages[file.filename]} />
                        {/if}
                      </div>
                    </div>

                    {#each comments as comment}
                      {#if comment.path === file.filename && comment.line_end === changedLinePair.left.number && comment.side === 'LEFT'}
                        <Comment {comment} />
                      {/if}
                    {/each}
                  </div>


                  <div class="side-wrapper">
                    <div class="side right-side">
                      <span class="line-number diff-line-{changedLinePair.right.type}">{changedLinePair.right.number}</span>
                      <div class="diff-line-content diff-line-{changedLinePair.right.type}">
                        {#if changedLinePair.right.type !== 'empty'}
                          <span class="prefix">{prefix(changedLinePair.right.type)}</span>
                          <HighlightedDiffLine code={changedLinePair.right.content} language={fileLanguages[file.filename]} />
                        {/if}
                      </div>
                    </div>

                    {#each comments as comment}
                      {#if comment.path === file.filename && comment.line_end === changedLinePair.right.number && comment.side === 'RIGHT'}
                        <Comment {comment} />
                      {/if}
                    {/each}
                  </div>
                </div>
              {/each}

              <div class="hunk-separator"></div>
            {/each}
          </div>
        </div>
      {/each}
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../../scss/components/item/filetab';
</style>
