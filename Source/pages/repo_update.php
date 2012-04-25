<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_update' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'repo_id' );
$f_repo_name = gpc_get_string( 'repo_name' );
$f_repo_url = gpc_get_string( 'repo_url' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_repo->name = $f_repo_name;
$t_repo->url = $f_repo_url;

$t_updated_repo = $t_vcs->update_repo( $t_repo );

if ( !is_null( $t_updated_repo ) ) {
	$t_updated_repo->save();
} else {
	$t_repo->save();
}

form_security_purge( 'plugin_Source_repo_update' );

print_successful_redirect( plugin_page( 'repo_manage_page', true ) . '&id=' . $t_repo->id );

