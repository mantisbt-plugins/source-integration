<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_repo_update_mappings' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

$t_mappings = $t_repo->load_mappings();

# start processing the updated form entries for each mapping
foreach( $t_mappings as $t_mapping ) {
	$t_posted_branch = str_replace( '.', '_', $t_mapping->branch );

	$f_mapping_delete = gpc_get_bool( $t_posted_branch . '_delete', false );

	if ( $f_mapping_delete ) {
		$t_mapping->delete();
		continue;
	}

	$f_mapping_branch = gpc_get_string( $t_posted_branch . '_branch', $t_mapping->branch );
	$f_mapping_type = gpc_get_int( $t_posted_branch . '_type', $t_mapping->type );
	if ( Source_PVM() ) {
		$f_mapping_pvm_version_id = gpc_get_int( $t_posted_branch . '_pvm_version_id', $t_mapping->pvm_version_id );
	} else {
		$f_mapping_version = gpc_get_string( $t_posted_branch . '_version', $t_mapping->version );
	}
	$f_mapping_regex = gpc_get_string( $t_posted_branch . '_regex', $t_mapping->regex );

	$t_update = false;

	# determine changes and if updates need to be made
	if ( $t_mapping->branch != $f_mapping_branch ) {
		$t_mapping->branch = $f_mapping_branch;
		$t_update = true;
	}
	if ( $t_mapping->type != $f_mapping_type ) {
		$t_mapping->type = $f_mapping_type;
		$t_update = true;
	}
	if ( Source_PVM() ) {
		if ( $t_mapping->pvm_version_id != $f_mapping_pvm_version_id ) {
			$t_mapping->pvm_version_id = $f_mapping_pvm_version_id;
			$t_update = true;
		}
	} else {
		if ( $t_mapping->version != $f_mapping_version ) {
			$t_mapping->version = $f_mapping_version;
			$t_update = true;
		}
	}
	if ( $t_mapping->regex != $f_mapping_regex && false !== preg_match( $f_mapping_regex, '' ) ) {
		$t_mapping->regex = $f_mapping_regex;
		$t_update = true;
	}

	# only save changes if something was updated
	if ( $t_update ) {
		$t_mapping->save();
	}
}

# process the form elements for creating a new mapping
$f_mapping_branch = gpc_get_string( '_branch', '' );
$f_mapping_type = gpc_get_int( '_type', 0 );
if ( Source_PVM() ) {
	$f_mapping_pvm_version_id = gpc_get_int( '_pvm_version_id', 0 );
	$f_mapping_version = '';
} else {
	$f_mapping_pvm_version_id = 0;
	$f_mapping_version = gpc_get_string( '_version', '' );
}
$f_mapping_regex = gpc_get_string( '_regex', '' );

if ( !is_blank( $f_mapping_branch ) ) {
	if ( isset( $t_mappings[ $f_mapping_branch ] ) ) {
		die( 'error branch' );
	}

	if ( $f_mapping_type < SOURCE_EXPLICIT ) {
		die( 'error type' );
	}

	if ( $f_mapping_type == SOURCE_EXPLICIT ) {
		if ( Source_PVM() ) {
			if ( $f_mapping_pvm_version_id < 1 ) {
				die( 'error product version' );
			}
		} else {
			if ( is_blank( $f_mapping_version ) ) {
				die( 'error version' );
			}
		}
	}

	$t_mapping = new SourceMapping( $t_repo->id, $f_mapping_branch, $f_mapping_type, $f_mapping_version, $f_mapping_regex, $f_mapping_pvm_version_id );
	$t_mapping->save();
}

form_security_purge( 'plugin_Source_repo_update_mappings' );
print_successful_redirect( plugin_page( 'repo_manage_page', true ) . '&id=' . $t_repo->id );

