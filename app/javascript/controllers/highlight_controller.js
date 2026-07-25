import { Controller } from "@hotwired/stimulus"

// When an item page is opened from a notification, the URL carries ?comment=ID
// (or ?highlight=item). Scroll that element into view and flash it so the user
// sees what changed.
export default class extends Controller {
  connect() {
    const params = new URLSearchParams(window.location.search)
    const commentId = params.get("comment")
    const highlight = params.get("highlight")

    let target = null
    if (commentId) target = document.getElementById(`base_comment_${commentId}`)
    else if (highlight === "item") target = this.element.querySelector(".item-wrapper")

    if (!target) return

    target.scrollIntoView({ behavior: "smooth", block: "center" })
    target.classList.add("flash-highlight")
    setTimeout(() => target.classList.remove("flash-highlight"), 2000)

    // One-shot: strip the params so refresh morphs and back/forward
    // restorations don't scroll and flash all over again.
    params.delete("comment")
    params.delete("highlight")
    const query = params.toString()
    history.replaceState(history.state, "",
      window.location.pathname + (query ? `?${query}` : "") + window.location.hash)
  }
}
