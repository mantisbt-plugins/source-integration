<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_update' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'repo_id' );
$f_repo_name = gpc_get_string( 'repo_name' );
$f_repo_url = gpc_get_string( 'repo_url' );
$f_repo_commit_needs_issue = gpc_get_bool( 'repo_commit_needs_issue', false );
$f_repo_commit_issues_must_exist = gpc_get_bool( 'repo_commit_issues_must_exist', false );
$f_repo_commit_ownership_must_match = gpc_get_bool( 'repo_commit_ownership_must_match', false );
$f_repo_commit_status_restricted = gpc_get_bool( 'repo_commit_status_restricted', false );
$f_repo_commit_status_allowed = gpc_get_int_array( 'repo_commit_status_allowed', MantisEnum::getValues( config_get( 'status_enum_string' ) ));
$f_repo_commit_project_restricted = gpc_get_bool( 'repo_commit_project_restricted', false );
$f_repo_commit_project_allowed = gpc_get_int_array( 'repo_commit_project_allowed', Array( 0 ) );
$f_repo_commit_committer_must_be_member = gpc_get_bool( 'repo_commit_committer_must_me_member', false );
$f_repo_commit_committer_must_be_level = gpc_get_int_array( 'repo_commit_committer_must_be_level', MantisEnum::getValues( config_get( 'access_levels_enum_string' ) ));

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_repo->name = $f_repo_name;
$t_repo->url = $f_repo_url;
$t_repo->info['repo_commit_needs_issue'] = $f_repo_commit_needs_issue;
$t_repo->info['repo_commit_issues_must_exist'] = $f_repo_commit_issues_must_exist;
$t_repo->info['repo_commit_ownership_must_match'] = $f_repo_commit_ownership_must_match;
$t_repo->info['repo_commit_status_restricted'] = $f_repo_commit_status_restricted;
$t_repo->info['repo_commit_status_allowed'] = $f_repo_commit_status_allowed;
$t_repo->info['repo_commit_project_restricted'] = $f_repo_commit_project_restricted;
$t_repo->info['repo_commit_project_allowed'] = $f_repo_commit_project_allowed;
$t_repo->info['repo_commit_committer_must_be_member'] = $f_repo_commit_committer_must_be_member;
$t_repo->info['repo_commit_committer_must_be_level'] = $f_repo_commit_committer_must_be_level;

$t_updated_repo = $t_vcs->update_repo( $t_repo );

if ( !is_null( $t_updated_repo ) ) {
	$t_updated_repo->save();
} else {
	$t_repo->save();
}

form_security_purge( 'plugin_Source_repo_update' );

print_successful_redirect( plugin_page( 'repo_manage_page', true ) . '&id=' . $t_repo->id );

