<?php

# Copyright (c) 2010 John Reese
# Licensed under the MIT license

$t_address = $_SERVER['REMOTE_ADDR'];
$t_valid = false;
$t_remote = true;

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

# Allow a logged-in user to import
if ( !$t_valid && auth_is_user_authenticated() && !current_user_is_anonymous() ) {
	form_security_validate( 'plugin_Source_repo_import_latest' );
	access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );
	helper_ensure_confirmed( plugin_lang_get( 'ensure_import_latest' ), plugin_lang_get( 'import_latest' ) );

	$t_valid = true;
	$t_remote = false;
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
}

# Loop through all repos to be imported
foreach ( $t_repos as $t_repo ) {
	$t_vcs = SourceVCS::repo( $t_repo );

	if ( !$t_remote ) {
		$t_repo->pre_stats = $t_repo->stats();
	}

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

	if ( !$t_remote ) {
		$t_repo->post_stats = $t_repo->stats();
	}
}

# Display output to the user
if ( !$t_remote ) {
	html_page_top();

?>
<br/>
<table class="width60" align="center">

<tr>
<td class="" colspan="2"><?php echo plugin_lang_get( 'import_results' ) ?></td>
</tr>

<?php foreach ( $t_repos as $t_repo ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo string_display_line( $t_repo->name ) ?></td>
<td>
<?php
	if ( $t_repo->import_error ) {
		echo plugin_lang_get( 'import_latest_failed' ), '<br/>';
	}

	$t_stats = $t_repo->post_stats;
	$t_stats['changesets'] -= $t_repo->pre_stats['changesets'];
	$t_stats['files'] -= $t_repo->pre_stats['files'];
	$t_stats['bugs'] -= $t_repo->pre_stats['bugs'];

	echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] );
?>
</td>
</tr>
<?php } ?>

<tr>
<td colspan="2" class="center">
<?php
	if ( $f_repo_id == 'all' ) {
		print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
	} else {
		print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) );
	}
?>
</td>
</tr>

</table>

<?php
	form_security_purge( 'plugin_Source_repo_import_latest' );
	html_page_bottom();
}

