<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
  <form id="myForm" method="POST" action="{{ route('api.webhook') }}">
    @csrf
    <input type="text" name="payload" id='payload' value='
  {
  "action": "created",
  "issue": {
    "url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2",
    "repository_url": "https://api.github.com/repos/lucasvanbriemen/github",
    "labels_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2/labels{/name}",
    "comments_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2/comments",
    "events_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2/events",
    "html_url": "https://github.com/lucasvanbriemen/github/issues/2",
    "id": 3431510799,
    "node_id": "I_kwDOPrFya87MiLMP",
    "number": 2,
    "title": "Test issue 2",
    "user": {
      "login": "lukaas-007",
      "id": 117530797,
      "node_id": "U_kgDOBwFgrQ",
      "avatar_url": "https://avatars.githubusercontent.com/u/117530797?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/lukaas-007",
      "html_url": "https://github.com/lukaas-007",
      "followers_url": "https://api.github.com/users/lukaas-007/followers",
      "following_url": "https://api.github.com/users/lukaas-007/following{/other_user}",
      "gists_url": "https://api.github.com/users/lukaas-007/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/lukaas-007/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/lukaas-007/subscriptions",
      "organizations_url": "https://api.github.com/users/lukaas-007/orgs",
      "repos_url": "https://api.github.com/users/lukaas-007/repos",
      "events_url": "https://api.github.com/users/lukaas-007/events{/privacy}",
      "received_events_url": "https://api.github.com/users/lukaas-007/received_events",
      "type": "User",
      "user_view_type": "public",
      "site_admin": false
    },
    "labels": [

    ],
    "state": "open",
    "locked": false,
    "assignee": {
      "login": "lukaas-007",
      "id": 117530797,
      "node_id": "U_kgDOBwFgrQ",
      "avatar_url": "https://avatars.githubusercontent.com/u/117530797?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/lukaas-007",
      "html_url": "https://github.com/lukaas-007",
      "followers_url": "https://api.github.com/users/lukaas-007/followers",
      "following_url": "https://api.github.com/users/lukaas-007/following{/other_user}",
      "gists_url": "https://api.github.com/users/lukaas-007/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/lukaas-007/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/lukaas-007/subscriptions",
      "organizations_url": "https://api.github.com/users/lukaas-007/orgs",
      "repos_url": "https://api.github.com/users/lukaas-007/repos",
      "events_url": "https://api.github.com/users/lukaas-007/events{/privacy}",
      "received_events_url": "https://api.github.com/users/lukaas-007/received_events",
      "type": "User",
      "user_view_type": "public",
      "site_admin": false
    },
    "assignees": [
      {
        "login": "lukaas-007",
        "id": 117530797,
        "node_id": "U_kgDOBwFgrQ",
        "avatar_url": "https://avatars.githubusercontent.com/u/117530797?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/lukaas-007",
        "html_url": "https://github.com/lukaas-007",
        "followers_url": "https://api.github.com/users/lukaas-007/followers",
        "following_url": "https://api.github.com/users/lukaas-007/following{/other_user}",
        "gists_url": "https://api.github.com/users/lukaas-007/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/lukaas-007/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/lukaas-007/subscriptions",
        "organizations_url": "https://api.github.com/users/lukaas-007/orgs",
        "repos_url": "https://api.github.com/users/lukaas-007/repos",
        "events_url": "https://api.github.com/users/lukaas-007/events{/privacy}",
        "received_events_url": "https://api.github.com/users/lukaas-007/received_events",
        "type": "User",
        "user_view_type": "public",
        "site_admin": false
      }
    ],
    "milestone": null,
    "comments": 1,
    "created_at": "2025-09-18T18:31:36Z",
    "updated_at": "2025-09-20T21:01:36Z",
    "closed_at": null,
    "author_association": "CONTRIBUTOR",
    "type": null,
    "active_lock_reason": null,
    "sub_issues_summary": {
      "total": 0,
      "completed": 0,
      "percent_completed": 0
    },
    "issue_dependencies_summary": {
      "blocked_by": 0,
      "total_blocked_by": 0,
      "blocking": 0,
      "total_blocking": 0
    },
    "body": null,
    "reactions": {
      "url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2/reactions",
      "total_count": 0,
      "+1": 0,
      "-1": 0,
      "laugh": 0,
      "hooray": 0,
      "confused": 0,
      "heart": 0,
      "rocket": 0,
      "eyes": 0
    },
    "timeline_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2/timeline",
    "performed_via_github_app": null,
    "state_reason": null
  },
  "comment": {
    "url": "https://api.github.com/repos/lucasvanbriemen/github/issues/comments/3315255325",
    "html_url": "https://github.com/lucasvanbriemen/github/issues/2#issuecomment-3315255325",
    "issue_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/2",
    "id": 3315255325,
    "node_id": "IC_kwDOPrFya87Fmsgd",
    "user": {
      "login": "lukaas-007",
      "id": 117530797,
      "node_id": "U_kgDOBwFgrQ",
      "avatar_url": "https://avatars.githubusercontent.com/u/117530797?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/lukaas-007",
      "html_url": "https://github.com/lukaas-007",
      "followers_url": "https://api.github.com/users/lukaas-007/followers",
      "following_url": "https://api.github.com/users/lukaas-007/following{/other_user}",
      "gists_url": "https://api.github.com/users/lukaas-007/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/lukaas-007/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/lukaas-007/subscriptions",
      "organizations_url": "https://api.github.com/users/lukaas-007/orgs",
      "repos_url": "https://api.github.com/users/lukaas-007/repos",
      "events_url": "https://api.github.com/users/lukaas-007/events{/privacy}",
      "received_events_url": "https://api.github.com/users/lukaas-007/received_events",
      "type": "User",
      "user_view_type": "public",
      "site_admin": false
    },
    "created_at": "2025-09-20T21:01:36Z",
    "updated_at": "2025-09-20T21:01:36Z",
    "body": "Test comment",
    "author_association": "MEMBER",
    "reactions": {
      "url": "https://api.github.com/repos/lucasvanbriemen/github/issues/comments/3315255325/reactions",
      "total_count": 0,
      "+1": 0,
      "-1": 0,
      "laugh": 0,
      "hooray": 0,
      "confused": 0,
      "heart": 0,
      "rocket": 0,
      "eyes": 0
    },
    "performed_via_github_app": null
  },
  "repository": {
    "id": 1051816555,
    "node_id": "R_kgDOPrFyaw",
    "name": "github",
    "full_name": "lucasvanbriemen/github",
    "private": false,
    "owner": {
      "login": "lucasvanbriemen",
      "id": 222973435,
      "node_id": "O_kgDODUpN-w",
      "avatar_url": "https://avatars.githubusercontent.com/u/222973435?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/lucasvanbriemen",
      "html_url": "https://github.com/lucasvanbriemen",
      "followers_url": "https://api.github.com/users/lucasvanbriemen/followers",
      "following_url": "https://api.github.com/users/lucasvanbriemen/following{/other_user}",
      "gists_url": "https://api.github.com/users/lucasvanbriemen/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/lucasvanbriemen/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/lucasvanbriemen/subscriptions",
      "organizations_url": "https://api.github.com/users/lucasvanbriemen/orgs",
      "repos_url": "https://api.github.com/users/lucasvanbriemen/repos",
      "events_url": "https://api.github.com/users/lucasvanbriemen/events{/privacy}",
      "received_events_url": "https://api.github.com/users/lucasvanbriemen/received_events",
      "type": "Organization",
      "user_view_type": "public",
      "site_admin": false
    },
    "html_url": "https://github.com/lucasvanbriemen/github",
    "description": null,
    "fork": false,
    "url": "https://api.github.com/repos/lucasvanbriemen/github",
    "forks_url": "https://api.github.com/repos/lucasvanbriemen/github/forks",
    "keys_url": "https://api.github.com/repos/lucasvanbriemen/github/keys{/key_id}",
    "collaborators_url": "https://api.github.com/repos/lucasvanbriemen/github/collaborators{/collaborator}",
    "teams_url": "https://api.github.com/repos/lucasvanbriemen/github/teams",
    "hooks_url": "https://api.github.com/repos/lucasvanbriemen/github/hooks",
    "issue_events_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/events{/number}",
    "events_url": "https://api.github.com/repos/lucasvanbriemen/github/events",
    "assignees_url": "https://api.github.com/repos/lucasvanbriemen/github/assignees{/user}",
    "branches_url": "https://api.github.com/repos/lucasvanbriemen/github/branches{/branch}",
    "tags_url": "https://api.github.com/repos/lucasvanbriemen/github/tags",
    "blobs_url": "https://api.github.com/repos/lucasvanbriemen/github/git/blobs{/sha}",
    "git_tags_url": "https://api.github.com/repos/lucasvanbriemen/github/git/tags{/sha}",
    "git_refs_url": "https://api.github.com/repos/lucasvanbriemen/github/git/refs{/sha}",
    "trees_url": "https://api.github.com/repos/lucasvanbriemen/github/git/trees{/sha}",
    "statuses_url": "https://api.github.com/repos/lucasvanbriemen/github/statuses/{sha}",
    "languages_url": "https://api.github.com/repos/lucasvanbriemen/github/languages",
    "stargazers_url": "https://api.github.com/repos/lucasvanbriemen/github/stargazers",
    "contributors_url": "https://api.github.com/repos/lucasvanbriemen/github/contributors",
    "subscribers_url": "https://api.github.com/repos/lucasvanbriemen/github/subscribers",
    "subscription_url": "https://api.github.com/repos/lucasvanbriemen/github/subscription",
    "commits_url": "https://api.github.com/repos/lucasvanbriemen/github/commits{/sha}",
    "git_commits_url": "https://api.github.com/repos/lucasvanbriemen/github/git/commits{/sha}",
    "comments_url": "https://api.github.com/repos/lucasvanbriemen/github/comments{/number}",
    "issue_comment_url": "https://api.github.com/repos/lucasvanbriemen/github/issues/comments{/number}",
    "contents_url": "https://api.github.com/repos/lucasvanbriemen/github/contents/{+path}",
    "compare_url": "https://api.github.com/repos/lucasvanbriemen/github/compare/{base}...{head}",
    "merges_url": "https://api.github.com/repos/lucasvanbriemen/github/merges",
    "archive_url": "https://api.github.com/repos/lucasvanbriemen/github/{archive_format}{/ref}",
    "downloads_url": "https://api.github.com/repos/lucasvanbriemen/github/downloads",
    "issues_url": "https://api.github.com/repos/lucasvanbriemen/github/issues{/number}",
    "pulls_url": "https://api.github.com/repos/lucasvanbriemen/github/pulls{/number}",
    "milestones_url": "https://api.github.com/repos/lucasvanbriemen/github/milestones{/number}",
    "notifications_url": "https://api.github.com/repos/lucasvanbriemen/github/notifications{?since,all,participating}",
    "labels_url": "https://api.github.com/repos/lucasvanbriemen/github/labels{/name}",
    "releases_url": "https://api.github.com/repos/lucasvanbriemen/github/releases{/id}",
    "deployments_url": "https://api.github.com/repos/lucasvanbriemen/github/deployments",
    "created_at": "2025-09-06T19:24:58Z",
    "updated_at": "2025-09-20T14:25:53Z",
    "pushed_at": "2025-09-20T14:25:49Z",
    "git_url": "git://github.com/lucasvanbriemen/github.git",
    "ssh_url": "git@github.com:lucasvanbriemen/github.git",
    "clone_url": "https://github.com/lucasvanbriemen/github.git",
    "svn_url": "https://github.com/lucasvanbriemen/github",
    "homepage": null,
    "size": 365,
    "stargazers_count": 0,
    "watchers_count": 0,
    "language": "PHP",
    "has_issues": true,
    "has_projects": true,
    "has_downloads": true,
    "has_wiki": false,
    "has_pages": false,
    "has_discussions": false,
    "forks_count": 0,
    "mirror_url": null,
    "archived": false,
    "disabled": false,
    "open_issues_count": 4,
    "license": null,
    "allow_forking": true,
    "is_template": false,
    "web_commit_signoff_required": false,
    "topics": [

    ],
    "visibility": "public",
    "forks": 0,
    "open_issues": 4,
    "watchers": 0,
    "default_branch": "main",
    "custom_properties": {

    }
  },
  "organization": {
    "login": "lucasvanbriemen",
    "id": 222973435,
    "node_id": "O_kgDODUpN-w",
    "url": "https://api.github.com/orgs/lucasvanbriemen",
    "repos_url": "https://api.github.com/orgs/lucasvanbriemen/repos",
    "events_url": "https://api.github.com/orgs/lucasvanbriemen/events",
    "hooks_url": "https://api.github.com/orgs/lucasvanbriemen/hooks",
    "issues_url": "https://api.github.com/orgs/lucasvanbriemen/issues",
    "members_url": "https://api.github.com/orgs/lucasvanbriemen/members{/member}",
    "public_members_url": "https://api.github.com/orgs/lucasvanbriemen/public_members{/member}",
    "avatar_url": "https://avatars.githubusercontent.com/u/222973435?v=4",
    "description": ""
  },
  "sender": {
    "login": "lukaas-007",
    "id": 117530797,
    "node_id": "U_kgDOBwFgrQ",
    "avatar_url": "https://avatars.githubusercontent.com/u/117530797?v=4",
    "gravatar_id": "",
    "url": "https://api.github.com/users/lukaas-007",
    "html_url": "https://github.com/lukaas-007",
    "followers_url": "https://api.github.com/users/lukaas-007/followers",
    "following_url": "https://api.github.com/users/lukaas-007/following{/other_user}",
    "gists_url": "https://api.github.com/users/lukaas-007/gists{/gist_id}",
    "starred_url": "https://api.github.com/users/lukaas-007/starred{/owner}{/repo}",
    "subscriptions_url": "https://api.github.com/users/lukaas-007/subscriptions",
    "organizations_url": "https://api.github.com/users/lukaas-007/orgs",
    "repos_url": "https://api.github.com/users/lukaas-007/repos",
    "events_url": "https://api.github.com/users/lukaas-007/events{/privacy}",
    "received_events_url": "https://api.github.com/users/lukaas-007/received_events",
    "type": "User",
    "user_view_type": "public",
    "site_admin": false
  }
}' />
    <button type="submit">Send</button>
  </form>

   <script>
    document.getElementById('myForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      const payload = document.getElementById('payload').value;

      await fetch("{{ route('api.webhook') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-github-event": "issue_comment",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
        },
        body: payload
      });
    });
  </script>

</body>
</html>