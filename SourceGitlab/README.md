## One time Mantis source integration setup

 - Copy the source integration plugins into mantis "plugins/" subdirectory
 - Install required plugins with Mantis plugin admin page
 - Get the "API Key" (like 'abcdeb8129a4451a35f47881') from the Source Integration plugin "manage_config_page"
   http://mantis.server.intra/plugin.php?page=Source/manage_config_page

## Gitlab setup

 - Login with an Owner (or Adminstrator) of a Project, go to "Projet settings", "Web hooks",
   add a Push hook with Mantis url and Mantis Source Integration plugin "API Key":
   http://mantis.server.intra/plugin.php?page=Source/checkin&api_key=abcdeb8129a4451a35f47881
 - Create a user with (at least) read access to the repository. For a public repository, any user would work.
   Login with this user, go to "Profile settings", "Account", and copy the "Private token".

## Mantis repository setup

 - In Mantis administration, create a repository with a unique name and of type "Gitlab".
 - URL field is not used by the plugin.
 - Gitlab config fields are:
  - hub_root: root url of the Gitlab webserver, required to access Web API
  - hub_repoid: id of the Gitlab projet, starting from 1 for the first created project (auto-filed if reponame is valid and readable for the user)
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
