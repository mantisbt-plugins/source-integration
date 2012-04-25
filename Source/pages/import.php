<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

$t_address = $_SERVER['REMOTE_ADDR'];
$t_valid = false;

helper_begin_long_process();

# Always allow the same machine to import
if ( '127.0.0.1' == $t_address || '127.0.1.1' == $t_address
     || 'localhost' == $t_address || '::1' == $t_address ) {
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
if ( gpc_get_string( 'api_key' ) == plugin_config_get( 'api_key' ) ) {
	$t_valid = true;
}
# Not validated by this point gets the boot!
if ( !$t_valid ) {
	die( plugin_lang_get( 'invalid_import_url' ) );
}

$f_repo_id = strtolower( gpc_get_string( 'id' ) );

# Load an array of repositories to be imported
if ( $f_repo_id == 'all' ) {
	$t_repos = SourceRepo::load_all();

} elseif ( is_numeric( $f_repo_id ) ) {
	$t_repo_id = (int) $f_repo_id;
	$t_repos = array( SourceRepo::load( $t_repo_id ) );
} else {
	$f_repo_name = $f_repo_id;
	$t_repos = array( SourceRepo::load_from_name( $f_repo_name ) );
}

# Loop through all repos to be imported
foreach ( $t_repos as $t_repo ) {
	$t_vcs = SourceVCS::repo( $t_repo );

	# keep checking for more changesets to import
	$t_repo->import_error = false;
	while( true ) {

		# import the next batch of changesets
		$t_changesets = $t_vcs->import_latest( $t_repo );

		# check for errors
		if ( !is_array( $t_changesets ) ) {
			$t_repo->import_error = true;
			break;
		}

		# if no more entries, we're done
		if ( count( $t_changesets ) < 1 ) {
			break;
		}

		Source_Process_Changesets( $t_changesets );
	}
}

