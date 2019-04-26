# SourceSVN Configuration

## Description

The **SourceSVN** extension plugin adds support for SVN repositories
without any specific front end.  See plugins such as SourceViewVC
and SourceWebSVN if you are using these front ends.

## Requirements

The **SourceSVN** plugin requires Mantis 1.2.x. See the
[README](../README.md#requirements) for further information.

Ensure that all of the following plugins are installed:
* **Source**
* **SourceSVN**

See the [README](../README.md#installation) for overall instructions
with regard to installing SourceIntegration plugins.

## Configuration of the Plugin

1. Click the *Manage* link in the navigation bar.

2. Click the *Manage Plugins* link in the management navigation bar.

3. Click the *Source Control Integration* link.

4. Scroll down the page until you see the section *Source Subversion Integration*.  
   There are currently 4 options to configure.

| Option                   | Notes          |
| ------------------------ | -------------- |
| SVN: Path to binary      | This should be the directory which contains the `svn` (or `svn.exe` on Windows) executable |
| SVN: Command arguments   | List any command arguments which always need to be supplied when calling the SVN binary.  If you are hosting on IIS, it's likely that the worker process will have no home directory, which will cause SVN to throw an error.  You can avoid this by creating an empty directory & entering `--config-dir c:\path\to\empty\dir` within this option field |
| SVN: Trust All SSL Certs | Enable this if your SVN repository is hosted on a service that uses a self-signed certificate |
| SVN: Use Windows 'start' | When enabled on Windows, SVN is invoked with the command `start /B /D "path\to\binary\executable" svn.exe [args]` rather than `path\to\binary\executable\svn.exe [args]`. This is useful for avoiding problems with spaces in the SVN executable path, e.g. `C:\Program Files\` |

5. Click *Update Configuration* when you're done.

## Configuration of a Repository

1. Click the *Repositories* link in the navigation bar.

2. In the *Create Repository* section:

   - Enter the repository name in the *Name* text field.
   - Select *SVN* from the *Type* pop-up menu.
   - Click the *Create Repository* button.

3. This will take you to the *Update Repository* page where you'll need to fill 
   in all the details for the repository:

   - The *Name* field should be pre-populated with the name you entered in Step 2a above.
   - Paste in the SVN repository's URL in the *URL* field (e.g. 
     `https://localhost.localdomain/repos/myrepo` or `file:///var/repos/myrepo`).
   - If access controls are configured on your SVN repository such that anonymous 
     read access is not permitted, within the *SVN Username* and *SVN Password*, 
     enter appropriate credentials that have read access to the repo.
   - If you use a "standard" repository layout, where the top-level of the 
     repository contains `/trunk`, `/branches` and `/tags`, then enable the 
     *Standard Repository* option
   - If your SVN repository contains multiple projects, as long as each project contains
     the standard `/trunk`, `/branches` and `/tags` directories, the entire repository 
     can be configured as a single instance using the *Standard Repository* option. 
     Configure the root directory of the repository in the *URL* field.
     When processing changesets, any path that contains `/trunk/` will be treated as a 
     trunk commit. Paths that do not contain `/trunk/` and do contain `/tags/TAG_NAME_HERE/` 
     or `/branches/BRANCH_NAME_HERE/` will be recognised as a tag or branch, and the name 
     will be extracted and applied to the changeset. Commits that include files from 
     multiple SVN *trunk/tags/branches* directories will be tagged with a branch based 
     on the first *trunk/tags/branches* directory encountered in the commit. Commits 
     where no path includes any of the standard directories will be ignored.
   - If you use a non-standard repository layout, enter the path to the *trunk*, 
     *branches* and *tags* directories into the following 3 option fields, e.g.  
     `/my_new_product/trunk`, `/my_new_product/branches`, `/my_new_product/tags`.  
     See the [SVN book](http://svnbook.red-bean.com/en/1.7/svn.branchmerge.maint.html) 
     for more details of repository layouts 
   - If you use a non-standard repository layout and you want to ignore commits 
     to the repository that are not changing files within the *Trunk Path*, 
     *Branch Path* or *Tag Path* directories (which is the most likely case), 
     then enable the *Ignore Other Paths* option.
   - Click the *Update Repository* button.

4. Click the *Import Everything* button to test connectivity and perform an 
   initial import of the repository changesets.

   **Note:** This may take a long time or even fail for large repositories.

5. Set up SVN repository hooks. 
   
   - [Repository hooks](http://svnbook.red-bean.com/en/1.7/svn.reposadmin.create.html#svn.reposadmin.create.hooks)
     allow the SVN server to trigger MantisBT to process new commits on demand, rather 
     than based on a polling schedule. Refer to your SVN server's documentation for more 
     information on how to implement the hooks.
      - Use the [post-commit](http://svnbook.red-bean.com/en/1.7/svn.ref.reposhooks.post-commit.html) 
        hook to process new commits
      - Use the [post-revprop-change](http://svnbook.red-bean.com/en/1.7/svn.ref.reposhooks.post-revprop-change.html) 
        hook to process retroactive log message edits. 
   - The [SourceSVN folder](../SourceSVN) 
     contains sample code for triggering the MantisBT updates.
      - For Unix-compatible SVN server hosts:
        - `post-commit.tmpl` and `post-revprop-change.tmpl` are sample shell scripts for
          triggering the appropriate source-integration APIs.
      - For Windows SVN server hosts:
        - `post-commit.ps1` and `post-revprop-change.ps1` are PowerShell scripts which implement 
          similar functionality to the Unix sample shell scripts.
        - `post-commit.bat` and `post-revprop-change.bat` are simple batch files which pass the
          hook parameters through to the corresponding PowerShell scripts.

## Windows integrated authentication

If you are using VisualSVN Server or another product which supports NTLM/Kerberos-based 
authentication over HTTPS, and you are running MantisBT on a Windows server, you may 
find that the *SVN Username* and *SVN Password* configuration settings are ignored. 
If the SVN client is able to establish Windows authentication while setting up the SSL 
connection, the "basic" credentials supplied by these parameters are not used. 
This occurs automatically at the SSL layer and the SVN client cannot override it.

In this situation, it is recommended to ensure that the web server running MantisBT is 
configured to run as a Windows domain user or managed service account, and grant that 
domain user read access to any SVN repositories that require MantisBT integration.