import { Controller } from "@hotwired/stimulus"

// Attached to a <details> form (e.g. "Reply…"): focus its textarea when
// opened so typing can start immediately.
export default class extends Controller {
  toggle() {
    if (this.element.open) this.element.querySelector("textarea")?.focus()
  }
}
