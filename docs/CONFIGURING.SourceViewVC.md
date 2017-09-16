# SourceViewVC Configuration

## Description

The **SourceViewVC** extension plugin adds support for SVN repositories
with a [ViewVC](http://www.viewvc.org/) front-end.

## Requirements

The **SourceViewVC** plugin requires Mantis 1.2.16. See the
[README](../README.md#requirements) for further information.

Ensure that all of the following plugins are installed:
* **Source**
* **SourceSVN**
* **SourceViewVC**

See the [README](../README.md#installation) for overall instructions.

## Configuration of SVN

See [SourceSVN configuration](CONFIGURING.SourceSVN.md#configuration-of-the-plugin).

## Configuration of a Repository

1. Click the *Repositories* link in the navigation bar.

2. In the *Create Repository* section:

   - Enter the repository name in the *Name* text field.
   - Select *ViewVC* from the *Type* pop-up menu.
   - Click the *Create Repository* button.

3. This will take you to the *Update Repository* page where you'll need to fill 
   in all the details for the repository:

   - The *Name* field should be pre-populated with the name you entered in Step 2a above.
   - Paste in the SVN repository's URL in the *URL* field 
     (e.g. `https://localhost.localdomain/repos/myrepo` or `file:///var/repos/myrepo`).
   - Paste in the ViewVC installation's root URL in the *ViewVC URL* field 
     (e.g. `http://viewvc-server/viewvc/`).
   - Enter the name of the SVN repository, as it appears in the list seen in 
     ViewVC, in the *ViewVC Name* field (e.g. `myrepo`).
   - If the ViewVC installation has the `root_as_url_component` option enabled 
     (see the `viewvc.conf` file) then enable the *ViewVC Root As URL Component Enabled?* 
     field.
   - If the ViewVC installation has the checkout view enabled (the `allowed_views` 
     field list includes `co` in the `viewvc.conf` file) then check the 
     *ViewVC Checkout View Enabled?* field.
   - Enter the username of a user which has read access to the SVN repository in 
     the *SVN Username* field (e.g. "repo-user").
   - Enter the password for the user in the *SVN Password* field (e.g. "Sup4rSecre7!").
   - If your repository is configured with the standard `trunk`, `branches` & 
     `tags` folders at the top-level, select the *Standard Repository* field, 
     otherwise enter the appropriate paths into the *Trunk Path*, *Branch Path* 
     and *Tag Path* fields.
   - Click the *Update Repository* button.

4. Click the *Import Everything* button to test connectivity and perform an 
   initial import of the repository changesets.

   **Note:** This may take a long time or even fail for large repositories.
