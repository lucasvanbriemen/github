<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';
  import MergePanel from './pr/MergePanel.svelte';

  let { item, params = {} } = $props();
  let body = $state(item.body);
  let issueComment = $state('');

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  function save_body(e) {
    body = e.value;

    api.post(route(`organizations.repositories.item.update`, { organization, repository, number }), {
      body,
    });
  }

  function post_comment(e) {
    api.post(route(`organizations.repositories.item.comment.create`, { organization, repository, number }), {
      body: issueComment,
    }).then((newComment) => {
      untrack(() => {
        item.comments.push(newComment);
        issueComment = '';
      });
    });
  }
</script>

<Markdown content={body} change={save_body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}

<MergePanel {params} {item} />

<Markdown bind:content={issueComment} isEditing={true} />
<button class="button-primary-outline" onclick={post_comment}>Post Comment</button>
