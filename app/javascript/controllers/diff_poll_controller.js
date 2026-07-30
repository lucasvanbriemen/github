import { Controller } from "@hotwired/stimulus"

// Keeps the Files tab current when new commits are pushed. Polls a cheap
// endpoint for the PR's current head sha; when it changes, it fetches the tab
// as a turbo_stream and swaps only the file list and the diffs. This doesn't
// depend on webhooks/ActionCable (which need a job worker the Passenger prod
// server doesn't run) — it just works as long as the web process is up.
export default class extends Controller {
  static values = {
    url: String,
    refreshUrl: String,
    head: String,
    interval: { type: Number, default: 15000 },
  }

  connect() {
    this.stopped = false
    this.timer = setInterval(() => this.check(), this.intervalValue)
  }

  disconnect() {
    // An in-flight check must not swap markup (or fall back to a visit) after
    // the user navigated away — it would act on whatever page they're on now.
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
        if (data && data.sha && data.sha !== this.headValue) this.refresh(data.sha)
      })
      .catch(() => {})
  }

  refresh(sha) {
    if (!window.Turbo) return this.reload()

    fetch(this.refreshUrlValue, { headers: { Accept: "text/vnd.turbo-stream.html" } })
      .then((response) => (response.ok ? response.text() : Promise.reject(response)))
      .then((streams) => {
        if (this.stopped) return
        window.Turbo.renderStreamMessage(streams)
        // Only record the sha once its diff is actually on the page, so a
        // failed swap leaves the next poll something to retry.
        this.headValue = sha
      })
      // A whole-page render is worse than a swap but better than a tab that
      // silently keeps showing the previous commit's diff.
      .catch(() => this.reload())
  }

  reload() {
    if (this.stopped) return
    clearInterval(this.timer)
    if (window.Turbo) {
      window.Turbo.visit(window.location.href, { action: "replace" })
    } else {
      window.location.reload()
    }
  }
}
