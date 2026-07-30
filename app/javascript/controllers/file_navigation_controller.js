import { Controller } from "@hotwired/stimulus"

// All file diffs are rendered into the page; this shows one at a time and
// drives prev/next + the file-list sidebar without any server round-trip.
// The current file is kept in the URL hash (as the wrapper's element id) so it
// survives both a Turbo navigation and diff-poll swapping the diffs underneath
// this controller.
export default class extends Controller {
  static targets = ["file", "link", "name", "prev", "next"]

  connect() {
    this.index = this.restoredIndex()
    this.render({ scroll: true })
  }

  disconnect() {
    this.restorePending = false
  }

  // diff-poll replaces the file list and the diffs but not this element, so the
  // controller stays connected while its targets change out from under it.
  // Re-deriving from the hash keeps the user on the same file when it's still
  // in the new diff, and falls back to the first file when the push removed it.
  fileTargetConnected() {
    this.scheduleRestore()
  }

  fileTargetDisconnected() {
    this.scheduleRestore()
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
    this.render({ scroll: true })
    const anchor = this.fileTargets[index]?.id
    // Preserve history.state — Turbo keeps its restoration identifier there.
    if (anchor) history.replaceState(history.state, "", `#${anchor}`)
  }

  // A swap fires one target callback per file; coalesce them into a single
  // render on the microtask queue, before the browser paints the new diff.
  scheduleRestore() {
    if (this.restorePending) return
    this.restorePending = true

    Promise.resolve().then(() => {
      if (!this.restorePending) return
      this.restorePending = false
      this.index = this.restoredIndex()
      // No scroll here: a poll-driven swap must not yank the page around while
      // the user is reading.
      this.render()
    })
  }

  restoredIndex() {
    const anchor = window.location.hash.slice(1)
    if (!anchor) return 0
    const index = this.fileTargets.findIndex((el) => el.id === anchor)
    return index >= 0 ? index : 0
  }

  render({ scroll = false } = {}) {
    this.fileTargets.forEach((el) => {
      el.hidden = Number(el.dataset.index) !== this.index
    })
    this.linkTargets.forEach((el) => {
      el.classList.toggle("current", Number(el.dataset.index) === this.index)
    })

    const active = this.linkTargets[this.index]
    if (active && this.hasNameTarget) this.nameTarget.textContent = active.title
    if (this.hasPrevTarget) this.prevTarget.disabled = this.index === 0
    if (this.hasNextTarget) this.nextTarget.disabled = this.index === this.fileTargets.length - 1

    // Keep the top of the active diff in view when jumping between files.
    if (scroll) this.element.querySelector(".item-wrapper")?.scrollIntoView({ block: "nearest" })
  }
}
