@props([
  'name' => 'markdown-editor',
  'id' => null,
  'label' => null,
  'value' => '',
  'placeholder' => 'Write your markdown here...',
  'wrapperOptions' => []
])

@php
  $editorId = $id ?? 'markdown-editor-' . uniqid();
  $wrapperAttributes = '';
  foreach ($wrapperOptions as $key => $val) {
    $wrapperAttributes .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
  }
@endphp

<div class="markdown-editor-wrapper" {!! $wrapperAttributes !!}>
  <div class="markdown-editor" data-editor-id="{{ $editorId }}">
    <div class="markdown-editor-content">
      <div class="tab-content active" data-tab="write">
        <div class="markdown-toolbar">
          <button type="button" class="toolbar-btn" data-action="bold" title="Bold (Ctrl+B)">
            <strong>B</strong>
          </button>
          <button type="button" class="toolbar-btn" data-action="italic" title="Italic (Ctrl+I)">
            <em>I</em>
          </button>
          <span class="toolbar-separator"></span>
          <button type="button" class="toolbar-btn" data-action="heading" title="Heading">
            H
          </button>
          <span class="toolbar-separator"></span>
          <button type="button" class="toolbar-btn" data-action="link" title="Insert Link (Ctrl+K)">
            🔗
          </button>
          <button type="button" class="toolbar-btn" data-action="code" title="Code">
            &lt;/&gt;
          </button>
          <button type="button" class="toolbar-btn" data-action="quote" title="Quote">
            "
          </button>
          <span class="toolbar-separator"></span>
          <button type="button" class="toolbar-btn" data-action="ul" title="Unordered List">
            • List
          </button>
          <button type="button" class="toolbar-btn" data-action="ol" title="Ordered List">
            1. List
          </button>
          <button type="button" class="toolbar-btn" data-action="task" title="Task List">
            ☐ Task
          </button>
        </div>
        <textarea
          id="{{ $editorId }}"
          name="{{ $name }}"
          class="markdown-textarea"
          placeholder="{{ $placeholder }}"
        >{{ $value }}</textarea>
      </div>
    </div>
  </div>
</div>
