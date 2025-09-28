<div class="search-select-wrapper">
  <select class="search-select" name="{{ $name }}">
    @foreach($options as $value => $label)
      <option value="{{ $value }}" @if($selected === $value) selected @endif>{{ $label }}</option>
    @endforeach
  </select>

  <div class="select-ui-wrapper">
    <input class="search-input" type="text" placeholder="{{ $placeholder }}">
    <div class="option-wrapper">
      @foreach($options as $value => $label)
        <div class="option-item" data-value="{{ $value }}">
          <span class="main-text">{{ $label }}</span>
        </div>
      @endforeach
    </div>
  </div>
</div>