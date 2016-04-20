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

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'changesets' ), ': ', $t_repo->name ?>
			</h4>
			<div class="widget-toolbar">
				<?php
					if( access_has_global_level( plugin_config_get( 'manage_threshold' ) ) ) {
						print_bracket_link( plugin_page( 'repo_manage_page' )
							. '&id=' . $t_repo->id, plugin_lang_get( 'manage' )
						);
					}
					print_bracket_link( plugin_page( 'search_page' ), plugin_lang_get( 'search' ) );
					if( $t_url = $t_vcs->url_repo( $t_repo ) ) {
						print_bracket_link( $t_url, plugin_lang_get( 'browse' ) );
					}
					print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
				?>
			</div>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">

	<table class="table table-striped table-bordered table-condensed table-hover">
		<?php
			Source_View_Changesets(
				$t_changesets,
				array( $t_repo->id => $t_repo ),
				false
			);
		?>
	</table>
				</div>
			</div>

	<div class="widget-toolbox padding-8 clearfix">
		<?php
			Source_View_Pagination(
				plugin_page('list') . '&id=' . $t_repo->id,
				$f_offset,
				$t_stats['changesets'],
				$f_perpage
			);
		?>
	</div>
		</div>
	</div>
</div>

<?php
layout_page_end( __FILE__ );
