<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_create' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_name = gpc_get_string( 'repo_name' );
$f_repo_type = gpc_get_string( 'repo_type' );

$t_repo = new SourceRepo( $f_repo_type, $f_repo_name );
$t_repo->save();

form_security_purge( 'plugin_Source_repo_create' );

print_successful_redirect( plugin_page( 'repo_update_page', true ) . '&id=' . $t_repo->id );
