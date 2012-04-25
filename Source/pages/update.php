<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_project_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$t_changeset = SourceChangeset::load( $f_changeset_id );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();

if ( plugin_config_get( 'enable_porting' ) ) {
	$f_ported = gpc_get_string( 'ported', '' );

	if ( 0 == $f_ported || in_array( $f_ported, $t_repo->branches ) ) {
		$t_changeset->ported = $f_ported;
	}
}

$t_changeset->save();

print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id );

