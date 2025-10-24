## One time Mantis source integration setup

 - Copy the source integration plugins into mantis `plugins/` subdirectory
 - Install required plugins with Mantis plugin admin page
 - Create an "API Key" with a command like `openssl rand -hex 50` or similar.
   Set it the Source Integration plugin `manage_config_page` at
   `https://mantis.server.intra/plugin.php?page=Source/manage_config_page`

## Gitlab setup

 - Login with an Owner (or Administrator) of the relevant project(s)
 - First, we need to create a 'project webhook' (not to be confused with 'system webhook', 'server webhook' or 'file webhook').
   From the main GitLab page, navigate to Groups -> Explore Groups, then choose the relevant group,
   then at the menu on the left side choose 'Settings > Webhooks'. (Alternatively, go directly to a URL like
   `https://gitlab.server.intra/mygroup/myproject/-/hooks`.) From there, click 'Add New Webhook',
   and populate with the following settings:
    - URL: `https://mantis.server.intra/plugin.php?page=Source/checkin&api_key=xxxxxxxxxxxxxxx` (the `api_key` is found in Mantis > Manage > Plugins > Source Control Integration).
    - Secret token: `blank`
    - Trigger: `push event`, `merge request`, `pipeline events`
    - SSL verification: `on`
 - Second, from the main GitLab page, navigate to Groups -> Explore Groups, then choose the relevant group,
   then at the menu on the left side choose 'Settings > Access Tokens'. (Alternatively, go directly to a URL like
   `https://gitlab.server.intra/groups/mygroup/-/settings/access_tokens`.) From there, click 'Add new token',
   and populate with the following settings:
    - token name: anything you want really, something identifiable
    - expiration: whatever you want (max allowed is 1 year)
    - role: `Guest`
    - scope: `read_api`

   Then press "Create group access token", it will then give a screen saying "Your new group access token",
   click the eyeball icon to see it. It should start with `glpat-`. Copy this. You will need it as
   "GitLab API Key" in Mantis' configuration.


## Mantis repository setup

 - In Mantis administration, create a repository with a unique name and of type "Gitlab".
 - Populate the following fields:
  - "URL": this field is not used by this plugin. Leave it empty.
  - "GitLab Root": the root URL of the Gitlab webserver, required to access Web API. For example `https://gitlab.server.intra`
  - "GitLab Repository ID": the id of the Gitlab project. Can be found under the project's general settings in gitlab.
  - "GitLab Repository Name": full name of the project in the form `group-namespace/project-name`
  - "GitLab API Key": a "Group Access Token" created in GitLab (per above section)
  - "Allowed Branches": use `*` or a list of branches to track

```
array(5) {
  ["hub_root"]=>
  string(27) "https://gitlab.server.intra"
  ["hub_repoid"]=>
  string(1) "5"
  ["hub_reponame"]=>
  string(30) "dispora/dispora"
  ["hub_app_secret"]=>
  string(20) "glpat-xxxxxxxxxxxxxx-xxxxx"
  ["master_branch"]=>
  string(1) "*"
}
```

## Token renewal

Since GitLab only allows a 'group access token' to last one year, you will need to renew it at least that often.
To do so, just create another the same way you created the original. Then update each repository in Mantis with the updated
"GitLab API Key".
