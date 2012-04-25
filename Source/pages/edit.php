<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_edit' );
access_ensure_global_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$f_user_id = gpc_get_int( 'user_id' );
$f_committer_id = gpc_get_int( 'committer_id' );
$f_branch = gpc_get_string( 'branch' );
$f_message = gpc_get_string( 'message' );

$t_changeset = SourceChangeset::load( $f_changeset_id );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();

if ( $f_user_id == 0 || user_ensure_exists( $f_user_id ) ) {
	$t_changeset->user_id = $f_user_id;
}

if ( $f_committer_id == 0 || user_ensure_exists( $f_committer_id ) ) {
	$t_changeset->committer_id = $f_committer_id;
}

if ( in_array( $f_branch, $t_repo->branches ) ) {
	$t_changeset->branch = $f_branch;
}

if ( plugin_config_get( 'enable_porting' ) ) {
	$f_ported = gpc_get_string( 'ported', '' );

	if ( 0 == $f_ported || in_array( $f_ported, $t_repo->branches ) ) {
		$t_changeset->ported = $f_ported;
	}
}

if ( !is_blank( $f_message ) ) {
	$t_changeset->message = $f_message;
}

$t_changeset->save();

form_security_purge( 'plugin_Source_edit' );
print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id . '&offset=' . $f_offset );

