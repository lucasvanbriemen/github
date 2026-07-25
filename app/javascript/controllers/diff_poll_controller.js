import { Controller } from "@hotwired/stimulus"

// Reloads the Files tab when new commits are pushed. Polls a cheap endpoint
// for the PR's current head sha; when it changes, reloads the page so the diff
// re-renders. This doesn't depend on webhooks/ActionCable (which need a job
// worker the Passenger prod server doesn't run) — it just works as long as the
// web process is up.
export default class extends Controller {
  static values = {
    url: String,
    head: String,
    interval: { type: Number, default: 15000 },
  }

  connect() {
    this.stopped = false
    this.timer = setInterval(() => this.check(), this.intervalValue)
  }

  disconnect() {
    // An in-flight check must not fire Turbo.visit after the user navigated
    // away — it would reload whatever page they're on now.
    this.stopped = true
    clearInterval(this.timer)
  }

  check() {
    // Skip while a tab is hidden to avoid pointless polling.
    if (document.hidden) return

    fetch(this.urlValue, { headers: { Accept: "application/json" } })
      .then((response) => (response.ok ? response.json() : null))
      .then((data) => {
        if (this.stopped) return
        if (data && data.sha && data.sha !== this.headValue) this.reload()
      })
      .catch(() => {})
  }

  reload() {
    clearInterval(this.timer)
    if (window.Turbo) {
      window.Turbo.visit(window.location.href, { action: "replace" })
    } else {
      window.location.reload()
    }
  }
}
