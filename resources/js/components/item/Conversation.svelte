<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();
  let body = $state(item.body);

  let organization = params.organization;
  let repository = params.repository;
  let number = params.number;

  function save_body(e) {
    body = e.value;
    console.log('saving body', body);

    api.post(route(`organizations.repositories.item.update`, { organization, repository, number }), {
      body,
    });
  }

</script>

<Markdown content={body} change={save_body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}
