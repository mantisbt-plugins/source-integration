
# SourceGithub Configuration

## Description

The **SourceGithub** plugin adds support for Git repositories hosted on 
[GitHub](http://github.com/), both public & private (presonal & organization) 
repositories.

**NOTE:** As of this writing the **SourceGithub** plugin requires either Mantis
1.2.16 (not yet released) or Mantis 1.2.15 with the 
[mantisbt/mantisbt@8df9d5f](http://github.com/mantisbt/mantisbt/commit/8df9d5fa221bb85f9f8c1ca6b698d75b740d6449)
patch applied. This is due to a new requirement in GitHub API v3 ([User Agent 
header is mandatory for all API
requests](http://developer.github.com/changes/2013-04-24-user-agent-required/))
enforced as of 2013-04-24.

##

1. Ensure both the **Source** and **SourceGithub** plugins are installed. See 
the [README](../README.md#installation) for overall instructions.

2. Click the "Repositories" link in the navigation bar.

3. In the "Create Repository" section:
    
    a. Enter the repository name in the "Name" text field.
    
    b. Select "GitHub" from the "Type" pop-up menu.
    
    c. Click the "Create Repository" button.

4. This will take you to the "Update Repository" page where you'll need to fill
    in all the details for the repository:
    
    a. The "Name" field should be pre-populated with the name you entered in
        Step 3a.
    
    b. Paste in the GitHub repositories URL in the "URL" field (e.g. 
        `https://github.com/mantisbt-plugins/source-integration`).
    
    c. Enter the GitHub username of the repository's owner in the "GitHub Username"
        field (e.g. "mantisbt-plugins").
    
    d. Enter the GitHub repository's name in the "GitHub Repository Name" field 
        (e.g. "source-integration").
    
    e. If it's a public GitHub repository, you can skip the "GitHub Application
        Client ID" & "GitHub Application Secret" fields. If it's a private 
        repository, you'll need to configure a GitHub Developer Application:
        
        I. Visit https://github.com/settings/developers and click the "Register
            new application" button.
        
        II. Enter "MantisBT Source Integration" in the "Application name" field.
        
        III. Enter the URL for your Mantis installation in the "Homepage URL" 
            field.
        
        IV. Enter the URL for your Mantis Source/oauth page (the URL with
            "/plugin.php?page=Source/oauth" appended to it; e.g. "http://mantisbt.org/bugs/plugin.php?page=Source/oauth").
        
        V. Click the "Register application" button.
        
        VI. Switch back to your Mantis GitHub Repository configuration.
        
        VII. Enter your GitHub Developer Application's Client ID in the "GitHub 
            Application Client ID" field.
        
        VIII. Enter your GitHub Developer Application's Secret in the "GitHub 
            Application Access Secret" field.
    
    f. You can specify a branch or branches other than just "master" in the 
        "Primary Branches" field, if you like.
    
    g. Click the "Update Repository" button.
    
5. If this is a private GitHub repository, you'll need to authorize Mantis
    to access your repository, so do the following:
    
    a. Click the "Update Repository" button.
    
    b. Click the "Click to Authorize" button in the "GitHub Application 
        Access Token" field. If successful, it will say "MantisBT is now
        authorized to access this GitHub repository."
    
6. Click the "Import Everything" button to test connectivity and perform an 
    initial import of the repository changesets. **Note:** This may take a
    long time or fail for large repositories.
