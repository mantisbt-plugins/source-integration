# Mantis Source Integration

[![Gitter](https://img.shields.io/gitter/room/mantisbt-plugins/source-integration.svg)](https://gitter.im/mantisbt-plugins/source-integration)

Copyright (c) 2008 - 2012  John Reese - https://noswap.com  
Copyright (c) 2012 - 2020  MantisBT Team - mantisbt-dev@lists.sourceforge.net

Released under the [MIT license](https://opensource.org/licenses/MIT)


## Description

The **Source** plugin provides a flexible framework for fully integrating any
sort of Version Control System (VCS) with Mantis, including support for
multiple repositories, changesets or revisions, and file path tracking.
It has many features already, such as importing repository history and
optional support for remote check-ins or imports, and utilizes a
class-based API to access objects in the database.

Initial support is included for [Subversion](https://subversion.apache.org/)
and [Git](https://git-scm.com/) repositories using the following extension
plugins:

* **SourceBitBucket**: Git repositories hosted on [BitBucket](https://bitbucket.org/).
* **SourceCgit**: Git repositories accessible via a
  [cgit](https://git.zx2c4.com/cgit/about/) web frontend installation.
* **SourceGithub**: Git repositories hosted on [GitHub](https://github.com/).
* **SourceGitlab**: Git repositories hosted on [GitLab](https://about.gitlab.com/).
* **SourceAzureDevOps**: Git repositories hosted on [Azure DevOps Services Repos](https://azure.microsoft.com/en-us/services/devops/).
* **SourceGitphp**: Git repositories accessible via a
  [Gitphp](https://gitphp.org/) web frontend installation.
* **SourceGitweb**: Git repositories accessible via a
  [GitWeb](https://git.wiki.kernel.org/index.php/Gitweb) web frontend
  installation.
* **SourceHgWeb**: Mercurial repositories accessible via a
  [HgWeb](https://www.mercurial-scm.org/wiki/PublishingRepositories#hgweb)
  frontend installation.
* **SourceSFSVN**: SVN repositories hosted on
  [SourceForge](https://sourceforge.net/).
* **SourceSVN**: SVN repositories locally accessible by the SVN binaries.
* **SourceViewVC**: SVN repositories accessible via a
  [ViewVC](http://www.viewvc.org/) web frontend installation.
* **SourceVisualSVNServer**: SVN repositories hosted on a 
  [VisualSVN Server](https://www.visualsvn.com/server/) installation,
  with support for URL linking from MantisBT to VisualSVN Server's built-in web frontend.
* **SourceWebSVN**: SVN repositories accessible via a
  [WebSVN](https://websvnphp.github.io/) web frontend installation.

Support for additional source control tools should be rather
straightforward to implement due to the flexibility inherent in the
integration framework and API.

## Requirements

The Source Integration framework requires **[MantisBT](https://mantisbt.org/)
version 2.21.0** or higher.

### VCS plugins

Some VCS plugins have additional, specific requirements:

- **SourceBitBucket** requires the 
  [PHP Curl extension](https://www.php.net/book.curl), or the ability to execute  
  system calls (via [shell_exec](https://www.php.net/function.shell-exec)).


## Installation

### Compatibility

The Source Integration framework's version numbering follows
[Semantic Versioning](https://semver.org/). Major version increments indicate a
change in the minimum required MantisBT version.

See the [Change log](docs/CHANGELOG.md) for detailed revision history.

Depending on which version of MantisBT you are using, please make sure to
get the appropriate version of the source code.
Use [release tags](https://github.com/mantisbt-plugins/source-integration/releases),
or the relevant branch in the Plugin's GitHub repository, as per the table below:

MantisBT version | Tags | Branch | Notes
:---:|---|---|---
2.x   | v2.* | [master](https://github.com/mantisbt-plugins/source-integration/archive/master.zip) | **Current release**
1.3.x | v1.* | [master-1.3.x](https://github.com/mantisbt-plugins/source-integration/archive/master-1.3.x.zip) | Support ended 2020-12-31
1.2.x | v0.* | [master-1.2.x](https://github.com/mantisbt-plugins/source-integration/archive/master-1.2.x.zip) | Support ended 2017-06-30


### Setup instructions

1. Download the appropriate version (see [Compatibility section](#compatibility) above)
   or clone a copy of the [source code](https://github.com/mantisbt-plugins/source-integration/)
   and checkout the correct branch.

2. Copy the primary Source plugin (the `Source/` directory) into your Mantis
   installation's `plugins/` directory.

3. Copy all the remaining plugins, or just the appropriate ones for your
   repositories, into your Mantis installation's `plugins/` directory.

4. While logged into your Mantis installation as an administrator, go to
   'Manage' -> "Manage Plugins".

5. In the "Available Plugins" list, you'll find the "Source Control
   Integration" and additional plugins:

    a. First, click the "Install" link for the "Source Control Integration"
       plugin.

    b. Next, click the "Install" link next to any additional Source Control
       plugins appropriate for your repositories.

6. Click on the "Source Control Integration" plugin to configure it.

   NOTE: an API Key must be set up to import changesets via shell.
   To generate a random key, run

        openssl rand -hex 12

7. Go to "Repositories" and enter your repository name, select the
   repository type, and click "Create Repository" to begin adding your first
   repository.

8. Configure the repository, following the specific documentation for the
   relevant plugin extension:

    * [SourceGithub](docs/CONFIGURING.SourceGithub.md)
    * [SourceGitlab](SourceGitlab/README.md)
    * [SourceAzureDevOps](docs/CONFIGURING.SourceAzureDevOps.md)
    * [SourceViewVC](docs/CONFIGURING.SourceViewVC.md)
    * [SourceSVN](docs/CONFIGURING.SourceSVN.md)
    * [SourceVisualSVNServer](docs/CONFIGURING.SourceVisualSVNServer.md)

9. Once configured, click the "Return to Repository" link and click either
   the "Import Everything" or "Import Newest Data" button to perform initial
   import of repository changesets and verify configuration.

10. Once satisfied that your repository is configured & working correctly,
    you can automate the import of new changesets by configuring a cron
    job on the web server where your Mantis installation resides, as follows:

    * import via curl (could run into timeouts on large repositories,
      or block your webserver)

            curl "http://host.domain.tld/mantisbt/plugin.php?page=Source/import&id=all&api_key=<YOUR_API_KEY>"

    * import via shell

            php-cgi -f plugin.php page=Source/import id=all api_key=<YOUR_API_KEY>

      Please be aware of the [difference between `php-cgi` and `php-cli`](http://www.php-cli.com/php-cli-cgi.shtml).
      The import *won't run* with php-cli.

    This will import new changesets for all configured repositories.

11. You can also configure event-driven import of new changesets. Many source control 
    systems support configurable hooks or triggers which can be used to notify the 
    **Source** plugin that new commits or revisions are available for import. This 
    improves user experience by eliminating delays between source control commits and 
    MantisBT state updates.

    Refer to the configuration documentation for the relevant plugin extension(s) for more 
    information.

12. Add additional repositories as needed.

## Support

The following support channels are available if you wish to file a
[bug report](https://github.com/mantisbt-plugins/source-integration/issues/new),
or have questions related to use and installation:

  - [GitHub issues tracker](https://github.com/mantisbt-plugins/source-integration/issues)
  - [Gitter chat room](https://gitter.im/mantisbt-plugins/source-integration)
  - If you feel lucky you may also want to try the legacy
    [#mantisbt IRC channel](https://webchat.freenode.net/?channels=%23mantisbt)
    on Freenode (irc://freenode.net/mantisbt)
    but since hardly anyone goes there nowadays, you may not get any response.

All code contributions (bug fixes, new features and enhancements, additional
VCS integration plugins) are welcome and highly encouraged, preferably as a
[Pull Request](https://github.com/mantisbt-plugins/source-integration/compare).

The latest source code is available on
[GitHub](https://github.com/mantisbt-plugins/source-integration);
John Reese's original project documentation can be found on his website,
[noswap.com](https://noswap.com/projects/source-integration/).
