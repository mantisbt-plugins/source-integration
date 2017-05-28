<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

helper_begin_long_process();

form_security_validate( 'plugin_Source_repo_import_latest' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );
helper_ensure_confirmed( plugin_lang_get( 'ensure_import_latest' ), plugin_lang_get( 'import_latest' ) );

$f_repo_id = strtolower( gpc_get_string( 'id' ) );

$t_repo_id = (int) $f_repo_id;
$t_repos = array( SourceRepo::load( $t_repo_id ) );

$t_repo = array_shift( $t_repos );
$t_vcs = SourceVCS::repo( $t_repo );

$t_repo->pre_stats = $t_repo->stats();

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

<div class="widget-box widget-color-blue2">
	<div class="widget-body">
		<div class="widget-main no-padding">
			<div class="table-responsive">
<table class="table table-striped table-bordered table-condensed">

<tr>
	<td class="" colspan="2"><?php echo plugin_lang_get( 'import_results' ) ?></td>
</tr>

<?php
# keep checking for more changesets to import
$t_repo->import_error = false;
while( true ) {

	# import the next batch of changesets
	$t_changesets = $t_vcs->import_latest( $t_repo );

	# check for errors
	if ( !is_array( $t_changesets ) ) {
		$t_repo->import_error = true;
		break;
	}

	# if no more entries, we're done
	if ( count( $t_changesets ) < 1 ) {
		break;
	}

	Source_Process_Changesets( $t_changesets );
}

$t_repo->post_stats = $t_repo->stats();
?>

<tr>
	<td class="category"><?php echo string_display_line( $t_repo->name ) ?></td>
<td>
<?php
if ( $t_repo->import_error ) {
	echo plugin_lang_get( 'import_latest_failed' ), '<br/>';
}

$t_stats = $t_repo->post_stats;
$t_stats['changesets'] -= $t_repo->pre_stats['changesets'];
$t_stats['files'] -= $t_repo->pre_stats['files'];
$t_stats['bugs'] -= $t_repo->pre_stats['bugs'];

echo sprintf( plugin_lang_get( 'import_stats' ), $t_stats['changesets'], $t_stats['files'], $t_stats['bugs'] );
?>
</td>
</tr>

<tr>
<td colspan="2" class="center">
	<?php print_small_button( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) ) ?>
</td>
</tr>

</table>
</div></div></div></div></div>
<?php
layout_page_end();
form_security_purge( 'plugin_Source_repo_import_latest' );

