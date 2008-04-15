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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

helper_ensure_confirmed( lang_get( 'plugin_Source_ensure_import' ), lang_get( 'plugin_Source_import_data' ) );
helper_begin_long_process();

html_page_top1();
html_page_top2();

$t_new_repo = SourceRepo::load( $f_repo_id );
$t_new_repo->id = 0;
$t_new_repo->name = 'Import ' . date( 'Y-m-d H:i:s' );
$t_new_repo->save();

$t_status = event_signal( 'EVENT_SOURCE_IMPORT_REPO', array( $t_new_repo ) );

if ( $t_status ) {
	SourceRepo::delete( $t_repo->id );

	$t_new_repo->name = $t_repo->name;
	$t_new_repo->save();

	echo '<br/><div class="center">';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_new_repo->id, 'Return To Repository' );
	echo '</div>';

} else {
	SourceRepo::delete( $t_new_repo->id );

	echo '<br/><div class="center">';
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, 'Return To Repository' );
	echo '</div>';
}

html_page_bottom1();

