<?php

//require_once( config_get( 'plugin_path' ) . 'SourceGithub/SourceGithub.php' );

auth_reauthenticate();

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

$f_repo_id = gpc_get_int( 'id' );
$f_code = gpc_get_string( 'code' );

$t_repo = SourceRepo::load( $f_repo_id );
if ( SourceGithubPlugin::oauth_get_access_token( $t_repo, $f_code ) === true ) {
	echo '<p>Your app is now authorized with GitHub.</p>';
} else {
	echo '<p>Sorry, your app could not be authorized with GitHub.</p>';
}

html_page_bottom1( __FILE__ );

?>