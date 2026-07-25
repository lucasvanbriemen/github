import { Controller } from "@hotwired/stimulus"

// Copies data-clipboard-text-value to the clipboard and briefly flashes the
// button label to confirm.
export default class extends Controller {
  static values = { text: String }
  static targets = ["button"]

  copy() {
    navigator.clipboard.writeText(this.textValue).then(() => {
      if (!this.hasButtonTarget) return
      // Remember the true label once — a rapid second click would otherwise
      // capture "Copied" as the label to restore.
      this.original ??= this.buttonTarget.textContent
      clearTimeout(this.timeout)
      this.buttonTarget.textContent = "Copied"
      this.timeout = setTimeout(() => {
        this.buttonTarget.textContent = this.original
      }, 1500)
    })
  }

  disconnect() {
    clearTimeout(this.timeout)
  }
}
