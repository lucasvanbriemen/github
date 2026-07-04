import { Controller } from "@hotwired/stimulus"

// Inline commenting on diff lines. The "+" button on a line clones the comment
// form template into that line's slot, filling the hidden path/line/side
// fields. Submitting posts to the review-comments endpoint (single comment or
// pending-review), which responds with a Turbo Stream.
export default class extends Controller {
  static values = { url: String }
  static targets = ["template"]

  open(event) {
    this.close()

    const button = event.currentTarget
    const slot = button.closest(".diff-cell").querySelector(".inline-comment-slot")
    if (!slot) return

    const form = this.templateTarget.content.cloneNode(true)
    form.querySelector('[data-field="path"]').value = button.dataset.path
    form.querySelector('[data-field="line"]').value = button.dataset.line
    form.querySelector('[data-field="side"]').value = button.dataset.side

    slot.appendChild(form)
    this.openSlot = slot
    slot.querySelector("textarea")?.focus()
  }

  close() {
    if (this.openSlot) {
      this.openSlot.innerHTML = ""
      this.openSlot = null
    }
  }

  // Close the inline form once the comment was accepted (Turbo Stream response).
  submitEnd(event) {
    if (event.detail.success) this.close()
  }
}
