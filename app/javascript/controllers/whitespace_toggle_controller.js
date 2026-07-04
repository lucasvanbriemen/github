import { Controller } from "@hotwired/stimulus"

// Toggles a class on the diff so whitespace-only rows are de-emphasized. The
// server always renders the full diff (data-whitespace-only on each row); this
// is purely a display toggle, no re-request.
export default class extends Controller {
  toggle(event) {
    this.element.classList.toggle("hide-whitespace", event.target.checked)
  }
}
