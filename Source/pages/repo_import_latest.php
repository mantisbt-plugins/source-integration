<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

$t_address = $_SERVER['REMOTE_ADDR'];
$t_valid = false;

# Always allow the same machine to import
if ( '127.0.0.1' == $t_address || '127.0.1.1' == $t_address ) {
	$t_valid = true;
}

# Allow a logged-in user to import
if ( !$t_valid && auth_is_user_authenticated() ) {
	access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );
	helper_ensure_confirmed( plugin_lang_get( 'ensure_import_latest' ), plugin_lang_get( 'import_latest' ) );

	$t_valid = true;
}

helper_begin_long_process();

# Check for allowed remote IP/URL addresses
if ( !$t_valid && ON == plugin_config_get( 'remote_import' ) ) {
	$t_import_urls = unserialize( plugin_config_get( 'import_urls' ) );
	preg_match( '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $t_address, $t_address_matches );

	foreach ( $t_import_urls as $t_url ) {
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
	die( plugin_lang_get( 'invalid_import_url' ) );
}

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

html_page_top1();
html_page_top2();

$t_pre_stats = $t_repo->stats();

$t_status = event_signal( 'EVENT_SOURCE_IMPORT_LATEST', array( $t_repo ) );

$t_stats = $t_repo->stats();
$t_stats['changesets'] -= $t_pre_stats['changesets'];
$t_stats['files'] -= $t_pre_stats['files'];
$t_stats['bugs'] -= $t_pre_stats['bugs'];

echo '<br/><div class="center">';
echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] ), '<br/>';

if ( !$t_status ) {
	echo plugin_lang_get( 'import_latest_failed' ), '<br/>';
}

print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, 'Return To Repository' );
echo '</div>';

html_page_bottom1();

