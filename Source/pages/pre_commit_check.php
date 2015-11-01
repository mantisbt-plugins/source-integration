<?php

# Copyright (c) 2014 John Bailey
# Licensed under the MIT license
#
# This file is intended to be called from a pre-commit hook in order to verify
#  mantis ticket references in the commit comment.  The level of checking
#  is configured on a per-repository basis

include_once 'si_common.php';

if ( !si_is_key_ok() ) {
	die( plugin_lang_get( 'invalid_key' ) );
}

# If you're worried about information "leaking" out via error messages, set this to false to prevent error messages 
#  containing any information from the ticket(s)
$t_informational_errors = true;

# Get a list of the bug IDs which were referenced in the commit comment
$t_bug_list = Source_Parse_Buglinks( gpc_get_string( 'commit_comment', '' ));
$t_resolved_threshold = config_get( 'bug_resolved_status_threshold' );

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
if(( sizeof( $t_bug_list ) == 0 ) && $t_repo_commit_needs_issue ) {

	# It was expected that the commit comment would reference one of more bug
	# IDs but this was not the case

	printf( "Check-Message: '%s'\r\n",plugin_lang_get( 'error_commit_needs_issue' ) );
	$t_all_ok = false;

} else {

	# Loop all the bug IDs referenced in the commit comment
	foreach( $t_bug_list as $t_bug_id ) {

		# Check existence first to prevent API throwing an error
		if( bug_exists( $t_bug_id ) ) {

			$t_bug = bug_get( $t_bug_id );

			# Ownership of ticket must match committer?
			if( $t_repo_commit_ownership_must_match ) {

				if( 0 == $t_bug->handler_id ) {
					$t_user_name = 'none';
					$t_user_email = 'none';
				} else {
					$t_user_name = user_get_name( $t_bug->handler_id );
					$t_user_email = user_get_email( $t_bug->handler_id );
				}

				# Check that the username of the committer matches the user name
				# or e-mail address of the owner of the ticket
				if( !( strlen( $f_committer_name ) && 
					  (( $t_user_name == $f_committer_name ) || 
					   ( $t_user_email == $f_committer_name )))) {

					printf( "Check-Message: '%s : %s %d", 
							plugin_lang_get( 'error_commit_issue_ownership' ), 
							plugin_lang_get( 'issue' ),
							$t_bug_id );

					 if( $t_informational_errors ) {

						 # Informative errors turned on so display the user to whom
						 #   the ticket is assigned
						 printf( " (%s/%s vs %s)",
								 $t_user_name, $t_user_email, $f_committer_name );
					 }

					 printf( "'\r\n" );
					 $t_all_ok = false;
				}
			} # End ownership must match ticket

			# Only allowed to commit against tickets with a specific status?
			if( $t_repo_commit_status_restricted ) {

				# Check that the bug's status is at a level for which a commit
				# is allowed
				if( !in_array( $t_bug->status, $t_repo_commit_status_allowed )) {

					 printf( "Check-Message: '%s : %s %d",
							 plugin_lang_get( 'error_commit_issue_wrong_status' ), 
							 plugin_lang_get( 'issue' ),
							 $t_bug_id );

					 if( $t_informational_errors ) {

						 # Informative errors turned on so display a list of statuses for which
						 #  a commit would be accepted

						 # Get an array of the names of the statuses for which commit is allowed
						 $t_statuses = array_map( function( $p_status ) { return get_enum_element( 'status', $p_status ); }, $t_repo_commit_status_allowed );

						 printf( " (%s vs %s)", 
								 get_enum_element( 'status', $t_bug->status ),
								 implode( $t_statuses, ", " ));
					 }
					 printf( "'\r\n" );
					 $t_all_ok = false;
				}
			} # End only allowed to commit against tickets with a specific status

			# Only allowed to commit against Mantis tickets within specific project(s)
			if( $t_repo_commit_project_restricted ) {

				if( !in_array( 0, $t_repo_commit_project_allowed ) &&
					!in_array( $t_bug->project_id, $t_repo_commit_project_allowed )) {

					 printf( "Check-Message: '%s : %s %d",
							 plugin_lang_get( 'error_commit_issue_wrong_project' ), 
							 plugin_lang_get( 'issue' ),
							 $t_bug_id );

					 if( $t_informational_errors ) {

						 # Informative errors turned on so display a list of Mantis projects to
						 #   which referenced tickets must belong

						 # Get an array of the names of all the projects
						 $t_projects = array_map( function( $p_proj ) { return project_get_field( $p_proj, 'name' ); }, $t_repo_commit_project_allowed );
						 
						 printf( " (%s vs %s)", 
								 project_get_field( $t_bug->project_id, 'name' ),
								 implode( $t_projects, ", " ));
					 }

					 printf( "'\r\n" );
					 $t_all_ok = false;
				}
			} # End only allowed to commit against tickets within specific projects

			# Committer must belong to the Mantis project?
			if( $t_repo_commit_committer_must_be_member ) {

				$t_user_id = user_get_id_by_name( $f_committer_name );

				# Didn't find the username?  Try the e-mail address
				if( $t_user_id == false ) {

					$t_user_id = user_get_id_by_email( $f_committer_name );
				}

				/* Check that the user exists in Mantis */
				if( $t_user_id == false ) {

					 printf( "Check-Message: '%s : %s %d (%s)'\r\n",
							 plugin_lang_get( 'error_commit_committer_not_found' ), 
							 plugin_lang_get( 'issue' ),
							 $t_bug_id, $f_committer_name );
					 $t_all_ok = false;

				/* Check that the user is assigned to the project */
				} elseif( ! project_includes_user( $t_bug->project_id, $t_user_id )) {
					 printf( "Check-Message: '%s : %s %d (%s)'\r\n",
							 plugin_lang_get( 'error_commit_committer_not_member' ), 
							 plugin_lang_get( 'issue' ),
							 $t_bug_id, $f_committer_name );
					 $t_all_ok = false;

				} else {

					$t_user_access_level = project_get_local_user_access_level( $t_bug->project_id, $t_user_id );
					if( !in_array( $t_user_access_level, $t_repo_commit_committer_must_be_level )) {

						printf( "Check-Message: '%s : %s %d",
								plugin_lang_get( 'error_commit_committer_wrong_level' ), 
								plugin_lang_get( 'issue' ),
								$t_bug_id );

						if( $t_informational_errors ) {

							# Informative errors turned on so display a list of access levels
							#   for which commit is allowed 
								
							$t_levels = MantisEnum::getAssocArrayIndexedByValues( config_get( 'access_levels_enum_string' ) );
							$t_allowed_levels = array_intersect_key( $t_levels, array_flip( $t_repo_commit_committer_must_be_level ));
							
							printf( " (%s vs %s)", 
									$t_levels[ $t_user_access_level ],
									implode( array_values( $t_allowed_levels ), ", " ) );
						}
						printf( "'\r\n" );
						$t_all_ok = false;
					}
				}
			} # End committer must belong to mantis project
		} else {

			/* If the issue doesn't exist, then can't perform the checks */
			if( $t_repo_commit_issues_must_exist ||
				$t_repo_commit_ownership_must_match ||
				$t_repo_commit_status_restricted ||
				$t_repo_commit_project_restricted ||
				$t_repo_commit_committer_must_be_member ) {

				printf( "Check-Message: '%s : %s %d'\r\n",
						plugin_lang_get( 'error_commit_nonexistent_issue' ), 
						plugin_lang_get( 'issue' ),
						$t_bug_id );
				$t_all_ok = false;
			}
		}
	}
}
printf( "Check-OK: %d\r\n",$t_all_ok );

?>
