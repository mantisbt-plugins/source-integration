<?php

# Copyright (c) 2014 John Bailey
# Licensed under the MIT license

include_once 'si-common.php';

$t_valid = false;

if ( si_is_key_ok() ) {
    $t_valid = true;
}

if ( !$t_valid ) {
    die( plugin_lang_get( 'invalid_key' ) );
}

# Get a list of the bug IDs which were referenced in the commit comment
$t_bug_list = Source_Parse_Buglinks( gpc_get_string( 'commit_comment', '' ));
$t_resolved_threshold = config_get('bug_resolved_status_threshold');

foreach( $t_bug_list as $t_bug_id )
{
    # Check existence first to prevent API throwing an error
    if( bug_exists( $t_bug_id ) )
    {
        $t_bug = bug_get( $t_bug_id );

        $t_bug_id_str = sprintf( "%08d", $t_bug_id );

        printf("Issue-%s-Project: '%s'\r\n",$t_bug_id_str,project_get_name( $t_bug->project_id ) );
        printf("Issue-%s-User: '%s'\r\n",$t_bug_id_str,user_get_name( $t_bug->handler_id ) );
        printf("Issue-%s-Resolved: '%s'\r\n",$t_bug_id_str,$t_bug->status < $t_resolved_threshold );
    }
}

?>
