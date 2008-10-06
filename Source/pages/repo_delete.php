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

form_security_validate( 'plugin_Source_repo_delete' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

helper_ensure_confirmed( sprintf( plugin_lang_get( 'ensure_delete' ), $t_repo->name ), plugin_lang_get( 'delete' ), ' ', plugin_lang_get( 'repository' ) );

SourceRepo::delete( $t_repo->id );

form_security_purge( 'plugin_Source_repo_delete' );
print_successful_redirect( plugin_page( 'index', true ) );
