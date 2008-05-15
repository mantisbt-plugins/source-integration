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

$f_repo_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 1 );
$f_perpage = 25;

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

$t_stats = $t_repo->stats();
$t_changesets = SourceChangeset::load_by_repo( $t_repo->id, false, $f_offset, $f_perpage );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<table class="width100" cellspacing="1" align="center">

<tr>
<td class="form-title"><?php echo $t_repo->name ?></td>
<tr>

<tr class="row-category">
<td><?php echo "Revision" ?></td>
<td><?php echo "Author" ?></td>
<td><?php echo "Message" ?></td>
<td><?php echo "Timestamp" ?></td>
<td><?php echo "Actions" ?></td>
</tr>

<?php foreach( $t_changesets as $t_changeset ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td><?php echo event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset ) ) ?></td>
<td><?php echo $t_changeset->author ?></td>
<td><?php echo $t_changeset->message ?></td>
<td><?php echo $t_changeset->timestamp ?></td>
<td><?php print_bracket_link( plugin_page( 'view' ) . '&id=' . $t_changeset->id, "Details" ) ?></td>
</tr>

<?php } ?>

</table>

<div class="center">
<?php
$t_count = $t_stats['changesets'];

if ( $t_count > $f_perpage ) {
	$t_page = 1;
	while( $t_count > 0 ) {
		if ( $t_page > 1 && $t_page % 10 != 1 ) {
			echo ', ';
		}

		if ( $t_page == $f_offset ) {
			echo " $t_page";
		} else {
			echo ' <a href="', plugin_page( 'list' ), '&id=', $t_repo->id, '&offset=', $t_page, '">', $t_page, '</a>';
		}

		if ( $t_page % 10 == 0 ) {
			echo '<br/>';
		}

		$t_count -= $f_perpage;
		$t_page ++;
	}
}
?>
</div>

<?php
html_page_bottom1( __FILE__ );

