<x-modal id="new-issue-modal">
  <x-slot name="title">Create Issue</x-slot>

  <x-compoment
      name="input"
      :options="[
        'type' => 'text',
        'value' => '',
        'id' => 'issue-title',
        'label' => 'Title',
      ]"
    />

  <x-markdown-editor id="issue-body" label="Leave a comment" />

  <x-slot name="footer">
    <button class="button-primary" id="submit-new-issue">Submit new issue</button>
  </x-slot>
</x-modal>