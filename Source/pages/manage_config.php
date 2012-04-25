<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

form_security_validate( 'plugin_Source_manage_config' );
auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_view_threshold = gpc_get_int( 'view_threshold' );
$f_update_threshold = gpc_get_int( 'update_threshold' );
$f_manage_threshold = gpc_get_int( 'manage_threshold' );
$f_username_threshold = gpc_get_int( 'username_threshold' );

$f_show_repo_link = gpc_get_bool( 'show_repo_link', OFF );
$f_show_search_link = gpc_get_bool( 'show_search_link', OFF );
$f_show_repo_stats = gpc_get_bool( 'show_repo_stats', OFF );

$f_enable_linking = gpc_get_bool( 'enable_linking', OFF );
$f_enable_mapping = gpc_get_bool( 'enable_mapping', OFF );
$f_enable_resolving = gpc_get_bool( 'enable_resolving', OFF );
$f_enable_message = gpc_get_bool( 'enable_message', OFF );
$f_enable_porting = gpc_get_bool( 'enable_porting', OFF );
$f_enable_product_matrix = gpc_get_bool( 'enable_product_matrix', OFF );

$f_buglink_regex_1 = gpc_get_string( 'buglink_regex_1' );
$f_buglink_reset_1 = gpc_get_string( 'buglink_reset_1', OFF );
$f_buglink_regex_2 = gpc_get_string( 'buglink_regex_2' );
$f_buglink_reset_2 = gpc_get_string( 'buglink_reset_2', OFF );

$f_bugfix_regex_1 = gpc_get_string( 'bugfix_regex_1' );
$f_bugfix_reset_1 = gpc_get_string( 'bugfix_reset_1', OFF );
$f_bugfix_regex_2 = gpc_get_string( 'bugfix_regex_2' );
$f_bugfix_reset_2 = gpc_get_string( 'bugfix_reset_2', OFF );
$f_bugfix_status = gpc_get_int( 'bugfix_status' );
$f_bugfix_resolution = gpc_get_int( 'bugfix_resolution' );
$f_bugfix_status_pvm = gpc_get_int( 'bugfix_status_pvm', plugin_config_get( 'bugfix_status_pvm' ) );
$f_bugfix_handler = gpc_get_bool( 'bugfix_handler' );
$f_bugfix_message = gpc_get_string( 'bugfix_message' );

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

$f_remote_imports = gpc_get_bool( 'remote_imports', OFF );
$f_import_urls = gpc_get_string( 'import_urls' );

$t_checkin_urls = check_urls( $f_checkin_urls );
$t_import_urls = check_urls( $f_import_urls );

$f_api_key = gpc_get_string( 'api_key' );

function maybe_set_option( $name, $value ) {
	if ( $value != plugin_config_get( $name ) ) {
		plugin_config_set( $name, $value );
	}
}

maybe_set_option( 'view_threshold', $f_view_threshold );
maybe_set_option( 'update_threshold', $f_update_threshold );
maybe_set_option( 'manage_threshold', $f_manage_threshold );
maybe_set_option( 'username_threshold', $f_username_threshold );

maybe_set_option( 'show_repo_link', $f_show_repo_link );
maybe_set_option( 'show_search_link', $f_show_search_link );
maybe_set_option( 'show_repo_stats', $f_show_repo_stats );

maybe_set_option( 'enable_linking', $f_enable_linking );
maybe_set_option( 'enable_mapping', $f_enable_mapping );
maybe_set_option( 'enable_resolving', $f_enable_resolving );
maybe_set_option( 'enable_message', $f_enable_message );
maybe_set_option( 'enable_porting', $f_enable_porting );
maybe_set_option( 'enable_product_matrix', $f_enable_product_matrix );

if ( ! $f_buglink_reset_1 ) {
	maybe_set_option( 'buglink_regex_1', $f_buglink_regex_1 );
} else {
	plugin_config_delete( 'buglink_regex_1' );
}

if ( ! $f_buglink_reset_2 ) {
	maybe_set_option( 'buglink_regex_2', $f_buglink_regex_2 );
} else {
	plugin_config_delete( 'buglink_regex_2' );
}

if ( ! $f_bugfix_reset_1 ) {
	maybe_set_option( 'bugfix_regex_1', $f_bugfix_regex_1 );
} else {
	plugin_config_delete( 'bugfix_regex_1' );
}

if ( ! $f_bugfix_reset_2 ) {
	maybe_set_option( 'bugfix_regex_2', $f_bugfix_regex_2 );
} else {
	plugin_config_delete( 'bugfix_regex_2' );
}

maybe_set_option( 'bugfix_status', $f_bugfix_status );
maybe_set_option( 'bugfix_resolution', $f_bugfix_resolution );
maybe_set_option( 'bugfix_status_pvm', $f_bugfix_status_pvm );
maybe_set_option( 'bugfix_handler', $f_bugfix_handler );
maybe_set_option( 'bugfix_message', $f_bugfix_message );

maybe_set_option( 'remote_checkin', $f_remote_checkin );
maybe_set_option( 'checkin_urls', serialize( $t_checkin_urls ) );
maybe_set_option( 'remote_imports', $f_remote_imports );
maybe_set_option( 'import_urls', serialize( $t_import_urls ) );

maybe_set_option( 'api_key', $f_api_key );

foreach( SourceVCS::all() as $t_type => $t_vcs ) {
	if ( $t_vcs->configuration ) {
		$t_vcs->update_config();
	}
}

form_security_purge( 'plugin_Source_manage_config' );

print_successful_redirect( plugin_page( 'manage_config_page', true ) );

