<?php
# Copyright (C) 2008 John Reese, LeetCode.net
#
#
# This program is free software:Affero  you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCAffero HANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

form_security_validate( 'plugin_Source_repo_update_mappings' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

$t_mappings = $t_repo->load_mappings();

# start processing the updated form entries for each mapping
foreach( $t_mappings as $t_mapping ) {
	$f_mapping_delete = gpc_get_bool( $t_mapping->branch . '_delete', false );

	if ( $f_mapping_delete ) {
		$t_mapping->delete();
	}

	$f_mapping_branch = gpc_get_string( $t_mapping->branch . '_branch', $t_mapping->branch );
	$f_mapping_type = gpc_get_int( $t_mapping->branch . '_type', $t_mapping->type );
	$f_mapping_version = gpc_get_string( $t_mapping->branch . '_version', $t_mapping->version );
	$f_mapping_regex = gpc_get_string( $t_mapping->branch . '_regex', $t_mapping->regex );

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
	if ( $t_mapping->version != $f_mapping_version ) {
		$t_mapping->version = $f_mapping_version;
		$t_update = true;
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
$f_mapping_version = gpc_get_string( '_version', '' );
$f_mapping_regex = gpc_get_string( '_regex', '' );

if ( !is_blank( $f_mapping_branch ) ) {
	if ( isset( $t_mappings[ $f_mapping_branch ] ) ) {
		die( 'error branch' );
	}

	if ( $f_mapping_type < SOURCE_EXPLICIT ) {
		die( 'error type' );
	}

	if ( $f_mapping_type == SOURCE_EXPLICIT && is_blank( $f_mapping_version ) ) {
		die( 'error version' );
	}

	$t_mapping = new SourceMapping( $t_repo->id, $f_mapping_branch, $f_mapping_type, $f_mapping_version, $f_mapping_regex );
	$t_mapping->save();
}

form_security_purge( 'plugin_Source_repo_update_mappings' );
print_successful_redirect( plugin_page( 'repo_manage_page', true ) . '&id=' . $t_repo->id );

