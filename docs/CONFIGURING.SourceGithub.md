# SourceGithub Configuration

## Description

The **SourceGithub** extension plugin adds support for both public & private as
well as personal & organization Git repositories hosted on
[GitHub](http://github.com/).


## Requirements

The **SourceGithub** plugin requires MantisBT version 2.3.0 or later. See the
[README](../README.md#requirements) for further information.

Ensure both the **Source** and **SourceGithub** plugins are properly installed.
See the [README](../README.md#installation) for overall instructions.


## Configuration

### Create a GitHub OAuth App

Setting up an OAuth app is required to authenticate the Source Integration
plugin and authorize it to access Private repositories.

It also allows increasing the GitHub API [Rate Limit](https://developer.github.com/v3/#rate-limiting)
when importing changesets to 5'000 requests per hour. Unauthenticated requests
are limited to 60 calls per hour, which would only be sufficient for very small
repositories.

Finally, it facilitates the creation of a Webhook from within the plugin's
repository management page, which lets GitHub trigger automatic import and
processing of commits, as they are pushed to the repository.

1. Navigate to your [GitHub Developer settings](https://github.com/settings/developers) page

2. Click on the [New OAuth App](https://github.com/settings/applications/new) button

3. Fill in the form
   - Give a name to your app, e.g. `MantisBT Source Integration`
   - Set _Homepage URL_ as appropriate (e.g. point to your website or your
	 MantisBT instance)
   - Provide an _Application description_ if necessary
   - _Authorization callback URL_ should point to your MantisBT instance's
	 base URL, e.g. `https://path.to/mantis/`

4. Click on **Register application**

5. Take note of the generated _Client ID_ and _Client Secret_, you will need
   them later on

**NOTE:** a single OAuth app is sufficient to handle all the GitHub repositories
configured in a given MantisBT instance.


### Create a Repository

1. Click the *Repositories* link in the navigation bar.

2. Fill the form in the *Create Repository* section:
   - Enter the repository's name in the *Name* text field
   - Select *GitHub* from the *Type* pop-up menu

3. Click the **Create Repository** button.

4. This will take you to the *Update Repository* page, where you'll need to fill
   in all the details for the repository:

   - The *Name* will be pre-populated with whatever you entered in step 2 above.
   - Paste in the GitHub repository's URL in the *URL* field
	 (e.g. `https://github.com/mantisbt-plugins/source-integration`).
   - Enter the name of the repository's owner in *GitHub Username*
	 (e.g. `mantisbt-plugins`)
   - Enter the name of the GitHub repository in the *GitHub Repository Name*
	 field (e.g. `source-integration`)
   - Paste the your OAuth app's _Client ID_ and _Client Secret_ in the
	 GitHub Application Client ID and Secret fields
   - The _GitHub Webhook Secret_ is not mandatory, but provides additional
	 security as it will allows the plugin to validate the payload received
	 from the GitHub Webhook to ensure it's legit
   - By default, the *Primary Branches* is set to `master`, but you can specify
	 another branch or additional ones (comma-separated), or use `*` for all
	 branches

5. Click the **Update Repository** button to save your changes


### Authorize the plugin to access GitHub

See the [Create a GitHub OAuth App section](#create-a-github-oauth-app) for an
explanation of why this step is needed.

1. In the Update Repository page, next to _GitHub Application Access Token_,
   click the **Authorize** button

2. The plugin will redirect to a GitHub page asking for authorization to
   access your personal public and private reporitories, as well as the
   organizations you are a member of. Click **Authorize**

   **NOTE:** Full access is requested, as GitHub does not allow fine-grained
   control over permissions for OAuth applications. However, the SourceGithub
   plugin will only:
   - read code (branches and commits)
   - create a _push_ event Webhook (see [below](#setup-the-webhook))

3. You will be redirected back to the SourceGithub plugin, where you should see
   a confirmation message informing you that authorization has been granted.

4. Click **Proceed**. This will take you back to the Update Repository page,
   where you should see _Authorized_ next to _GitHub Application Access Token_.

**NOTE:** You may revoke the authorization at any time by clicking on the
_Update Repository_ page's **Revoke** button. Also, for obvious security
reasons, the Token will automatically be deleted if the Client ID or Secret are
changed.


### Setup the Webhook

Use of a push event Webhook is strongly recommended, as it will let GitHub
automatically trigger the processing of new commits, whenever they are pushed
to the repository.

1. In the Update Repository page, next to _GitHub Webhook Secret_, click the
   **Create Webhook** button

2. You should see a confirmation message, indicating that the Webhook was
   created successfully. If not, an error message will be displayed, and you
   may find additional information about whatever prevented the operation to
   succeed in the browser's console.

   **NOTE:** The most common cause for failure is when the Webhook already
   exists, and could therefore not be created. In that case, the error message
   becomes a hyperlink, which will take you to the Webhook's config page on
   GitHub.

3. Click **Back to Repository**

If you prefer, it is also possible to setup the webhook manually:

1. Go to the repository's settings page (e.g. ), and select Webhooks

2. Click on **Add Webhook**

3. Fill in the form
   - Set _Payload URL_ to
	 `https://path.to/mantis/plugin.php?page=Source/checkin&api_key=XXXX`,
	 where XXXX is your _API Key_, as defined in the Source Integration plugin's
	 Configuration page.
   -  _Content type_ must be set to `application/json`
   - _Secret_ must match the _GitHub Webhook Secret_ defined in step 4 of the
	 [Create a Repository](#create-a-repository) section above
   - Select _Just the push event_
   - Make sure the _Active_ checkbox is checked

4. Click on **Add Webhook**

At this point a test payload will be sent to your MantisBT instance.
The Source Integration plugin does not actually process it, but you should
check that it has been delivered successfully. A green check mark will be
displayed next to the newly created Webhook, indicating success; in case of
error, you will see a red warning sign instead.

**NOTE:** An alternative to using Webhooks for changesets processing automation
would be to schedule a cron job on your server.

### Initialize the repository

This step will perform an initial import of the repository's changesets.

1. Go to the Manage Repository page

2. Click the **Import Everything** button

**NOTE:** This may take a long time or even fail for large repositories.
Failures are generally caused by timeouts on the PHP or web server side, as
the maximum time allowed to execute a script is reached
(see [max_execution_time](http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time)).
The Source Integration plugin does not handle this well; this is a
[known issue](https://github.com/mantisbt-plugins/source-integration/issues/60),
but unfortunately there are currently no workarounds.
