# Source Integration Plugin classes hierarchy

## Plugin classes

- **MantisSourceBase** extends MantisPlugin
    - **SourcePlugin** - The Source Integration plugin
    - **SourceIntegrationPlugin** - A child plugin for *Source* that handles 
      integration with the MantisBT UI 
    - **MantisSourcePlugin** - Abstract base class for VCS-specific plugins
        - **SourceGenericPlugin**
        - **MantisSourceGitBasePlugin** - Abstract class for git-based plugins
            - **SourceBitBucketPlugin**
            - **SourceCgitPlugin**
            - **SourceGithubPlugin**
            - **SourceGitlabPlugin**
            - **SourceGitphpPlugin**
            - **SourceGitwebPlugin**
        - **SourceHgWebPlugin**
        - **SourceSVNPlugin** Subversion and derivatives
            - **SourceSFSVNPlugin**
            - **SourceViewVCPlugin**
            - **SourceWebSVNPlugin**

## Helper classes

- **SourceChangeset**
- **SourceRelatedChangesetsColumn** extends MantisColumn
- **SourceFile**
- **SourceFilter**
- **SourceFilterOption**
- **SourceMapping**
- **SourceRepo**
- **SourceUser**
- **SourceVCS**
- **SourceVCSWrapper**
