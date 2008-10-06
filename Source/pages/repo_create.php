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

form_security_validate( 'plugin_Source_repo_create' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_name = gpc_get_string( 'repo_name' );
$f_repo_type = gpc_get_string( 'repo_type' );

$t_repo = new SourceRepo( $f_repo_type, $f_repo_name );
$t_repo->save();

form_security_purge( 'plugin_Source_repo_create' );

print_successful_redirect( plugin_page( 'repo_update_page', true ) . '&id=' . $t_repo->id );
