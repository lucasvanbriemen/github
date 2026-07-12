import { Controller } from "@hotwired/stimulus"

// Toggles the item body between its rendered markdown and an edit form.
// Saving posts to items#update (which broadcasts the new body); the returned
// redirect/broadcast re-renders this partial back in display mode.
export default class extends Controller {
  static targets = ["display", "form"]

  edit() {
    this.displayTarget.hidden = true
    this.formTarget.hidden = false
    this.formTarget.querySelector("textarea")?.focus()
  }

  cancel() {
    this.formTarget.hidden = true
    this.displayTarget.hidden = false
  }
}
