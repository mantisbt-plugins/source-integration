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

# TODO: Implement cron-able usage similar to checkin.php?

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

helper_ensure_confirmed( plugin_lang_get( 'ensure_import_latest' ), plugin_lang_get( 'import_latest' ) );
helper_begin_long_process();

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

