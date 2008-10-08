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

access_ensure_project_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$t_changeset = SourceChangeset::load( $f_changeset_id );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();

if ( plugin_config_get( 'enable_porting' ) ) {
	$f_ported = gpc_get_string( 'ported', '' );

	if ( 0 == $f_ported || in_array( $f_ported, $t_repo->branches ) ) {
		$t_changeset->ported = $f_ported;
	}
}

$t_changeset->save();

print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id );

