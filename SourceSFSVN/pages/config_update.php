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

form_security_validate( 'plugin_SourceSFSVN_config_update' );

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
auth_reauthenticate();

$f_svnpath = gpc_get_string( 'svnpath', '' );
$t_svnpath = plugin_config_get( 'svnpath' );

$f_svnpath = rtrim( $f_svnpath, '/' );

if ( $f_svnpath != $t_svnpath ) {
	if ( is_blank( $f_svnpath ) ) {
		plugin_config_delete( 'svnpath' );

	} else {
		# be sure that the path is valid
		if ( is_dir( $f_svnpath ) &&
			is_file( $f_svnpath . DIRECTORY_SEPARATOR . 'svn' ) &&
			is_executable( $f_svnpath . DIRECTORY_SEPARATOR . 'svn' ) ) {

			plugin_config_set( 'svnpath', $f_svnpath );
		} else {
			plugin_error( 'SVNPathInvalid' );
		}

	}
}

form_security_purge( 'plugin_SourceSFSVN_config_update' );
print_successful_redirect( plugin_page( 'config_page', true ) );

