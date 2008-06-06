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

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_view_threshold = gpc_get_int( 'view_threshold' );
$f_manage_threshold = gpc_get_int( 'manage_threshold' );

$f_buglink_regex_1 = gpc_get_string( 'buglink_regex_1' );
$f_buglink_reset_1 = gpc_get_string( 'buglink_reset_1', OFF );
$f_buglink_regex_2 = gpc_get_string( 'buglink_regex_2' );
$f_buglink_reset_2 = gpc_get_string( 'buglink_reset_2', OFF );

function check_urls( $t_urls_in ) {
	$t_urls_in = explode( "\n", $t_urls_in );
	$t_urls_out = array();

	foreach( $t_urls_in as $t_url ) {
		$t_url = trim( $t_url );
		if ( is_blank( $t_url ) || in_array( $t_url, $t_urls_out ) ) {
			continue;
		}

		$t_urls_out[] = $t_url;
	}

	return $t_urls_out;
}

$f_remote_checkin = gpc_get_bool( 'remote_checkin', OFF );
$f_checkin_urls = gpc_get_string( 'checkin_urls' );

$f_remote_import = gpc_get_bool( 'remote_import', OFF );
$f_import_urls = gpc_get_string( 'import_urls' );

$t_checkin_urls = check_urls( $f_checkin_urls );
$t_import_urls = check_urls( $f_import_urls );

if ( $f_view_threshold != plugin_config_get( 'view_threshold' ) ) {
	plugin_config_set( 'view_threshold', $f_view_threshold );
}

if ( $f_manage_threshold != plugin_config_get( 'manage_threshold' ) ) {
	plugin_config_set( 'manage_threshold', $f_manage_threshold );
}

if ( ! $f_buglink_reset_1 ) {
	if ( $f_buglink_regex_1 != plugin_config_get( 'buglink_regex_1' ) ) {
		plugin_config_set( 'buglink_regex_1', $f_buglink_regex_1 );
	}
} else {
	plugin_config_delete( 'buglink_regex_1' );
}

if ( ! $f_buglink_reset_2 ) {
	if ( $f_buglink_regex_2 != plugin_config_get( 'buglink_regex_2' ) ) {
		plugin_config_set( 'buglink_regex_2', $f_buglink_regex_2 );
	}
} else {
	plugin_config_delete( 'buglink_regex_2' );
}

if ( $f_manage_threshold != plugin_config_get( 'manage_threshold' ) ) {
	plugin_config_set( 'manage_threshold', $f_manage_threshold );
}

if ( $f_remote_checkin != plugin_config_get( 'remote_checkin' ) ) {
	plugin_config_set( 'remote_checkin', $f_remote_checkin );
}

plugin_config_set( 'checkin_urls', serialize( $t_checkin_urls ) );
plugin_config_set( 'import_urls', serialize( $t_import_urls ) );

print_successful_redirect( plugin_page( 'manage_config_page', true ) );

