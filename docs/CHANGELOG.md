# Source Integration Plugin Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/)
specification.

--------------------------------------------------------------------------------

# Releases for MantisBT 2.x

## [2.3.0] - 2019-09-06

### Fixed

- Support for BitBucket API 2.0
  [#320](https://github.com/mantisbt-plugins/source-integration/issues/320)

## [2.2.0] - 2019-03-26

Includes all changes and fixes from [1.6.0](#160---2019-01-31) and [1.6.1](#161---2019-03-26).

### Added

- GitHub: Use AJAX to automate Webhook creation
  [#302](https://github.com/mantisbt-plugins/source-integration/pull/302)
- SVN: support SVN:Log revision property changes
  [#305](https://github.com/mantisbt-plugins/source-integration/pull/305)

### Changed

- Avoid going back and forth between repository manage & update pages
  [#297](https://github.com/mantisbt-plugins/source-integration/pull/297)
- Give visual feedback that the repo was updated
  [#298](https://github.com/mantisbt-plugins/source-integration/pull/298)
- Adjust left column width on Update Repo page
  [#299](https://github.com/mantisbt-plugins/source-integration/pull/299)
- Do not use POST action on Manage Repo page Update button
  [#300](https://github.com/mantisbt-plugins/source-integration/pull/300)
- GitHub: adjust oauth authorization page for MantisBT 2.x UI
  [#293](https://github.com/mantisbt-plugins/source-integration/pull/293)
- GitHub: Use AJAX to revoke app token
  [#303](https://github.com/mantisbt-plugins/source-integration/pull/303)
- GitHub: improve documentation
  [#304](https://github.com/mantisbt-plugins/source-integration/pull/304)

### Fixed

- Display problem on narrow screens in repo_update_page
  [#296](https://github.com/mantisbt-plugins/source-integration/pull/296)
- GitHub: token remains valid if Client ID or Secret change
  [#301](https://github.com/mantisbt-plugins/source-integration/pull/301)


## [2.1.5] - 2018-09-02

Includes all changes and fixes from [1.5.9](#159---2018-09-02).

### Fixed

- Fix French regex labels
  [#285](https://github.com/mantisbt-plugins/source-integration/issues/285)

### Security

- Fix XSS in Manage Repository and Changesets List pages (CVE request pending)
  [#286](https://github.com/mantisbt-plugins/source-integration/issues/286)


## [2.1.4] - 2018-08-30

Includes all changes and fixes from [1.5.8](#158---2018-08-30).

### Changed

- Improve labels for RegEx strings
  [#283](https://github.com/mantisbt-plugins/source-integration/pull/283)
- Update Russian translations
  [#280](https://github.com/mantisbt-plugins/source-integration/pull/280)
- Cgit: Portuguese-Brazil translation
  [#267](https://github.com/mantisbt-plugins/source-integration/pull/267)
- Gitlab: Improve integration Readme
  [#278](https://github.com/mantisbt-plugins/source-integration/pull/278)


## [2.1.3] - 2018-07-30

Includes all changes and fixes from [1.5.7](#157---2018-07-30).


## [2.1.2] - 2018-06-13

Includes all changes and fixes from [1.5.6](#156---2018-06-13).

### Fixed

- HgWeb: prevent lockup and display warning when importing empty repository
  [#269](https://github.com/mantisbt-plugins/source-integration/pull/269)


## [2.1.1] - 2018-04-09

Includes all changes and fixes from [1.5.5](#155---2018-04-09).

### Changed

- Updated German translation
  [#251](https://github.com/mantisbt-plugins/source-integration/pull/251)

### Fixed

- Code cleanup


## [2.1.0] - 2017-09-17

Includes all changes and fixes from [1.5.3](#153---2017-06-12) and [1.5.4](#154---2017-09-17).

### Added

- Display linked changesets and allow adding new ones on list page
  [#202](https://github.com/mantisbt-plugins/source-integration/pull/202)
- SourceSVN documentation
  [#250](https://github.com/mantisbt-plugins/source-integration/pull/250)

### Changed

- Minimum MantisBT version increased to 2.0.1
- Search page improvements:
  increase size of 'Revision' field
  [#206](https://github.com/mantisbt-plugins/source-integration/pull/206),
  use new datetime picker
  [#223](https://github.com/mantisbt-plugins/source-integration/pull/223)
- Display text descriptions instead of raw keys on repository manage page
  [#215](https://github.com/mantisbt-plugins/source-integration/pull/215)
- Use specific error messages instead of ERROR_GENERIC
  [#203](https://github.com/mantisbt-plugins/source-integration/pull/203)
- Disable 'branch' field except for new mapping
  [#243](https://github.com/mantisbt-plugins/source-integration/pull/243)
- Only display spacer row when necessary in branch mappings list
  [#244](https://github.com/mantisbt-plugins/source-integration/pull/244)
- Show status color box next to issue id in view page
  [#234](https://github.com/mantisbt-plugins/source-integration/pull/234)
- SVN: improve error detection & handling
  [#247](https://github.com/mantisbt-plugins/source-integration/pull/247)
- WebSVN: updated German translation
  [#225](https://github.com/mantisbt-plugins/source-integration/pull/225)


## [2.0.3] - 2017-05-28

### Added

- Document requirement for cURL / shell_exec
  [#214](https://github.com/mantisbt-plugins/source-integration/issues/214)

### Fixed

- HgWeb: replace invalid function map() by array_map()
  [#213](https://github.com/mantisbt-plugins/source-integration/issues/213)
- Gitweb: can't retrieve changesets when protected by HTTP basic auth
  [#218](https://github.com/mantisbt-plugins/source-integration/issues/218)


## [2.0.2] - 2017-03-16

Includes all changes and fixes from [1.5.2](#152---2017-03-16).

### Security

- CVE-2017-6958: XSS in search page
  [#205](https://github.com/mantisbt-plugins/source-integration/issues/205),
  thanks to Dmitry Ivanov ([d1m0ck](https://twitter.com/d1m0ck))


## [2.0.1] - 2017-03-06

Includes all changes and fixes from [1.5.1](#151---2017-03-06).


## [2.0.0] - 2017-03-06

Includes all changes and fixes from [1.5.0](#150---2017-03-06).

### Fixed

- Apply Modern UI to SourceGitphp repository update page


## [2.0.0-beta.2] - 2016-11-26

### Changed

- Display repo settings as key-value instead of vardump

### Fixed

- Menu options
- PHP system notice and display of 'Array' under the manage menu items
  [#175](https://github.com/mantisbt-plugins/source-integration/issues/175)
- Broken main menu item links
  [#176](https://github.com/mantisbt-plugins/source-integration/issues/176)
- Repository list alignment of type column
- Source control username in account preferences
  [#180](https://github.com/mantisbt-plugins/source-integration/issues/180)


## [2.0.0-beta.1] - 2016-07-21

### Added

- Support for MantisBT 2.0

### Changed

- Adapt pages layout for MantisBT Modern UI

### Removed

- Support for MantisBT 1.3

--------------------------------------------------------------------------------

# Releases for MantisBT 1.3

## [1.6.1] - 2019-03-26

### Fixed

- CGit: replace invalid function map() by array_map()
  [#306](https://github.com/mantisbt-plugins/source-integration/issues/306)

## [1.6.0] - 2019-01-31

### Changed

- Github: adapt checkin following retirement of GitHub Services
  [#292](https://github.com/mantisbt-plugins/source-integration/issues/292)
- Github: support payload signature validation from webhook
  [#295](https://github.com/mantisbt-plugins/source-integration/issues/295)

## [1.5.9] - 2018-09-02

### Security

- Fix XSS in Manage Repository and Changesets List pages (CVE request pending)
  [#286](https://github.com/mantisbt-plugins/source-integration/issues/286)

## [1.5.8] - 2018-08-30

### Fixed

- Remove usage of create_function(), deprecated in PHP 7.2
  [#284](https://github.com/mantisbt-plugins/source-integration/issues/284)
- ViewVC: fix links to moved/deleted files
  [#273](https://github.com/mantisbt-plugins/source-integration/issues/273)


## [1.5.7] - 2018-07-30

### Fixed

- HgWeb: fix unsupported PCRE /J modifier on PHP < 7.2
  [#275](https://github.com/mantisbt-plugins/source-integration/pull/275)

## [1.5.6] - 2018-06-13

### Fixed

- GitLab: use API v4
  [#270](https://github.com/mantisbt-plugins/source-integration/pull/270)

## [1.5.5] - 2018-04-09

### Fixed

- HgWeb: syntax error
  [#265](https://github.com/mantisbt-plugins/source-integration/pull/265)

## [1.5.4] - 2017-09-17

### Changed

- HgWeb: allow space and unicode chars in filename
  [#219](https://github.com/mantisbt-plugins/source-integration/pull/219)

### Fixed

- Remove extra '(select one)' in mapping strategy selection list
  [#238](https://github.com/mantisbt-plugins/source-integration/issues/238)
- Change of repo name after full import
  [#245](https://github.com/mantisbt-plugins/source-integration/issues/245)
- HgWeb: fix handling of commit message lines beginning with `#`
  [#233](https://github.com/mantisbt-plugins/source-integration/issues/233)
- HgWeb: fix errors while importing the repository
  [#248](https://github.com/mantisbt-plugins/source-integration/issues/248)
  [#249](https://github.com/mantisbt-plugins/source-integration/issues/249)
- SVN: make sure svn_binary() retrieves options from SourceSVN's config
  [#241](https://github.com/mantisbt-plugins/source-integration/issues/241)

## [1.5.3] - 2017-06-12

### Fixed

- Git*, HgWeb: Fix SQL syntax error in 'import_full'
  [#221](https://github.com/mantisbt-plugins/source-integration/issues/221)
- GitLab: fix invalid diff URL
  [#227](https://github.com/mantisbt-plugins/source-integration/issues/227)
- Gitphp: replace deprecated db_query_bound() call
  [#222](https://github.com/mantisbt-plugins/source-integration/issues/222)
- HgWeb: replace invalid function map() by array_map()
  [#213](https://github.com/mantisbt-plugins/source-integration/issues/213)

## [1.5.2] - 2017-03-16

### Changed

- Source_FilterOption_Permalink() should not handle integer params as strings
  [#207](https://github.com/mantisbt-plugins/source-integration/issues/207)

### Fixed

- Changeset reference is not processed when preceded by @-mention
  [#204](https://github.com/mantisbt-plugins/source-integration/issues/204)


## [1.5.1] - 2017-03-06

### Fixed

- Bug preventing use of Git-based plugins on PHP versions < 5.6
  [#199](https://github.com/mantisbt-plugins/source-integration/issues/199)

## [1.5.0] - 2017-03-06

### Added

- Branch validation for Git-based plugins that didn't have it (Cgit, Gitweb, Gitphp)

### Changed

- Use an abstract base class for Git-based plugins (Cgit, GitHub, GitLab, Gitweb, Gitphp)


## [1.4.1] - 2017-02-22

### Changed

- Workaround for 4-bytes UTF-8 characters (e.g. emojis) in commit messages
  [#194](https://github.com/mantisbt-plugins/source-integration/issues/194)
- Github: branch validation regex now follows rules defined in git check-ref-format man page

### Fixed

- Github: handling branches containing '/'
  [#193](https://github.com/mantisbt-plugins/source-integration/issues/193)


## [1.4.0] - 2017-02-06

Includes all changes and fixes from 0.19.

Most of the changes to support MantisBT 1.3 took place in 1.3.2. The bump to
1.4.0 was made for compliance with SemVer and the new version numbering
scheme.

### Changed

- New SemVer-based version numbering scheme
- Gitphp: support for MantisBT 1.3


## [1.3.2] - 2017-02-05

### Added

- Support for MantisBT 1.3
- Gitweb: Add support for HTTP basic auth
  [#144](https://github.com/mantisbt-plugins/source-integration/issues/144)
- Support for Pull Request linking (Bitbucket, Github)
  [#116](https://github.com/mantisbt-plugins/source-integration/issues/116)
- New 'MantisSourceBase' common ancestor class
- Classes hierarchy documentation

### Changed

- Update MantisCore dependency to 1.3 for all child plugins
- Adapt pages layout for Mantis 1.3.0
- Improve layout of 'Enabled Features' in config page
- Improve bug resolution and assignment logic
  [#80](https://github.com/mantisbt-plugins/source-integration/issues/80)
  [#104](https://github.com/mantisbt-plugins/source-integration/issues/104)
- Hide edit controls for unauthorized users on changeset details page
  [#188](https://github.com/mantisbt-plugins/source-integration/issues/188)
- Plugins title prefixed with 'Source' to group them in Mantis Plugin admin page
- Set all plugins' URL to point Github's page

### Removed

- Support for MantisBT 1.2
- jQuery plugin dependency

### Fixed

- Javascript change event on search page
- Data type mismatch error on edit page
  [#134](https://github.com/mantisbt-plugins/source-integration/issues/134)
- Changeset linking
  [#146](https://github.com/mantisbt-plugins/source-integration/issues/146),
  [#161](https://github.com/mantisbt-plugins/source-integration/issues/161)
- Set issue resolution to 'fixed' when processing changesets
  [#191](https://github.com/mantisbt-plugins/source-integration/issues/191)
- Cgit: filter out decoration tag from commit message
  [#185](https://github.com/mantisbt-plugins/source-integration/issues/185)
- GitHub: system notice when authorizing application
  [#168](https://github.com/mantisbt-plugins/source-integration/issues/168)
- GitHub: allow clearing OAuth access token
  [#133](https://github.com/mantisbt-plugins/source-integration/issues/133)
- GitLab: Remove calls to deprecated helper_alternate_class()
- SVN: prevent Data Type mismatch error in config page
  [#167](https://github.com/mantisbt-plugins/source-integration/issues/167)
- SVN: force SourceSVN plugin in svn_call
  [#186](https://github.com/mantisbt-plugins/source-integration/issues/186)


## [1.3.1] - 2015-09-12

Includes all changes and fixes from master-1.2.x branch, up to commit
[92f682f3](https://github.com/mantisbt-plugins/source-integration/commit/92f682f3b296af72af6fb6d9f207ac5097cce8fe).


## [1.3.0] - 2014-11-08

### Added

- Initial and partial support for MantisBT 1.3

--------------------------------------------------------------------------------

# Legacy releases for MantisBT 1.2

## [0.19] - 2017-02-06
## [0.18] - 2013-02-22
## [0.17] - 2012-12-07
## [0.16.4] - 2011-07-21
## [0.16.3] - 2011-06-06
## [0.16.2] - 2010-06-27
## [0.16.1] - 2010-04-14
## [0.16] - 2010-04-12
## [0.15] - 2010-04-01
## [0.14] - 2010-01-26
## [0.13.2] - 2009-04-06
## [0.13.1] - 2009-04-01
## [0.13.0] - 2008-10-28
## [0.12a] - 2008-07-29
## [0.12] - 2008-07-29
## [0.11a] - 2008-06-30
## [0.11] - 2008-06-13
## [0.10] - 2008-06-05
## [0.9c] - 2008-04-18
## [0.9b] - 2008-04-12
## [0.9a] - 2008-04-11
## [0.9] - 2008-04-11


[Unreleased 2.x]: https://github.com/mantisbt-plugins/source-integration/compare/v2.3.0...HEAD

[2.3.0]: https://github.com/mantisbt-plugins/source-integration/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.5...v2.2.0
[2.1.5]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.4...v2.1.5
[2.1.4]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.3...v2.1.4
[2.1.3]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.2...v2.1.3
[2.1.2]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/mantisbt-plugins/source-integration/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.3...v2.1.0
[2.0.3]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.2...v2.0.3
[2.0.2]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.0-beta.2...v2.0.0
[2.0.0-beta.2]: https://github.com/mantisbt-plugins/source-integration/compare/v2.0.0-beta.1...v2.0.0-beta.2
[2.0.0-beta.1]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.2...v2.0.0-beta.1

[Unreleased 1.x]: https://github.com/mantisbt-plugins/source-integration/compare/v1.6.1...master-1.3.x

[1.6.1]: https://github.com/mantisbt-plugins/source-integration/compare/v1.6.0...v1.6.1
[1.6.0]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.9...v1.6.0
[1.5.9]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.8...v1.5.9
[1.5.8]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.7...v1.5.8
[1.5.7]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.6...v1.5.7
[1.5.6]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.5...v1.5.6
[1.5.5]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.4...v1.5.5
[1.5.4]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.3...v1.5.4
[1.5.3]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.2...v1.5.3
[1.5.2]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.1...v1.5.2
[1.5.1]: https://github.com/mantisbt-plugins/source-integration/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/mantisbt-plugins/source-integration/compare/v1.4.1...v1.5.0
[1.4.1]: https://github.com/mantisbt-plugins/source-integration/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/mantisbt-plugins/source-integration/compare/v1.3.2...v1.4.0
[1.3.2]: https://github.com/mantisbt-plugins/source-integration/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/mantisbt-plugins/source-integration/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/mantisbt-plugins/source-integration/compare/v0.19...v1.3.0

[0.19]: https://github.com/mantisbt-plugins/source-integration/compare/v0.18...v0.19
[0.18]: https://github.com/mantisbt-plugins/source-integration/compare/v0.17...v0.18
[0.17]: https://github.com/mantisbt-plugins/source-integration/compare/v0.16.4...v0.17
[0.16.4]: https://github.com/mantisbt-plugins/source-integration/compare/v0.16.3...v0.16.4
[0.16.3]: https://github.com/mantisbt-plugins/source-integration/compare/v0.16.2...v0.16.3
[0.16.2]: https://github.com/mantisbt-plugins/source-integration/compare/v0.16.1...v0.16.2
[0.16.1]: https://github.com/mantisbt-plugins/source-integration/compare/v0.16...v0.16.1
[0.16]: https://github.com/mantisbt-plugins/source-integration/compare/v0.15...v0.16
[0.15]: https://github.com/mantisbt-plugins/source-integration/compare/v0.14...v0.15
[0.14]: https://github.com/mantisbt-plugins/source-integration/compare/release-0.13.2...v0.14
[0.13.2]: https://github.com/mantisbt-plugins/source-integration/compare/release-0.13.1...release-0.13.2
[0.13.1]: https://github.com/mantisbt-plugins/source-integration/compare/release-0.13.0...release-0.13.1
[0.13.0]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.12a...release-0.13.0
[0.12a]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.12...Source-0.12a
[0.12]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.11a...Source-0.12
[0.11a]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.11...Source-0.11a
[0.11]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.10...Source-0.11
[0.10]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.9c...Source-0.10
[0.9c]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.9b...Source-0.9c
[0.9b]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.9a...Source-0.9b
[0.9a]: https://github.com/mantisbt-plugins/source-integration/compare/Source-0.9...Source-0.9a
[0.9]: https://github.com/mantisbt-plugins/source-integration/compare/8070579680bb2d56651d67f69755b879121917f6...Source-0.9
