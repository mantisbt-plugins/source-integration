<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_import_full' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );

helper_ensure_confirmed( plugin_lang_get( 'ensure_import_full' ), plugin_lang_get( 'import_full' ) );
helper_begin_long_process();

html_page_top1();
html_page_top2();

# create a new, temporary repo
$t_new_repo = SourceRepo::load( $f_repo_id );
$t_new_repo->id = 0;
$t_new_repo->name = 'Import ' . date( 'Y-m-d H:i:s' );
$t_new_repo->save();

# keep checking for more changesets to import
$t_error = false;
while( true ) {

	# import the next batch of changesets
	$t_changesets = $t_vcs->import_full( $t_new_repo );

	# check for errors
	if ( !is_array( $t_changesets ) ) {
		$t_error = true;
		break;
	}

	# if no more entries, we're done
	if ( count( $t_changesets ) < 1 ) {
		break;
	}

	$t_new_repo->name = $t_repo->name;
	Source_Process_Changesets( $t_changesets, $t_new_repo );
}

# if we errored, delete the new repo and stop
if ( $t_error ) {
	SourceRepo::delete( $t_new_repo->id );

	echo '<br/><div class="center">';
	echo plugin_lang_get( 'import_full_failed' ), '<br/>';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) );
	echo '</div>';

# otherwise, rename and save the new repo, then delete the old
} else {
	$t_new_repo->save();

	SourceRepo::delete( $t_repo->id );

	$t_stats = $t_new_repo->stats();

	echo '<br/><div class="center">';
	echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] ), '<br/>';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_new_repo->id, plugin_lang_get( 'back_repo' ) );
	echo '</div>';
}

form_security_purge( 'plugin_Source_repo_import_full' );

html_page_bottom1();

