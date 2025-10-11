<div class="background-overlay modal-container" id="{{ $id }}">
  <div class="modal-wrapper">
    <div class='header'>
      <h2 class='title'>{{ $title }}</h2>
      <button class='close-button'>{!! svg("cross") !!}</button>
    </div>

    <div class='content'>
      {{ $slot }}
    </div>

    <div class='footer'>{{ $footer }}</div>
  </div>
</div>