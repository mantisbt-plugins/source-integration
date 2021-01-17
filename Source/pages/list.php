<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

/** @noinspection PhpIncludeInspection */
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
				<?php echo plugin_lang_get( 'changesets' ), ': ', string_display_line( $t_repo->name ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">

					<div class="widget-toolbox padding-8 clearfix">
						<?php
							if( access_has_global_level( plugin_config_get( 'manage_threshold' ) ) ) { ?>
								<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id ?>">
									<?php echo plugin_lang_get( 'manage' ) ?>
								</a>
							<?php } ?>
								<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'search_page' ) ?>">
									<?php echo plugin_lang_get( 'search' ) ?>
								</a>
							<?php
							if( $t_url = $t_vcs->url_repo( $t_repo ) ) { ?>
								<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo string_display_line( $t_url ) ?>">
									<?php echo plugin_lang_get( 'browse' ) ?>
								</a>
							<?php } ?>
								<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'index' ) ?>">
									<?php echo plugin_lang_get( 'back' ) ?>
								</a>
					</div>
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
layout_page_end();
