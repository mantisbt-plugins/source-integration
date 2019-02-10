<?php
# Copyright (c) 2012 Morgan Aldridge
# Copyright (c) 2019 Damien Regad
# Licensed under the MIT license

auth_reauthenticate();

$f_repo_id = gpc_get_int( 'id' );
$f_code = gpc_get_string( 'code' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_authorized = SourceGithubPlugin::oauth_get_access_token( $t_repo, $f_code );
$t_redirect_url = plugin_page( 'repo_update_page', false, 'Source' ) . '&id=' . $t_repo->id;

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

if( $t_authorized ) {
	html_operation_successful( $t_redirect_url, plugin_lang_get( 'repo_authorized' ) );
} else {
	html_operation_failure( $t_redirect_url, plugin_lang_get( 'repo_authorization_failed' ) );
}

layout_page_end();
