# Auto-Unresolve Feature - Proof of Concept

## Overview
This feature automatically unresolves a parent comment when a reply is posted to it. This ensures that resolved comments with new replies are visible again to the user, allowing them to see and respond to the new discussion.

## Use Case
1. You resolve a PR code review comment
2. A team member posts a reply to that comment
3. The parent comment automatically unresolves
4. You can now see the discussion and respond without manually unresolving it

## Implementation Details

### Files Modified

#### 1. **PullRequestComment Model** (`app/Models/PullRequestComment.php`)
- Added `unresolveParentIfResolved()` method
- This method checks if the comment has a parent (via `in_reply_to_id`)
- If parent exists and is resolved, it automatically unresolves it
- Uses the relationship `parentComment()` which already exists in the model

#### 2. **ProcessPullRequestReviewCommentWebhook** (`app/Listeners/ProcessPullRequestReviewCommentWebhook.php`)
- When a webhook for a new PR comment arrives (from GitHub)
- After creating/updating the `PullRequestComment` record
- Calls `unresolveParentIfResolved()` to automatically unresolve resolved parents
- Only triggers if action is not 'deleted'

#### 3. **BaseCommentController** (`app/Http/Controllers/BaseCommentController.php`)
- Updated `createPRComment()` method
- When a user creates a reply via the UI (in_reply_to_id provided)
- Checks if the parent comment is resolved
- Automatically sets `resolved = false` before saving

## How It Works

### Webhook Flow (Comment from GitHub)
```
GitHub sends webhook → ProcessPullRequestReviewCommentWebhook listener
→ Creates/Updates PullRequestComment with in_reply_to_id
→ Calls unresolveParentIfResolved()
→ Recursively unresolves parent and all ancestors in the chain
```

### API Flow (Comment from UI)
```
User posts reply in UI → createPRComment() endpoint
→ Calls GitHub API to create comment
→ Checks if parent comment is resolved
→ Recursively unresolves parent and all ancestors if needed
```

### Recursive Behavior
The unresolve happens recursively up the comment chain:
- When a reply is posted to **Comment A** → unresolve Comment A
- When a reply is posted to **Comment A.1** (child of A) → unresolve A.1, then A
- When a reply is posted to **Comment A.1.1** (grandchild of A) → unresolve A.1.1, then A.1, then A
- And so on for any depth of nesting

## Testing the Feature

### Manual Test Case 1: Simple Reply (Parent -> Child)
1. Navigate to a PR in the GitHub GUI
2. Resolve a top-level code review comment
3. Go to GitHub.com and post a reply to that comment
4. Webhook will trigger and unresolve the parent
5. Refresh the page to see the parent is now unresolved

### Manual Test Case 2: Nested Reply (Parent -> Child -> Grandchild)
1. Navigate to a PR in the GitHub GUI
2. Resolve a top-level code review comment (let's call it "A")
3. Go to GitHub.com and post a reply to that comment (creating "A.1")
4. Leave "A.1" unresolved
5. Post a reply to "A.1" (creating "A.1.1")
6. Webhook will trigger and unresolve both "A.1" and "A" recursively
7. Refresh the page to see both comments are now unresolved

### Manual Test Case 3: Deep Nesting (Multiple Levels)
1. Resolve a root comment
2. Create a chain of replies: Root -> Reply1 -> Reply2 -> Reply3
3. Resolve each comment in the chain
4. Post a reply to Reply3
5. All ancestor comments (Reply3, Reply2, Reply1, Root) should auto-unresolve
6. Verify the entire thread is now visible

### Manual Test Case 4: UI Reply (In Application)
1. Navigate to a PR in the GitHub GUI
2. Resolve a comment and one of its child comments
3. Click "Reply" on the child comment in the app
4. Type your reply and submit
5. Both the child and parent automatically unresolve
6. See the entire thread is now visible

## Database Changes
No database migrations needed. The feature uses existing columns:
- `base_comments.resolved` - Already exists
- `pull_request_comments.in_reply_to_id` - Already exists for threading

## Backward Compatibility
✅ Fully backward compatible
- No breaking changes
- No database schema modifications
- Existing comment resolution still works normally
- Only adds new behavior when replying to resolved comments

## Future Enhancements
- Add UI indicator showing "Auto-unresolved due to new reply"
- Add configuration option to disable auto-unresolve
- Add event/notification when auto-unresolve happens
- Extend feature to issue comments (when/if threading is supported)

## Code Locations
- Model method: `app/Models/PullRequestComment.php:51-60`
- Webhook implementation: `app/Listeners/ProcessPullRequestReviewCommentWebhook.php:58-81`
- API implementation: `app/Http/Controllers/BaseCommentController.php:87-102`
