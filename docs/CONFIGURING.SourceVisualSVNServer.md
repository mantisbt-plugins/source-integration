# SourceVisualSVNServer Configuration

## Description

The **SourceVisualSVNServer** extension plugin adds support for 
adding links from MantisBT to the VisualSVN Server web interface,
in addition to the basic **SourceSVN** features.

## Requirements

The **SourceVisualSVNServer** plugin requires Mantis 1.2.x. See the
[README](../README.md#requirements) for further information.

Ensure that all of the following plugins are installed:
* **Source**
* **SourceSVN**
* **SourceVisualSVNServer**

See the [README](../README.md#installation) for overall instructions
with regards to installing SourceIntegration plugins.

## Configuration of SVN

See [SourceSVN configuration](CONFIGURING.SourceSVN.md#configuration-of-the-plugin).

## Configuration of a Repository

1. Click the *Repositories* link in the navigation bar.

2. In the *Create Repository* section:

   - Enter the repository name in the *Name* text field.
   - Select *VisualSVN Server* from the *Type* pop-up menu.
   - Click the *Create Repository* button.

3. This will take you to the *Update Repository* page where you'll need to fill 
   in all the details for the repository:

   - The *Name* field should be pre-populated with the name you entered in Step 2a above.
   - Paste in the SVN repository's URL in the *URL* field 
     (e.g. `https://localhost.localdomain/svn/myrepo`).
   - Configure the [standard SVN repository settings](CONFIGURING.SourceSVN.md#configuration-of-a-repository).
   - If necessary, update the repository URL path prefix to match the configuration of the VisualSVN Server (default `svn`).
     The plugin uses this prefix to locate the corresponding web interface URLs for the repository.
   - Click the *Update Repository* button.

4. Click the *Import Everything* button to test connectivity and perform an 
   initial import of the repository changesets.

   **Note:** This may take a long time or even fail for large repositories.
