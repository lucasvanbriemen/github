<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

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

  function ready_for_review() {
    api.post(route(`organizations.repositories.pr.update`, { organization, repository, number }), {
      draft: false,
    });

    item.state = 'open';
  }

</script>

{#if item.state === 'draft'}
  <button class="button-primary ready-for-review" onclick={ready_for_review}>Ready for Review</button>
{/if}

<Markdown content={body} change={save_body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}

<Markdown bind:content={issueComment} isEditing={true} />
<button class="button-primary" onclick={post_comment}>Post Comment</button>
