# SourceForgejo configuration

The **SourceForgejo** plugin links MantisBT with Git repositories hosted on a
[Forgejo](https://forgejo.org/) instance. It talks to Forgejo's REST API (v1)
and receives push notifications through a webhook.

```
 git push ──► Forgejo ──webhook (POST, HMAC-SHA256)──► MantisBT  plugin.php?page=Source/checkin
                 ▲                                         │
                 └────── REST API v1 (Authorization: token) ◄┘   list commits + changed files
```

## Requirements

- MantisBT and the Source Integration framework as stated in the main
  [README](../README.md)
- Forgejo (developed and tested with Forgejo 11.0)
- PHP [cURL extension](https://www.php.net/book.curl) (recommended); without
  it the plugin falls back to PHP's HTTP stream wrapper, which requires
  `allow_url_fopen = On`

## Differences to SourceGitea

Forgejo is API-compatible with Gitea, so the SourceGitea plugin can be pointed
at a Forgejo server too. SourceForgejo is nevertheless a separate plugin,
because it works differently in the following areas:

| Topic                | SourceGitea                               | SourceForgejo                                    |
|----------------------|-------------------------------------------|--------------------------------------------------|
| API authentication   | `?access_token=` in the URL (deprecated)  | `Authorization: token …` header                  |
| Webhook verification | none (API key only)                       | HMAC-SHA256 signature checked against the secret |
| Changed files        | not imported                              | imported (added / modified / removed)            |
| History import       | one API request per commit                | one API request per 50 commits                   |
| Large pushes         | relies on the commits listed in payload   | reads the branch from the API (payload is capped at 5 commits by default) |
| Allowed branches     | applied to imports only                   | applied to imports and webhook pushes            |
| File links           | `/src/branch/<sha>/…`                     | permalink `/src/commit/<sha>/…`                  |
| Pull request links   | GitLab-style, not valid                   | `/pulls/<n>`                                     |

## 1. MantisBT: one-time setup of the framework

1. Copy (or mount) the `Source` and `SourceForgejo` directories into MantisBT's
   `plugins/` directory.
2. *Manage → Manage Plugins*: install **Source Control Integration** first,
   then **Source Forgejo Integration**.
3. *Repositories → Configuration*: note the **API Key**. Generate one if the
   field is empty; it is required for the webhook unless the Forgejo server's
   address is listed under *Allowed Addresses*.

## 2. Forgejo: access token

The token is used by MantisBT to read commits. It is not needed for public
repositories.

1. Log in as the user MantisBT should act as (a dedicated, read-only account
   is recommended).
2. *Settings → Applications → Access tokens → Generate new token*
   - Token name: e.g. `mantis-source-integration`
   - Repository and Organization Access: *All* (or *Public only*)
   - Permissions: **repository → Read**; everything else *No access*
3. Copy the token; it is only shown once.

Administrators can alternatively create the token on the command line:

```sh
forgejo admin user generate-access-token --username <user> \
    --token-name mantis-source-integration --scopes read:repository --raw
```

## 3. MantisBT: repository

*Repositories → Create Repository*, select type **Forgejo**, then fill in:

| Field                  | Value                                                                 |
|------------------------|-----------------------------------------------------------------------|
| Name                   | Unique name, used in changeset links (`c:<name>:<revision>:`)         |
| URL                    | Not used by the plugin; the repository's web URL is a sensible value  |
| Forgejo Root           | Base URL of the instance, e.g. `https://git.example.com`              |
| Forgejo Repository Owner | User or organization owning the repository                          |
| Forgejo Repository Name | Name of the repository                                               |
| Forgejo Access Token   | Token created in step 2                                               |
| Forgejo Webhook Secret | Random string, e.g. the output of `openssl rand -hex 24`              |
| Allowed Branches       | Comma-separated list of branches, or `*` for all                      |

After saving, the form shows whether the API can be reached with these
settings (**API Access**), and the **Webhook Target URL**.

> **Warning: think before importing an existing repository.**
> *Import Everything* — as well as the first push received through the webhook —
> processes the commit messages of the **complete history** of the allowed
> branches. Any message matching the framework's regular expressions (by default
> `issue #123`, `fixes #123` …) is linked to the MantisBT issue with that number
> and, if *Enable resolving* is on, **resolves it**. For repositories whose
> history references another tracker's issue numbers (forks, migrated projects),
> disable resolving before the first import, or do not link them at all.

## 4. Forgejo: webhook

In the repository (or organization): *Settings → Webhooks → Add webhook →
Forgejo*

| Field           | Value                                                                              |
|-----------------|------------------------------------------------------------------------------------|
| Target URL      | `https://mantis.example.com/plugin.php?page=Source/checkin&api_key=<API Key>`      |
| HTTP method     | `POST`                                                                             |
| POST content type | `application/json`                                                               |
| Secret          | The *Forgejo Webhook Secret* entered in MantisBT                                   |
| Trigger on      | *Push events*                                                                      |
| Branch filter   | `*`                                                                                |

Notes:

- Requests without a valid `X-Forgejo-Signature` are rejected with HTTP 403 as
  soon as a webhook secret is set in MantisBT. If no secret is set there, the
  webhook must not define one either.
- If MantisBT is not reachable anonymously (reverse proxy with single sign-on,
  HTTP authentication), the check-in page must be exempted from it.
- If MantisBT runs on the same host or in a private network, Forgejo may
  refuse to deliver the webhook; add the host to `ALLOWED_HOST_LIST` in the
  `[webhook]` section of Forgejo's `app.ini`.
- Use *Test delivery* and the *Recent deliveries* list of the webhook to check
  the response sent by MantisBT.

## 5. Linking commits to issues

With the framework's default settings, commit messages are interpreted as
follows:

| Commit message contains         | Effect                                              |
|---------------------------------|-----------------------------------------------------|
| `issue #123`, `bug #123`        | Changeset is attached to issue 123                  |
| `fixes #123`, `resolved #123`   | Changeset is attached and the issue is resolved     |

An issue is only resolved if the commit's author or committer can be mapped to
a MantisBT user who is allowed to handle the issue. The mapping uses, in this
order: the *Source Control Username* set in the user's account preferences
(*My Account → Preferences*), the e-mail address, the real name and the user
name.

## Troubleshooting

| Symptom                                           | Cause / solution                                                                 |
|---------------------------------------------------|----------------------------------------------------------------------------------|
| *API Access* shows `HTTP 401` / `HTTP 403`        | Token invalid or lacking the `read:repository` scope                              |
| *API Access* shows `HTTP 404`                     | Owner or repository name wrong, or a private repository accessed without token    |
| Webhook delivery answers `Invalid webhook signature` | Secret differs between the webhook and the MantisBT repository                 |
| Webhook delivery answers `Invalid remote check-in address` | `api_key` missing or wrong in the target URL                             |
| Webhook delivery answers `Invalid repository name` | No Forgejo repository in MantisBT matches the payload's owner/name and host      |
| Delivery succeeds, but no changeset appears       | Branch is not in *Allowed Branches* (see the response body of the delivery)       |
| Changeset attached, issue not resolved            | Author cannot be mapped to a MantisBT user with sufficient access (see step 5)    |
