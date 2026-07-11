import { Controller } from "@hotwired/stimulus"

// All file diffs are rendered into the page; this shows one at a time and
// drives prev/next + the file-list sidebar without any server round-trip.
// The current file is kept in the URL hash (as the wrapper's element id) so
// it survives the reloads diff-poll and Turbo refresh broadcasts trigger.
export default class extends Controller {
  static targets = ["file", "link", "name", "position", "prev", "next"]

  connect() {
    this.index = this.restoredIndex()
    this.render()
  }

  show(event) {
    this.navigate(Number(event.currentTarget.dataset.index))
  }

  prev() {
    if (this.index > 0) this.navigate(this.index - 1)
  }

  next() {
    if (this.index < this.fileTargets.length - 1) this.navigate(this.index + 1)
  }

  navigate(index) {
    this.index = index
    this.render()
    const anchor = this.fileTargets[index]?.id
    // Preserve history.state — Turbo keeps its restoration identifier there.
    if (anchor) history.replaceState(history.state, "", `#${anchor}`)
  }

  restoredIndex() {
    const anchor = window.location.hash.slice(1)
    if (!anchor) return 0
    const index = this.fileTargets.findIndex((el) => el.id === anchor)
    return index >= 0 ? index : 0
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
