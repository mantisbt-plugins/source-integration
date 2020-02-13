<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_delete' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_string( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );

$t_message = sprintf( plugin_lang_get( 'ensure_delete' ), string_attribute( $t_repo->name ) );
helper_ensure_confirmed( $t_message, plugin_lang_get( 'delete_repository' ) );

SourceRepo::delete( $t_repo->id );

form_security_purge( 'plugin_Source_repo_delete' );
print_successful_redirect( plugin_page( 'index', true ) );
