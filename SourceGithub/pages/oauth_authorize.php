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
	$t_was_authorized = true;
} else {
	$t_was_authorized = false;
}
?>

<table class="width60" align="center" cellspacing="1">

<tr>
<td class="form-title"><?php echo plugin_lang_get( 'oauth_authorization' ) ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'repo_manage_page', false, 'Source' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) ) ?></td>
</tr>

<tr>
<td class="center" colspan="2"><?php echo $t_was_authorized === true ? plugin_lang_get('repo_authorized') : plugin_lang_get('repo_authorization_failed'); ?></td>
</tr>

</table>

<?php
html_page_bottom1( __FILE__ );

?>