<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_attach' );
access_ensure_global_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$f_bug_ids = gpc_get_string( 'bug_ids' );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_bugs();

$t_user_id = auth_get_current_user_id();

$t_bug_ids = explode( ',', $f_bug_ids );
foreach( $t_bug_ids as $t_bug_id ) {
	$t_bug_id = (int) $t_bug_id;

	if ( $t_bug_id < 1 || !bug_exists( $t_bug_id ) ) {
		continue;
	}

	if ( !in_array( $t_bug_id, $t_changeset->bugs ) ) {
		$t_changeset->bugs[] = $t_bug_id;
	}
}

$t_changeset->save_bugs( $t_user_id );

form_security_purge( 'plugin_Source_attach' );
print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id );

