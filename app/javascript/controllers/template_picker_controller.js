import { Controller } from "@hotwired/stimulus"

// Fills the description textarea with a chosen issue/PR template body. If the
// user has already typed something, confirm before overwriting.
export default class extends Controller {
  static targets = ["data", "body"]

  get templates() {
    return JSON.parse(this.dataTarget.textContent)
  }

  get textarea() {
    // The markdown composer renders a textarea inside the body wrapper.
    return this.bodyTarget.querySelector("textarea")
  }

  apply(event) {
    const body = this.templates[event.currentTarget.dataset.templateId]
    if (body === undefined) return

    const textarea = this.textarea
    if (!textarea) return

    if (textarea.value.trim() && !confirm("Replace the current description with this template?")) return

    textarea.value = body
    textarea.dispatchEvent(new Event("input", { bubbles: true }))

    this.element.querySelectorAll(".template-option").forEach((el) => el.classList.remove("selected"))
    event.currentTarget.classList.add("selected")
  }
}
