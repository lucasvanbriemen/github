import { Controller } from "@hotwired/stimulus"

// Flash message: dismiss button, and auto-hide for notices (alerts stay
// until dismissed so errors aren't missed).
export default class extends Controller {
  static values = { autohide: Boolean }

  connect() {
    if (this.autohideValue) {
      this.timeout = setTimeout(() => this.dismiss(), 6000)
    }
  }

  disconnect() {
    clearTimeout(this.timeout)
  }

  dismiss() {
    this.element.remove()
  }
}
