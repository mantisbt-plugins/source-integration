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
$t_count = $t_stats['changesets'];

if ( $t_count > $f_perpage ) {

	$t_pages = ceil( $t_count / $f_perpage );
	$t_block = max( 5, min( 20, ceil( $t_pages / 6 ) ) );
	$t_current = $f_offset;
	$t_page_set = array();

	$t_page_link_body = "if ( is_null( \$t ) ) { \$t = \$p; }
		return ( is_null( \$p ) ? '...' : ( \$p == $t_current ? \"<strong>\$p</strong>\" :
		'<a href=\"' . plugin_page( 'list' ) . '&id=$t_repo->id' . '&offset=' . \$p . '\">' . \$t . '</a>' ) );";
	$t_page_link = create_function( '$p, $t=null', $t_page_link_body ) or die( 'gah' );

	if ( $t_pages > 15 ) {
		$t_used_page = false;
		for( $i = 1; $i <= $t_pages; $i++ ) {
			if ( $i <= 3 || $i > $t_pages-3 ||
				( $i >= $t_current-5 && $i <= $t_current+5 ) ||
				$i % $t_block == 0) {

				$t_page_set[] = $i;
				$t_used_page = true;
			} else if ( $t_used_page ) {
				$t_page_set[] = null;
				$t_used_page = false;
			}
		}

	} else {
		$t_page_set = range( 1, $t_pages );
	}

	if ( $t_current > 1 ) {
		echo $t_page_link( $f_offset-1, '<<' ), '&nbsp;&nbsp;';
	}

	$t_page_set = array_map( $t_page_link, $t_page_set );
	echo join( ' ', $t_page_set );

	if ( $t_current < $t_pages ) {
		echo '&nbsp;&nbsp;', $t_page_link( $f_offset+1, '>>' );
	}

}
?>
</td>
</tr>

</table>

<?php
html_page_bottom1( __FILE__ );

