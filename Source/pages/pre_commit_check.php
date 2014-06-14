<?php

# Copyright (c) 2014 John Bailey
# Licensed under the MIT license
#
# This file is intended to be called from a pre-commit hook in order to verify
#  mantis ticket references in the commit comment.  The level of checking
#  is configured on a per-repository basis

include_once 'si-common.php';

if ( !si_is_key_ok() ) {
    die( plugin_lang_get( 'invalid_key' ) );
}

# If you're worried about information "leaking" out via error messages, set this to false to prevent error messages 
#  containing any information from the ticket(s)
$t_informational_errors = false;

# Get a list of the bug IDs which were referenced in the commit comment
$t_bug_list = Source_Parse_Buglinks( gpc_get_string( 'commit_comment', '' ));
$t_resolved_threshold = config_get('bug_resolved_status_threshold');

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
$t_repo_commit_project_restricted = isset( $t_repo->info['repo_commit_project_restricted'] ) ? $t_repo->info['repo_commit_project_restricted'] : '';
$t_repo_commit_project_allowed = isset( $t_repo->info['repo_commit_project_allowed'] ) ? $t_repo->info['repo_commit_project_allowed'] : '';
$t_repo_commit_committer_must_be_member = isset( $t_repo->info['repo_commit_committer_must_be_member'] ) ?  $t_repo->info['repo_commit_committer_must_be_member'] : '';
$t_repo_commit_committer_must_be_level = isset( $t_repo->info['repo_commit_committer_must_be_level'] ) ?  $t_repo->info['repo_commit_committer_must_be_level'] : MantisEnum::getValues( config_get( 'access_levels_enum_string' ) ) ;

$t_all_ok = true;

# Check number of bugs referenced in the commit comment
if(( sizeof( $t_bug_list ) == 0 ) && $t_repo_commit_needs_issue )
{
    # It was expected that the commit comment would reference one of more bug
    # IDs but this was not the case

    printf("Check-Message: '%s'\r\n",plugin_lang_get( 'error_commit_needs_issue' ) );
    $t_all_ok = false;
}
else
{
    foreach( $t_bug_list as $t_bug_id )
    {
        # Check existence first to prevent API throwing an error
        if( bug_exists( $t_bug_id ) )
        {
            $t_bug = bug_get( $t_bug_id );

            if( $t_repo_commit_ownership_must_match )
            {
                $t_user_name = user_get_name( $t_bug->handler_id );
                $t_user_email = user_get_email( $t_bug->handler_id );

                # Check that the username of the committer matches the user name
                # or e-mail address of the owner of the ticket
                if(!( strlen( $f_committer_name ) && 
                     (( $t_user_name == $f_committer_name ) || 
                      ( $t_user_email == $f_committer_name ))))
                {
                     printf("Check-Message: '%s : %d", plugin_lang_get( 'error_commit_issue_ownership' ), $t_bug_id );

                     if( $t_informational_errors )
                     {
                         printf(" (%s vs %s/%s)",
                                $t_user_name, $f_committer_name, $t_user_email );
                     }
                     printf("'\r\n");
                     $t_all_ok = false;
                }
            }
            if( $t_repo_commit_status_restricted )
            {
                # Check that the bug's status is at a level for which a commit
                # is allowed
                if( !in_array( $t_bug->status, $t_repo_commit_status_allowed ))
                {
                     printf("Check-Message: '%s : %d",
                            plugin_lang_get( 'error_commit_issue_wrong_status' ), $t_bug_id );

                     if( $t_informational_errors )
                     {
                         printf(" (%s vs ", get_enum_element( 'status', $t_bug->status ));

                         $t_first = true;

                         # Output the list of statuses for which commit is allowed
                         foreach( $t_repo_commit_status_allowed as $t_allowed_status )
                         {
                             if( !$t_first )
                             {
                                 printf(", ");
                             }
                             printf( get_enum_element( 'status', $t_allowed_status ));
                             $t_first = false;
                         }
                         printf(")");
                     }
                     printf("'\r\n");
                     $t_all_ok = false;
                }
            }
            if( $t_repo_commit_project_restricted )
            {
                if( !in_array( 0, $t_repo_commit_project_allowed ) &&
                    !in_array( $t_bug->project_id, $t_repo_commit_project_allowed ))
                {
                     printf("Check-Message: '%s : %d",
                            plugin_lang_get( 'error_commit_issue_wrong_project' ), $t_bug_id );
                     if( $t_informational_errors )
                     {
                         printf(" (%s vs ", project_get_field( $t_bug->project_id, 'name' ));

                         $t_first = true;

                         # Output the list of projects for which commit is allowed
                         foreach( $t_repo_commit_project_allowed as $t_allowed_project )
                         {
                             if( !$t_first )
                             {
                                 printf(", ");
                             }
                             printf( project_get_field( $t_allowed_project, 'name' ) );
                             $t_first = false;
                         }
                         printf(")");
                     }
                     printf("'\r\n");
                     $t_all_ok = false;
                }
            }
            if( $t_repo_commit_committer_must_be_member )
            {
                $t_user_id = user_get_id_by_name( $f_committer_name );

                /* Check that the user exists in Mantis */
                if( $t_user_id == false )
                {
                     printf("Check-Message: '%s : %d (%s)'\r\n",
                            plugin_lang_get( 'error_commit_committer_not_found' ), $t_bug_id, $f_committer_name );
                     $t_all_ok = false;
                }
                /* Check that the user is assigned to the project */
                elseif( ! project_includes_user( $t_bug->project_id, $t_user_id ))
                {
                     printf("Check-Message: '%s : %d (%s)'\r\n",
                            plugin_lang_get( 'error_commit_committer_not_member' ), $t_bug_id, $f_committer_name );
                     $t_all_ok = false;
                }
                else
                {
                    $t_user_access_level = project_get_local_user_access_level( $t_bug->project_id, $t_user_id );
                    if( !in_array( $t_user_access_level, $t_repo_commit_committer_must_be_level ))
                    {
                        printf("Check-Message: '%s : %d",
                               plugin_lang_get( 'error_commit_committer_wrong_level' ), $t_bug_id );

                        if( $t_informational_errors )
                        {
                            printf(" (%s vs", $f_committer_name );

                            $t_first = true;
                            $t_levels = MantisEnum::getValues( config_get( 'access_levels_enum_string' ) );
                            # Output the list of projects for which commit is allowed
                            foreach( $t_repo_commit_committer_must_be_level as $t_allowed_level )
                            {
                                if( !$t_first )
                                {
                                    printf(", ");
                                }
                                printf( $t_levels[ $t_allowed_level ] );
                                $t_first = false;
                            }

                            printf(")");
                        }
                        printf("'\r\n");
                        $t_all_ok = false;
                    }
                }
            }
        }
        else
        {
            /* If the issue doesn't exist, then can't perform the checks */
            if( $t_repo_commit_issues_must_exist ||
                $t_repo_commit_ownership_must_match ||
                $t_repo_commit_status_restricted ||
                $t_repo_commit_project_restricted ||
                $t_repo_commit_committer_must_be_member )
            {
                printf("Check-Message: '%s : %d'\r\n",
                       plugin_lang_get( 'error_commit_nonexistent_issue' ), $t_bug_id );
                $t_all_ok = false;
            }
        }
    }
}
printf("Check-OK: %d\r\n",$t_all_ok );

?>
