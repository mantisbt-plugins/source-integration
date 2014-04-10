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

$f_committer_name = gpc_get_string( 'committer', '' );
$f_repo_name = gpc_get_string( 'repo_name', '' );
$t_repo = SourceRepo::load_by_name( $f_repo_name );
# Repo not found
if ( is_null( $t_repo ) ) {
        die( plugin_lang_get( 'invalid_repo' ) );
}
$t_repo_commit_needs_issue = isset( $t_repo->info['repo_commit_needs_issue'] ) ? $t_repo->info['repo_commit_needs_issue'] : false;
$t_repo_commit_issues_must_exist = isset( $t_repo->info['repo_commit_issues_must_exist'] ) ? $t_repo->info['repo_commit_issues_must_exist'] : false;
$t_repo_commit_ownership_must_match = isset( $t_repo->info['repo_commit_ownership_must_match'] ) ? $t_repo->info['repo_commit_ownership_must_match'] : false;
$t_repo_commit_status_restricted = isset( $t_repo->info['repo_commit_status_restricted'] ) ? $t_repo->info['repo_commit_status_restricted'] : false;
$t_repo_commit_status_allowed = isset( $t_repo->info['repo_commit_status_allowed'] ) ? $t_repo->info['repo_commit_status_allowed'] : '';

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

        # Check existence first to prevent API throwing an error
        if( bug_exists( $t_bug_id ) )
        {
            $t_bug = bug_get( $t_bug_id );

            if( $t_repo_commit_ownership_must_match )
            {
                $t_user_name = user_get_name( $t_bug->handler_id );
                $t_user_email = user_get_email( $t_bug->handler_id );
                if(!( strlen( $f_committer_name ) && ( $t_user_name == $f_committer_name )))
                {
                     printf("Check-Message: '%s : %d (%s vs %s)'\r\n",plugin_lang_get( 'error_commit_issue_ownership' ), $t_bug_id, $t_user_name, $f_committer_name );
                     $t_all_ok = false;
                }
            }
            if( $t_repo_commit_status_restricted )
            {
                if( !in_array( $t_bug->status, $t_repo_commit_status_allowed ))
                {
                     printf("Check-Message: '%s : %d (%s vs ", plugin_lang_get( 'error_commit_issue_wrong_status' ), $t_bug_id,  get_enum_element( 'status', $t_bug->status ));
		     $t_first = true;
		     foreach( $t_repo_commit_status_allowed as $t_allowed_status )
		     {
			     if( !$t_first )
			     {
				     printf(",");
			     }
			     printf( get_enum_element( 'status', $t_allowed_status ));
			     $t_first = false;
		     }
                     printf(")'\r\n");
                     $t_all_ok = false;
                }
            }
        }
        else
        {
            /* If the issue doesn't exist, then the ownership can't match */
            if( $t_repo_commit_issues_must_exist || $t_repo_commit_ownership_must_match || $t_repo_commit_status_restricted )
            {
                printf("Check-Message: '%s : %d'\r\n",plugin_lang_get( 'error_commit_nonexistent_issue' ), $t_bug_id );
                $t_all_ok = false;
            }
        }
    }
}
printf("Check-OK: %d\r\n",$t_all_ok );

?>
