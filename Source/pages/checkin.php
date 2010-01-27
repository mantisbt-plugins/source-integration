<?php
# Copyright (C) 2008-2010 John Reese, LeetCode.net
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

$t_address = $_SERVER['REMOTE_ADDR'];
$t_valid = false;

# Always allow the same machine to check-in
if ( '127.0.0.1' == $t_address || '127.0.1.1' == $t_address
     || 'localhost' == $t_address || '::1' == $t_address ) {
	$t_valid = true;
}

# Check for allowed remote IP/URL addresses
if ( !$t_valid && ON == plugin_config_get( 'remote_checkin' ) ) {
	$t_checkin_urls = unserialize( plugin_config_get( 'checkin_urls' ) );
	preg_match( '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $t_address, $t_address_matches );

	foreach ( $t_checkin_urls as $t_url ) {
		if ( $t_valid ) break;

		$t_url = trim( $t_url );

		if ( preg_match( '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $t_url, $t_remote_matches ) ) { # IP
			if ( $t_url == $t_address ) {
				$t_valid = true;
				break;
			}

			$t_match = true;
			for( $i = 1; $i <= 4; $i++ ) {
				if ( $t_remote_matches[$i] == '0' || $t_address_matches[$i] == $t_remote_matches[$i] ) {
				} else {
					$t_match = false;
					break;
				}
			}

			$t_valid = $t_match;

		} else {
			$t_ip = gethostbyname( $t_url );
			if ( $t_ip == $t_address ) {
				$t_valid = true;
				break;
			}
		}
	}
}

# Not validated by this point gets the boot!
if ( !$t_valid ) {
	die( plugin_lang_get( 'invalid_checkin_url' ) );
}

# Let plugins try to intepret POST data before we do
$t_predata = event_signal( 'EVENT_SOURCE_PRECOMMIT' );

# Expect plugin data in form of array( repo_name, data )
if ( is_array( $t_predata ) && count( $t_predata ) == 2 ) {
	$t_repo = $t_predata['repo'];
	$f_data = $t_predata['data'];
} else {
	$f_repo_name = gpc_get_string( 'repo_name' );
	$f_data = gpc_get_string( 'data' );

	# Try to find the repository by name
	$t_repo = SourceRepo::load_by_name( $f_repo_name );
}

# Repo not found
if ( is_null( $t_repo ) ) {
	die( plugin_lang_get( 'invalid_repo' ) );
}

$t_vcs = SourceVCS::repo( $t_repo );

# Let the plugins handle commit data
$t_changesets = $t_vcs->commit( $t_repo, $f_data );

# Changesets couldn't be loaded apparently
if ( !is_array( $t_changesets ) ) {
	die( plugin_lang_get( 'invalid_changeset' ) );
}

# No more changesets to checkin
if ( count( $t_changesets ) < 1 ) {
	return;
}

Source_Process_Changesets( $t_changesets );

# Allow plugins to handle commits afterwards
$t_vcs->postcommit( $t_repo, $t_changesets );

