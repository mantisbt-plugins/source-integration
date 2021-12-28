<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_update = access_has_global_level( plugin_config_get( 'update_threshold' ) );

/** @noinspection PhpIncludeInspection */
require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_files();
$t_changeset->load_bugs();

# Get the list of related bugs the user has access to
$t_view_bug_threshold = config_get('view_bug_threshold');
$t_visible_bugs = array_filter(
	$t_changeset->bugs,
	function( $p_bug_id ) use ( $t_view_bug_threshold ) {
		return bug_exists( $p_bug_id)
			&& access_has_bug_level( $t_view_bug_threshold, $p_bug_id );
	}
);
bug_cache_array_rows( $t_visible_bugs );
$t_bug_rows = array();
foreach( $t_visible_bugs as $t_bug_id ) {
	/** @noinspection PhpUnhandledExceptionInspection */
	$t_bug_rows[$t_bug_id] = bug_get_row( $t_bug_id );
}

$t_affected_rowspan = count( $t_visible_bugs ) + ( $t_can_update ? 1 : 0 );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	error_parameters( $f_changeset_id );
	plugin_error( SourcePlugin::ERROR_REPO_MISSING_CHANGESET );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();

if ( $t_changeset->parent ) {
	$t_changeset_parent = SourceChangeset::load_by_revision( $t_repo, $t_changeset->parent );
} else {
	$t_changeset_parent = null;
}

$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_use_porting = plugin_config_get( 'enable_porting' );

$t_columns =
	( $t_use_porting ? 1 : 0 ) +
	5;

$t_update_form = $t_use_porting && $t_can_update;

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>

<div class="widget-box widget-color-blue2">
	<div class="widget-header widget-header-small">
		<h4 class="widget-title lighter">
			<?php echo string_display_line( $t_repo->name ), ': ', $t_vcs->show_changeset( $t_repo, $t_changeset ) ?>
		</h4>
	</div>

	<div class="widget-body">
		<div class="widget-main no-padding">
			<div class="table-responsive">
				<div class="widget-toolbox padding-8 clearfix">
<?php
	if ( $t_url = $t_vcs->url_changeset( $t_repo, $t_changeset ) ) {
		print_extra_small_button($t_url, plugin_lang_get('diff', 'Source') );
		echo ' ';
	}
	print_extra_small_button(
		plugin_page( 'list' ) . '&id=' . $t_repo->id . '&offset=' . $f_offset,
		plugin_lang_get( 'back_repo' )
	);
?>
				</div>

<table class="table table-striped table-bordered table-condensed">
<tbody>

<tr>
<th class="category"><?php echo plugin_lang_get( 'author' ) ?></th>
<th class="category"><?php echo plugin_lang_get( 'committer' ) ?></th>
<th class="category"><?php echo plugin_lang_get( 'branch' ) ?></th>
<th class="category"><?php echo plugin_lang_get( 'timestamp' ) ?></th>
<th class="category"><?php echo plugin_lang_get( 'parent' ) ?></th>
<?php if ( $t_use_porting ) { ?>
<th class="category"><label for="ported"><?php echo plugin_lang_get( 'ported' ) ?></label></th>
<?php } ?>
</tr>

<tr>
<td class="center"><?php Source_View_Author( $t_changeset ) ?></td>
<td class="center"><?php Source_View_Committer( $t_changeset ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->branch ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->getLocalTimestamp() ) ?></td>

<td class="center"><?php
	if ( $t_changeset_parent ) {
		print_link(
			plugin_page( 'view' ) . '&id=' . $t_changeset_parent->id,
			$t_vcs->show_changeset( $t_repo, $t_changeset_parent )
		);
	}
?>
</td>

<?php
	if ( $t_use_porting ) {
?>
<td class="center">
<?php
		if ( $t_update_form ) {
?>
	<form action="<?php echo plugin_page( 'update' ) ?>" method="post">
		<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
		<?php echo form_security_field( 'plugin_Source_update' ) ?>
		<select id="ported" name="ported">
			<option value="" <?php check_selected( "", $t_changeset->ported ) ?>>
				<?php echo plugin_lang_get( 'pending' ) ?>
			</option>
			<option value="0" <?php check_selected( "0", $t_changeset->ported ) ?>>
				<?php echo plugin_lang_get( 'na' ) ?>
			</option>
			<option value="">--</option>
<?php
			foreach( $t_repo->branches as $t_branch ) {
				if ( $t_branch == $t_changeset->branch ) {
					continue;
				}
?>
			<option value="<?php echo string_attribute( $t_branch ) ?>" <?php check_selected( $t_branch, $t_changeset->ported ) ?>>
				<?php echo string_display_line( $t_branch ) ?>
			</option>
<?php
			}
?>
		</select>
		<button class="btn btn-sm btn-primary btn-white btn-round"><?php echo plugin_lang_get( 'update' ) ?></button>
	</form>
<?php
		} else {
			switch( $t_changeset->ported ) {
				case '0':
					echo plugin_lang_get( 'na' );
					break;
				case '':
					echo plugin_lang_get( 'pending' );
					break;
				default:
					echo string_display_line( $t_changeset->ported );
			}
		}
?>
</td>
<?php
	}
?>
</tr>

<?php if ( $t_affected_rowspan > 0 ) { ?>
<tr class="spacer"></tr>

<tr>
<th class="category" rowspan="<?php echo $t_affected_rowspan ?>">
	<?php echo plugin_lang_get( 'affected_issues' ) ?>
</th>
<?php } ?>

<?php
$t_first = true;
$t_user_id = auth_get_current_user_id();
$t_security_token = form_security_param( 'plugin_Source_detach' );

foreach ( $t_bug_rows as $t_bug_id => $t_bug_row ) {
	$t_color_class = html_get_status_css_fg(
		$t_bug_row['status'],
		$t_user_id,
		$t_bug_row['project_id']
	);
	$t_status_description = get_enum_element(
		'status',
		$t_bug_row['status'],
		$t_bug_row['project_id']
	);

	echo ( $t_first ? '' : "<tr>\n" );
?>
<td colspan="<?php echo $t_columns-( $t_can_update ? 2 : 1 ) ?>"><?php
	# Status color box with tooltip
	echo '<i class="fa fa-square fa-status-box ' . $t_color_class
		. '" title="' . string_attribute( $t_status_description ) . '"></i>&nbsp;';

	# Issue ID and description
	echo '<a href="view.php?id=', $t_bug_id, '">',
		bug_format_id( $t_bug_id ), '</a>: ',
		string_display_line( $t_bug_row['summary'] ) ?>
</td>
<?php if ( $t_can_update ) { ?>
<td class="center"><?php
	$t_param = array( 'id' => $t_changeset->id, 'bug_id' => $t_bug_id);
	print_small_button(
		plugin_page( 'detach' )
		. '&' . http_build_query( $t_param ) . $t_security_token,
		plugin_lang_get( 'detach' )
	) ?>
</td>
<?php } ?>
</tr>

<?php
	$t_first = false;
} # foreach

if ( $t_can_update ) {
	if ( !$t_first ) { ?>
<tr>
<?php } ?>
<td colspan="<?php echo $t_columns-1 ?>">
<form action="<?php echo plugin_page( 'attach' )  ?>" method="post">
	<?php echo form_security_field( 'plugin_Source_attach' ) ?>
	<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
	<label for="bug_ids"><?php echo plugin_lang_get( 'attach_to_issue' ) ?></label>
	<input id="bug_ids" name="bug_ids" type="text" class="input-sm" size="15"/>
	<button class="btn btn-sm btn-primary btn-white btn-round"><?php echo plugin_lang_get( 'attach' ) ?></button>
</form>
</td>
</tr>
<?php } ?>

<tr class="spacer"></tr>

<tr>
<th class="category" rowspan="<?php echo count( $t_changeset->files ) + 1 ?>">
	<?php echo plugin_lang_get( 'changeset' ) ?>
</th>
<td colspan="<?php echo $t_columns-1 ?>"><?php echo string_display_links( $t_changeset->message ) ?></td>
</tr>

<?php foreach ( $t_changeset->files as $t_file ) { ?>
<tr>
<td class="small" colspan="<?php echo $t_columns-2 ?>"><?php echo string_display_line( $t_vcs->show_file( $t_repo, $t_changeset, $t_file ) ) ?></td>
<td class="center">
<?php
	print_extra_small_button(
		$t_vcs->url_diff( $t_repo, $t_changeset, $t_file ),
		plugin_lang_get( 'diff', 'Source' )
	);
	echo ' ';
	print_extra_small_button(
		$t_vcs->url_file( $t_repo, $t_changeset, $t_file ),
		plugin_lang_get( 'file', 'Source' )
	);
?>
</td>
</tr>

<?php } ?>

</tbody>
</table>
			</div>
		</div>
		<?php if ( $t_can_update ) { ?>
			<div class="widget-toolbox padding-8 clearfix">
				<form action="<?php echo helper_mantis_url( 'plugin.php' ) ?>" method="get">
				<input type="hidden" name="page" value="Source/edit_page"/>
				<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
					<button class="btn btn-primary btn-white btn-round"><?php echo plugin_lang_get( 'edit' ) ?></button>
				</form>
			</div>
		<?php } ?>
	</div>
</div>

</div>

<?php
layout_page_end();
