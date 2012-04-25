<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_detach' );
access_ensure_global_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$f_bug_id = gpc_get_int( 'bug_id' );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_bugs();

$t_changeset->bugs = array_diff( $t_changeset->bugs, array( $f_bug_id ) );

$t_user_id = auth_get_current_user_id();
$t_changeset->save_bugs( $t_user_id );

form_security_purge( 'plugin_Source_detach' );
print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id );

