import { Controller } from "@hotwired/stimulus"

// Submits the closest form when an input changes — used for the project
// status/select dropdowns so picking a value saves immediately.
export default class extends Controller {
  submit(event) {
    event.target.closest("form")?.requestSubmit()
  }
}
