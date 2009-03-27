
## Mantis Source Integration

Copyright (C) 2008	John Reese, LeetCode.net

### Description

This plugin provides a flexible framework for fully integrating any
sort of version control system with Mantis, including support for
multiple repositories, changesets or revisions, and file path tracking.
It has many features already, such as importing repository history and
optional support for remote check-ins or imports, and utilizes a 
class-based API to access objects in the database.

Initial support is included, using extension plugins, for Subversion
repositories using WebSVN, and Git repositories hosted on GitHub.
Support for more source control tools is planned, but should be rather 
straightforward to implement, due to the flexibility inherent in the 
integration framework and API.

### Requirements

The Source Integration framework requires Mantis version 1.2.0 or
higher, and requires the following additional plugins:

- [Meta, version 0.1+](http://git.mantisforge.org/w/meta.git)

### Installation

For basic instruction on getting started with the plugin framework,
see the article on LeetCode.net covering Git and Subversion:
  http://leetcode.net/blog/2009/01/integrating-git-svn-with-mantisbt/

### Support

Problems or questions dealing with use and installation should be
directed to the MantisBT IRC channel #mantishelp:

  irc://freenode.net/mantishelp
  http://leetcode.net/mantis/plugin.php?page=Mibbit/irc

Bug reports or fixes are highly encouraged, and should be directed to
the bug tracker on LeetCode.net:

  http://leetcode.net/mantis

The latest source code can be found on MantisForge.org:

  http://git.mantisforge.org/w/source-integration.git

