
## Mantis Source Integration

Copyright (C) 2008	John Reese

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

- [Meta, version 0.1+](http://github.com/jreese/mantis-forge)

### Support

Bug reports or fixes are highly encouraged, as are contributions via
GitHub's fork/pull-request methods.  Bug reports should be directed to
the bug tracker located at: http://leetcode.net/mantis

