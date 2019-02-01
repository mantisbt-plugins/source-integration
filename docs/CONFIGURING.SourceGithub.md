# SourceGithub Configuration

## Description

The **SourceGithub** extension plugin adds support for Git repositories 
hosted on [GitHub](http://github.com/), both public & private as well as 
personal & organization repositories.


## Requirements

The **SourceGithub** plugin requires Mantis 1.3.0 or later. See the 
[README](../README.md#requirements) for further information.

Ensure both the **Source** and **SourceGithub** plugins are installed.
See the [README](../README.md#installation) for overall instructions.


## Configuration

1. Click the *Repositories* link in the navigation bar.

2. In the *Create Repository* section:

   - Enter the repository name in the *Name* text field.
   - Select *GitHub* from the *Type* pop-up menu.
   - Click the *Create Repository* button.

3.  This will take you to the *Update Repository* page where you'll need to fill
    in all the details for the repository:

   - The *Name* field should be pre-populated with the name you entered in Step 3a above.

   - Paste in the GitHub repository's URL in the *URL* field 
     (e.g. `https://github.com/mantisbt-plugins/source-integration`).

   - Enter the GitHub username of the repository's owner in the *GitHub Username*
     field (e.g. "mantisbt-plugins").

   - Enter the GitHub repository's name in the *GitHub Repository Name* field
     (e.g. "source-integration").

   - If it's a public GitHub repository, you can skip the *GitHub Application
     Client ID* & *GitHub Application Secret* fields. If it's a private
     repository, you'll need to configure a GitHub Developer Application:

        1.  Visit https://github.com/settings/developers and click the *Register
            new application* button.
        2.  Enter *MantisBT Source Integration* in the *Application name* field.
        3.  Enter the URL for your Mantis installation in the *Homepage URL*
            field.
        4.  Enter the URL for your Mantis Source/oauth page (the URL with
            `/plugin.php?page=Source/oauth` appended to it; e.g. "http://mantisbt.org/bugs/plugin.php?page=Source/oauth").
        5.  Click the *Register application* button.
        6.  Switch back to your Mantis GitHub Repository configuration.
        7.  Enter your GitHub Developer Application's Client ID in the *GitHub
            Application Client ID* field.
        8.  Enter your GitHub Developer Application's Secret in the *GitHub
            Application Access Secret* field.

   - You can specify a branch or branches other than just "master" in the
     *Primary Branches* field, if you like.

   - Click the *Update Repository* button.

4.  If this is a private GitHub repository, you'll need to authorize Mantis
    to access your repository:

  - Click the *Update Repository* button.

  - Click the *Click to Authorize* button in the *GitHub Application
    Access Token* field. If successful, it will say "MantisBT is now
    authorized to access this GitHub repository."

5.  Click the *Import Everything* button to test connectivity and perform an
    initial import of the repository changesets.

    **Note:** This may take a long time or even fail for large repositories.
