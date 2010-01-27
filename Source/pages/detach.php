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

form_security_validate( 'plugin_Source_detach' );
access_ensure_global_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$f_bug_id = gpc_get_int( 'bug_id' );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_bugs();

$t_changeset->bugs = array_diff( $t_changeset->bugs, array( $f_bug_id ) );

$t_user_id = auth_get_current_user_id();
$t_changeset->save_bugs( $t_user_id );

form_security_purge( 'plugin_Source_detach' );
print_successful_redirect( plugin_page( 'view', true ) . '&id=' . $t_changeset->id );

