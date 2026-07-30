Rails.application.routes.draw do
  root to: "organizations#index"

  post "/incoming_hook", to: "incoming_webhooks#create"

  # Authenticated proxy for GitHub-hosted images in rendered markdown.
  get "/proxy/image", to: "images#show", as: :image_proxy

  # Browser-extension endpoint: maps a github.com URL to the GUI equivalent.
  post "/check_end_point", to: "redirects#check"

  resources :organizations, only: [ :index ]

  # No index action exists — the list lives on the home page.
  resources :notifications, only: [] do
    member { post :complete }
  end

  get "/:organization_name/:repository_name", to: "items#index", as: :items

  scope "/:organization_name/:repository_name" do
    get   "items/new",                 to: "items#new",             as: :new_item
    post  "items",                     to: "items#create",          as: :create_item
    get   "item/:number",              to: "items#show",            as: :item
    patch "item/:number",              to: "items#update"
    patch "item/:number/checkbox",     to: "items#toggle_checkbox", as: :item_checkbox
    put   "item/:number/labels",       to: "item_labels#update",    as: :item_labels
    put   "item/:number/assignees",    to: "item_assignees#update", as: :item_assignees
    put   "item/:number/reviewers",    to: "item_reviewers#update", as: :item_reviewers
    post  "item/:number/comments",     to: "base_comments#create",  as: :item_comments
    patch "item/:number/comments/:id", to: "base_comments#update",  as: :item_comment
    get   "item/:number/files",        to: "items#files",           as: :item_files
    get   "item/:number/head_sha",     to: "items#head_sha",        as: :item_head_sha
    # diff-poll: head_sha detects a new push, diff swaps the changed regions.
    get   "item/:number/diff",         to: "items#diff",            as: :item_diff
    post   "item/:number/review_comments", to: "review_comments#create", as: :item_review_comments
    delete "item/:number/review_comments/:key", to: "review_comments#destroy", as: :item_review_comment
    post   "item/:number/review",       to: "reviews#create",        as: :item_review
    post   "item/:number/merge",        to: "merges#create",         as: :item_merge

    # Lazy-loaded sidebar sections (Turbo Frames) + their write actions.
    get    "item/:number/links",        to: "item_links#show",       as: :item_links
    patch  "item/:number/links",        to: "item_links#update"
    get    "item/:number/projects",     to: "item_projects#show",    as: :item_projects
    post   "item/:number/projects",     to: "item_projects#create"
    patch  "item/:number/projects",     to: "item_projects#update"
    delete "item/:number/projects",     to: "item_projects#destroy"
  end
end
