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
$t_bug_count = 0;

$f_repo_name = gpc_get_string( 'repo_name', '' );
$t_repo = SourceRepo::load_by_name( $f_repo_name );
# Repo not found
if ( is_null( $t_repo ) ) {
        die( plugin_lang_get( 'invalid_repo' ) );
}
$t_repo_commit_needs_issue = isset( $t_repo->info['repo_commit_needs_issue'] ) ? $t_repo->info['repo_commit_needs_issue'] : false;

$t_all_ok = true;


if(( sizeof( $t_bug_list ) == 0 ) && $t_repo_commit_needs_issue )
{
    printf("Check-Message: '%s'\r\n",plugin_lang_get( 'error_commit_needs_issue' ) );
    $t_all_ok = false;
}
else
{

foreach( $t_bug_list as $t_bug_id )
{
    $t_bug_count++;
    $t_bug_id_str = sprintf( "%08d", $t_bug_count );

    printf("Issue-%s-ID: %d\r\n",$t_bug_id_str, $t_bug_id );

    # Check existence first to prevent API throwing an error
    if( bug_exists( $t_bug_id ) )
    {
        $t_bug = bug_get( $t_bug_id );

        printf("Issue-%s-Exists: 1\r\n",$t_bug_id_str );
        printf("Issue-%s-Project: '%s'\r\n",$t_bug_id_str,project_get_name( $t_bug->project_id ) );
        printf("Issue-%s-User: '%s'\r\n",$t_bug_id_str,user_get_name( $t_bug->handler_id ) );
        printf("Issue-%s-Resolved: '%s'\r\n",$t_bug_id_str,$t_bug->status < $t_resolved_threshold );
    }
    else
    {
        printf("Issue-%s-Exists: 0\r\n",$t_bug_id_str );
    }
}
}
printf("Check-OK: %d\r\n",$t_all_ok );

?>
