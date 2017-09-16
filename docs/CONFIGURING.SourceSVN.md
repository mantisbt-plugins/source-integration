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
| SVN: Use Windows 'start' | |

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
     *Standard Repository option
   - If you use a non-standard repository layout, enter the path to the *trunk*, 
     *branches* and *tags* directories into the following 3 option fields, e.g.  
     `/my_new_product/trunk`, `/my_new_product/branches`, `/my_new_product/tags`.  
     See the [SVN book](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.maint.html) 
     for more details of repository layouts 
   - If you use a non-standard repository layout and you want to ignore commits 
     to the repository that are not changing files within the *Trunk Path*, 
     *Branch Path* or   *Tag Path* directories (which is the most likely case), 
     then enable the *Ignore Other Paths* option.
   - Click the *Update Repository* button.

4. Click the *Import Everything* button to test connectivity and perform an 
   initial import of the repository changesets.

   **Note:** This may take a long time or even fail for large repositories.
