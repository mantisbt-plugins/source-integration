## One time Mantis source integration setup

 - Copy the source integration plugins into mantis "plugins/" subdirectory
 - Install required plugins with Mantis plugin admin page
 - Get the "API Key" (like 'abcdeb8129a4451a35f47881') from the Source Integration plugin "manage_config_page"
   http://mantis.server.intra/plugin.php?page=Source/manage_config_page

## Gitlab setup

 - Login with an Owner (or Administrator) of a Project, go to *Settings -> Integrations* and
   add a Push hook to Web Hooks section with Mantis URL and Mantis Source Integration plugin "API Key":
   `http://mantis.server.intra/plugin.php?page=Source/checkin&api_key=abcdeb8129a4451a35f47881`
 - Go to your user *Settings -> Access Token -> Personal Access Tokens* and create a token with at least "api"
   access. You will need it as "hub_app_secret" in Mantis configuration.

## Mantis repository setup

 - In Mantis administration, create a repository with a unique name and of type "Gitlab".
 - URL field is not used by the plugin.
 - Gitlab config fields are:
  - hub_root: root url of the Gitlab webserver, required to access Web API
  - hub_repoid: id of the Gitlab project. Should be auto-filled if reponame is valid and readable for the user.
  - hub_reponame: full name of the project in the form "group-namespace/project-name"
  - hub_app_secret: the "Private token" of a user with at least a read access to the project.
  - master_branch: use '*' or a list of branches to track

```
array(5) {
  ["hub_root"]=>
  string(27) "http://gitlab.server.intra"
  ["hub_repoid"]=>
  string(1) "5"
  ["hub_reponame"]=>
  string(30) "dispora/dispora"
  ["hub_app_secret"]=>
  string(20) "abcde-private-token"
  ["master_branch"]=>
  string(1) "*"
}
```
