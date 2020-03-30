## One time Mantis source integration setup

 - Copy the source integration plugins into mantis "plugins/" subdirectory (Source and SourceAzureDevOps folder)
 - Install both plugins with Mantis plugin admin page
 

## Azure DevOps setup

 - Login to Azure DevOps Services using the credentials of some build user or as project administrator.
 - Open *User Settings -> Personal access tokens* from the top left menu.
 - Add a *+ New Token* with at least *Read* access to *Code* and copy the token to your clipboard.

## Mantis repository setup

 - In Mantis administration, create a repository with a unique name and of type "Azure DevOps".
 - URL must be *https://dev.azure.com/{your organization}*.
 - Fill in *project name* and *repository name*.
 - Paste your access token.
 - define the branches to be visited; default: master, all: *, otherwise: comma separated list.
 - in case you connect from behind a corporate proxy you might provide its url, e.g. *http://proxy.company.corp*
  