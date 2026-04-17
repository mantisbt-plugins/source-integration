<?php

plugin_push_current( 'Source' );
$t_threshold = plugin_config_get( 'manage_threshold' );
plugin_pop_current();

access_ensure_global_level( $t_threshold );

$f_repo_id = gpc_get_int( 'id' );
$t_repo    = SourceRepo::load( $f_repo_id );
$t_vcs     = SourceVCS::repo( $t_repo );

$t_result = $t_vcs->test_connection( $t_repo );

header( 'Content-Type: application/json' );
echo json_encode( $t_result );
