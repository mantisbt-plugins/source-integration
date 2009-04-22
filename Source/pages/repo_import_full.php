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

form_security_validate( 'plugin_Source_repo_import_full' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

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
	$t_changesets = event_signal( 'EVENT_SOURCE_IMPORT_FULL', array( $t_new_repo ) );

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
}

# if we errored, delete the new repo and stop
if ( $t_error ) {
	SourceRepo::delete( $t_new_repo->id );

	echo '<br/><div class="center">';
	echo plugin_lang_get( 'import_full_failed' ), '<br/>';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, 'Return To Repository' );
	echo '</div>';

# otherwise, rename and save the new repo, then delete the old
} else {
	$t_new_repo->name = $t_repo->name;
	$t_new_repo->save();

	SourceRepo::delete( $t_repo->id );

	$t_stats = $t_new_repo->stats();

	echo '<br/><div class="center">';
	echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] ), '<br/>';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_new_repo->id, 'Return To Repository' );
	echo '</div>';
}

form_security_purge( 'plugin_Source_repo_import_full' );

html_page_bottom1();

