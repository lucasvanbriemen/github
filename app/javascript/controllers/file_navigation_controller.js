import { Controller } from "@hotwired/stimulus"

// All file diffs are rendered into the page; this shows one at a time and
// drives prev/next + the file-list sidebar without any server round-trip.
export default class extends Controller {
  static targets = ["file", "link", "name", "position", "prev", "next"]

  connect() {
    this.index = 0
    this.render()
  }

  show(event) {
    this.index = Number(event.currentTarget.dataset.index)
    this.render()
  }

  prev() {
    if (this.index > 0) {
      this.index--
      this.render()
    }
  }

  next() {
    if (this.index < this.fileTargets.length - 1) {
      this.index++
      this.render()
    }
  }

  render() {
    this.fileTargets.forEach((el) => {
      el.hidden = Number(el.dataset.index) !== this.index
    })
    this.linkTargets.forEach((el) => {
      el.classList.toggle("current", Number(el.dataset.index) === this.index)
    })

    const active = this.linkTargets[this.index]
    if (active && this.hasNameTarget) this.nameTarget.textContent = active.title
    if (this.hasPositionTarget) this.positionTarget.textContent = `${this.index + 1} / ${this.fileTargets.length}`
    if (this.hasPrevTarget) this.prevTarget.disabled = this.index === 0
    if (this.hasNextTarget) this.nextTarget.disabled = this.index === this.fileTargets.length - 1

    // Keep the top of the active diff in view when jumping between files.
    this.element.querySelector(".item-wrapper")?.scrollIntoView({ block: "nearest" })
  }
}
