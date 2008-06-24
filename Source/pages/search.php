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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

$f_offset = gpc_get_int( 'offset', 1 );
$f_perpage = 25;

require_once( config_get( 'plugin_path' ) . 'Source' . DIRECTORY_SEPARATOR . 'Source.FilterAPI.php' );

# Generate listing
list( $t_filter, $t_permalink ) = Source_Generate_Filter();
list( $t_changesets, $t_count ) = $t_filter->find( $f_offset );
$t_repos = SourceRepo::load_by_changesets( $t_changesets );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

?>

<br/>
<table class="width100" align="center" cellspacing="1">

<tr>
<td class="form-title"><?php echo plugin_lang_get( 'search' ), ' ', plugin_lang_get( 'changesets' ) ?></td>
<td class="right" colspan="2">
<?php
print_bracket_link( plugin_page( 'search' ) . $t_permalink, "Permalink" );
print_bracket_link( plugin_page( 'search_page' ) . $t_permalink, "Modify Search" );
print_bracket_link( plugin_page( 'search_page' ), "New Search" );
?>
</td>
</tr>

<?php
foreach( $t_changesets as $t_changeset ) {
	$t_changeset->load_files();
	$t_changeset->load_bugs();
	$t_repo = $t_repos[ $t_changeset->repo_id ];
	$t_rows = count( $t_changeset->files ) + 1;
?>

<tr class="row-1">
<td class="category" width="25%" rowspan="<?php echo $t_rows ?>">
	<strong><?php echo string_display( $t_repo->name ), ' ', event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset ) ) ?></strong><br/>
	<span class="small"><?php echo "Timestamp: ", $t_changeset->timestamp ?></span><br/>
	<span class="small"><?php echo "Author: ", $t_changeset->author ?></span>
</td>
<td colspan="2"><?php echo string_display_links( $t_changeset->message ) ?></td>
</tr>

<?php foreach ( $t_changeset->files as $t_file ) { ?>

<tr class="row-2">
<td><?php echo string_display_line( event_signal( 'EVENT_SOURCE_SHOW_FILE', array( $t_repo, $t_changeset, $t_file ) ) ) ?></td>
<td class="center" width="15%">
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE_DIFF', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'diff', 'Source' ) ) ?>
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'file', 'Source' ) ) ?>
</td>
</tr>

<?php } ?>

<tr><td class="spacer"></td></tr>

<?php } ?>

<tr>
<td colspan="3" class="center">

<?php #PAGINATION

if ( $t_count > $f_perpage ) {
	$t_page = 1;
	while( $t_count > 0 ) {
		if ( $t_page > 1 && $t_page % 15 != 1 ) {
			echo ', ';
		}

		if ( $t_page == $f_offset ) {
			echo " $t_page";
		} else {
			echo ' <a href="', plugin_page( 'search' ), '&offset=', $t_page, $t_permalink, '">', $t_page, '</a>';
		}

		if ( $t_page % 15 == 0 ) {
			echo '<br/>';
		}

		$t_count -= $f_perpage;
		$t_page ++;
	}
}
?>
</td>
</tr>

</table>

<?php
html_page_bottom1( __FILE__ );

