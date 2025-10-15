<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );
$f_status = gpc_get_int( 'status', null );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_repo_commit_needs_issue = isset( $t_repo->info['repo_commit_needs_issue'] ) ?  $t_repo->info['repo_commit_needs_issue'] : false;
$t_repo_commit_issues_must_exist = isset( $t_repo->info['repo_commit_issues_must_exist'] ) ?  $t_repo->info['repo_commit_issues_must_exist'] : false;
$t_repo_commit_ownership_must_match = isset( $t_repo->info['repo_commit_ownership_must_match'] ) ?  $t_repo->info['repo_commit_ownership_must_match'] : false;
$t_repo_commit_committer_must_be_member = isset( $t_repo->info['repo_commit_committer_must_be_member'] ) ?  $t_repo->info['repo_commit_committer_must_be_member'] : false;
$t_repo_commit_committer_must_be_level = isset( $t_repo->info['repo_commit_committer_must_be_level'] ) ?  $t_repo->info['repo_commit_committer_must_be_level'] : MantisEnum::getValues( config_get( 'access_levels_enum_string' ) ) ;
$t_repo_commit_status_restricted = isset( $t_repo->info['repo_commit_status_restricted'] ) ?  $t_repo->info['repo_commit_status_restricted'] : false;
$t_repo_commit_status_allowed = isset( $t_repo->info['repo_commit_status_allowed'] ) ?  $t_repo->info['repo_commit_status_allowed'] : MantisEnum::getValues( config_get( 'status_enum_string' ));
$t_repo_commit_project_restricted = isset( $t_repo->info['repo_commit_project_restricted'] ) ?  $t_repo->info['repo_commit_project_restricted'] : false;
$t_repo_commit_project_allowed = isset( $t_repo->info['repo_commit_project_allowed'] ) ?  $t_repo->info['repo_commit_project_allowed'] : Array( 0 );

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

# Display Repository Updated message if status is true
if( $f_status ) {
?>
<div class="col-md-12 col-xs-12">
	<div class="alert alert-success center">
		<p class="bold bigger-110">
			<?php echo plugin_lang_get( 'repository_updated' ) ?>
		</p>
	</div>
</div>
<?php
}
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	<form action="<?php echo plugin_page( 'repo_update.php' ) ?>" method="post">
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'update_repository' ) ?>
			</h4>
			<?php echo form_security_field( 'plugin_Source_repo_update' ) ?>
			<input type="hidden" name="repo_id" id="repo_id" value="<?php echo $t_repo->id ?>"/>
		</div>
		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="widget-toolbox padding-8 clearfix">
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id ?>">
						<?php echo plugin_lang_get( 'back_repo' ) ?>
					</a>
				</div>
				<div class="table-responsive">
		<table class="table table-striped table-bordered table-condensed">
			<tr>
				<td class="category" width="35%"><?php echo plugin_lang_get( 'name' ) ?></td>
				<td>
					<input name="repo_name" type="text" maxlength="128" size="40" value="<?php echo string_attribute( $t_repo->name ) ?>"/>
				</td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'type' ) ?></td>
				<td><?php echo string_display( $t_type ) ?></td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'url' ) ?></td>
				<td>
					<input name="repo_url" type="text" maxlength="250" size="40" value="<?php echo string_attribute( $t_repo->url ) ?>"/>
				</td>
			</tr>

			<?php $t_vcs->update_repo_form( $t_repo ) ?>
						
			<tr >
				<td class="category"><?php echo plugin_lang_get( 'pre_commit_checks' ); ?></td>
				<td>
					<table class="table table-striped table-bordered table-condensed">
						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_needs_issue' ); ?></td>
							<td><input name="repo_commit_needs_issue" type="checkbox" <?php echo ($t_repo_commit_needs_issue ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_issues_must_exist' ); ?></td>
							<td><input name="repo_commit_issues_must_exist" type="checkbox" <?php echo ($t_repo_commit_issues_must_exist ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_ownership_must_match' ); ?></td>
							<td><input name="repo_commit_ownership_must_match" type="checkbox" <?php echo ($t_repo_commit_ownership_must_match ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_committer_must_be_member' ); ?></td>
							<td><input id="repo_commit_committer_must_be_member" name="repo_commit_committer_must_be_member" type="checkbox" <?php echo ($t_repo_commit_committer_must_be_member ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_committer_must_be_level' ) ?></td>
							<td><select multiple="multiple" id="repo_commit_committer_must_be_level" name="repo_commit_committer_must_be_level[]"><?php print_enum_string_option_list( 'access_levels', $t_repo_commit_committer_must_be_level ) ?></select></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_status_restricted' ); ?></td>
							<td><input name="repo_commit_status_restricted" id="repo_commit_status_restricted" type="checkbox" <?php echo ($t_repo_commit_status_restricted ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_status_restricted_list' ) ?></td>
							<td><select multiple="multiple" id="repo_commit_status_allowed" name="repo_commit_status_allowed[]"><?php print_enum_string_option_list( 'status', $t_repo_commit_status_allowed ) ?></select></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_project_restricted' ); ?></td>
							<td><input id="repo_commit_project_restricted" name="repo_commit_project_restricted" type="checkbox" <?php echo ($t_repo_commit_project_restricted ? 'checked="checked"' : '') ?>/></td>
						</tr>

						<tr>
							<td class="category"><?php echo plugin_lang_get( 'commit_project_restricted_list' ) ?></td>
							<td><select multiple="multiple" id="repo_commit_project_allowed" name="repo_commit_project_allowed[]"><?php print_project_option_list( $t_repo_commit_project_allowed ) ?></select></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get( 'update_repository' ) ?>" />
			</div>
		</div>

	</div>
	</form>
</div>

<?php
layout_page_end();

