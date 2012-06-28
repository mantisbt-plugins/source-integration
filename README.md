
# Mantis Source Integration

Copyright (C) 2012 John Reese

## Description

This plugin provides a flexible framework for fully integrating any
sort of version control system with Mantis, including support for
multiple repositories, changesets or revisions, and file path tracking.
It has many features already, such as importing repository history and
optional support for remote check-ins or imports, and utilizes a 
class-based API to access objects in the database.

Initial support is included, using extension plugins, for Subversion
repositories using [WebSVN](http://www.websvn.info/), and Git repositories 
hosted on [GitHub](http://github.com/). Support for more source control 
tools is planned, but should be rather  straightforward to implement, due 
to the flexibility inherent in the integration framework and API.

## Requirements

The Source Integration framework requires [Mantis](http://www.mantisbt.org/) 
version 1.2.0 or higher.

## Installation

1. Download or clone a copy of the [Source Integration source
   code](http://github.com/mantisbt-plugins/source-integration/).

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

7. Go to "Repositories" and enter your repository name, select the
   repository type, and click "Create Repository" to begin adding your first
   repository.

8. Once configured, click the "Return to Repository" link and click either
   the "Import Everything" or "Import Newest Data" button to perform initial 
   import of repository changesets and verify configuration.

9. Once satisfied that your repository is configured & working correctly,
   you can automate the import of new changesets by configuring a cron
   job on the web server where your Mantis installation resides, as follows:
   
        curl "http://host.domain.tld/mantisbt/plugin.php?page=Source/import&id=all"
   
   This will import new changesets for all configured repositories.

10. Add additional repositories as needed.

## Support

Problems or questions dealing with use and installation should be
directed to the MantisBT IRC channel #mantishelp:

  irc://freenode.net/mantishelp

Bug reports or fixes are highly encouraged, and should be directed to
the bug tracker on GitHub:

  http://github.com/mantisbt-plugins/source-integration/issues

The latest source code can be found on GitHub:

  http://github.com/mantisbt-plugins/source-integration

Original project and documentation can be found on noswap.com:

  http://noswap.com/projects/source-integration/