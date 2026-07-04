import { Controller } from "@hotwired/stimulus"

// Makes rendered task-list checkboxes clickable. commonmarker renders them
// disabled; on connect we enable them, and a click PATCHes the checkbox index
// to the server, which flips the matching "- [ ]" line in the raw markdown and
// saves it back to GitHub. The change is optimistic and reverted on failure;
// the authoritative re-render arrives via a Turbo Stream broadcast.
export default class extends Controller {
  static values = { url: String }

  connect() {
    this.checkboxes.forEach((box) => {
      box.disabled = false
      box.addEventListener("change", this.onChange)
    })
  }

  disconnect() {
    this.checkboxes.forEach((box) => box.removeEventListener("change", this.onChange))
  }

  get checkboxes() {
    return Array.from(this.element.querySelectorAll('input[type="checkbox"]'))
  }

  onChange = (event) => {
    const box = event.target
    const index = this.checkboxes.indexOf(box)
    const checked = box.checked

    fetch(this.urlValue, {
      method: "PATCH",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content,
        Accept: "text/vnd.turbo-stream.html, application/json",
      },
      body: JSON.stringify({ index, checked }),
    })
      .then((response) => {
        if (!response.ok) box.checked = !checked
      })
      .catch(() => {
        box.checked = !checked
      })
  }
}
