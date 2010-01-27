<?php
# Copyright (C) 2008-2009 John Reese, LeetCode.net
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

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

