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
$t_remote = true;

helper_begin_long_process();

# Allow a logged-in user to import
if ( auth_is_user_authenticated() && !current_user_is_anonymous() ) {
	form_security_validate( 'plugin_Source_repo_import_latest' );
	access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );
	helper_ensure_confirmed( plugin_lang_get( 'ensure_import_latest' ), plugin_lang_get( 'import_latest' ) );

	$t_valid = true;
	$t_remote = false;
}

# Always allow the same machine to import
if ( !$t_valid && ( '127.0.0.1' == $t_address || '127.0.1.1' == $t_address ) ) {
	$t_valid = true;
}

# Check for allowed remote IP/URL addresses
if ( !$t_valid && ON == plugin_config_get( 'remote_imports' ) ) {
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
$t_vcs = SourceVCS::repo( $t_repo );

if ( !$t_remote ) {
	html_page_top1();
	html_page_top2();
}

$t_pre_stats = $t_repo->stats();

# keep checking for more changesets to import
$t_error = false;
while( true ) {

	# import the next batch of changesets
	$t_changesets = $t_vcs->import_latest( $t_repo );

	# check for errors
	if ( !is_array( $t_changesets ) ) {
		$t_error = true;
		break;
	}

	# if no more entries, we're done
	if ( count( $t_changesets ) < 1 ) {
		break;
	}

	Source_Process_Changesets( $t_changesets );

	# let plugins process this batch of changesets
	$t_vcs->postimport( $t_repo, $t_changesets );
}

# only display results when the user is initiating the import
if ( !$t_remote ) {

	if ( $t_error ) {
		echo '<br/>', plugin_lang_get( 'import_latest_failed' ), '<br/>';
	}

	$t_stats = $t_repo->stats();
	$t_stats['changesets'] -= $t_pre_stats['changesets'];
	$t_stats['files'] -= $t_pre_stats['files'];
	$t_stats['bugs'] -= $t_pre_stats['bugs'];

	echo '<br/><div class="center">';
	echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] ), '<br/>';

	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, 'Return To Repository' );
	echo '</div>';

	form_security_purge( 'plugin_Source_repo_import_latest' );
	html_page_bottom1();
}

