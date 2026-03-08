<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';
  import MergePanel from './pr/MergePanel.svelte';
  import ClosedPanel from './ClosedPanel.svelte';
  import { organization, repository } from '../stores';

  let { item, params = {} } = $props();
  let body = $state(item.body);
  let issueComment = $state('');

  let number = params.number;

  function save_body(e) {
    body = e.value;

    api.post(route(`organizations.repositories.item.update`, { $organization, $repository, number }), {
      body,
    }).then((updatedItem) => {
      item = updatedItem;
    }).catch((error) => {
      console.error('Failed to save issue body:', error);
    });
  }

  function post_comment(e) {
    api.post(route(`organizations.repositories.item.comment.create`, { $organization, $repository, number }), {
      body: issueComment,
    }).then((newComment) => {
      untrack(() => {
        if (item.comments) {
          item.comments.push(newComment);
        }
        issueComment = '';
      });
    });
  }
</script>

<Markdown content={body} change={save_body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}

{#if item.type === 'pull_request'}
  <MergePanel {item} />
{:else}
  <ClosedPanel {item} />
{/if}

<Markdown bind:content={issueComment} isEditing={true} />
<button class="button-primary-outline" onclick={post_comment}>Post Comment</button>
