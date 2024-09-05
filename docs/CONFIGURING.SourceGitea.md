## One time Mantis source integration setup

 - Copy the source integration plugins into mantis "plugins/" subdirectory
 - Install required plugins with Mantis plugin admin page
 - Get the "API Key" (like 'abcdeb8129a4451a35f47881') from the Source Integration plugin "manage_config_page"
   http://mantis.server.intra/plugin.php?page=Source/manage_config_page

## Gitea setup

 - Login with an Owner (or Administrator) of a Project, go to *Settings -> Webhooks* and
   add a Gitea hook with Mantis URL and Mantis Source Integration plugin "API Key":
   `http://mantis.server.intra/plugin.php?page=Source/checkin&api_key=abcdeb8129a4451a35f47881`
 - Go to your user *Settings -> Applications -> Manage Access Tokens* and create a token. You will need it as "hub_app_secret" in Mantis configuration.

## Mantis repository setup

 - In Mantis administration, create a repository with a unique name and of type "Gitea".
 - URL field is not used by the plugin.
 - Gitea config fields are:
  - hub_root: root url of the Gitea webserver, required to access Web API
  - hub_ownerid: name of the Gitea repository owner, can be an organization or an user.
  - hub_repoid: name of the Gitea repository.
  - hub_app_secret: the "Private token" of a user.
  - master_branch: use '*' or a list of branches to track

