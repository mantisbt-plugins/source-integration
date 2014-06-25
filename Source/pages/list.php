<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 1 );
$f_perpage = 25;

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_stats = $t_repo->stats( false );
$t_changesets = SourceChangeset::load_by_repo( $t_repo->id, true, $f_offset, $f_perpage );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<table class="width100" cellspacing="1" align="center">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'changesets' ), ': ', $t_repo->name ?></td>
<td class="right" colspan="2">
<?php
if ( access_has_global_level( plugin_config_get( 'manage_threshold' ) ) ) {
	print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'manage' ) );
}
print_bracket_link( plugin_page( 'search_page' ), plugin_lang_get( 'search' ) );
if ( $t_url = $t_vcs->url_repo( $t_repo ) ) {
	print_bracket_link( $t_url, plugin_lang_get( 'browse' ) );
}
print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
?>
</td>
</tr>

<?php Source_View_Changesets( $t_changesets, array( $t_repo->id => $t_repo ), false ) ?>


<tr>
<td colspan="4" class="center">

<?php #PAGINATION
Source_View_Pagination(
	plugin_page('list') . '&id=' . $t_repo->id,
	$f_offset,
	$t_stats['changesets'],
	$f_perpage
);
?>
</td>
</tr>

</table>

<?php
html_page_bottom1( __FILE__ );
