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

$f_remote_checkin = gpc_get_bool( 'remote_checkin', OFF );
$f_checkin_urls = gpc_get_string( 'checkin_urls' );

$t_checkin_urls_gpc = explode( "\n", $f_checkin_urls );
$t_checkin_urls = array();

foreach( $t_checkin_urls_gpc as $t_checkin_url ) {
	$t_checkin_url = trim( $t_checkin_url );
	if ( is_blank( $t_checkin_url ) || in_array( $t_checkin_url, $t_checkin_urls ) ) {
		continue;
	}

	$t_checkin_urls[] = $t_checkin_url;
}

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

print_successful_redirect( plugin_page( 'manage_config_page', true ) );

