import { Controller } from "@hotwired/stimulus"

// Copies data-clipboard-text-value to the clipboard and briefly flashes the
// button label to confirm.
export default class extends Controller {
  static values = { text: String }
  static targets = ["button"]

  copy() {
    navigator.clipboard.writeText(this.textValue).then(() => {
      if (!this.hasButtonTarget) return
      const original = this.buttonTarget.textContent
      this.buttonTarget.textContent = "Copied"
      setTimeout(() => {
        this.buttonTarget.textContent = original
      }, 1500)
    })
  }
}
