<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

$f_offset = gpc_get_int( 'offset', 1 );
$f_perpage = 25;

require_once( config_get( 'plugin_path' ) . 'Source' . DIRECTORY_SEPARATOR . 'Source.FilterAPI.php' );

# Generate listing
list( $t_filter, $t_permalink ) = Source_Generate_Filter();
list( $t_changesets, $t_count ) = $t_filter->find( $f_offset, $f_perpage );
$t_repos = SourceRepo::load_by_changesets( $t_changesets );

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'search_changesets' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">

					<div class="widget-toolbox padding-8 clearfix">
						<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'search' ) . string_attribute( $t_permalink ) ?>">
							<?php echo plugin_lang_get( 'permalink' ) ?>
						</a>
						<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'search_page' ) . string_attribute( $t_permalink ) ?>">
							<?php echo plugin_lang_get( 'modify_search' ) ?>
						</a>
						<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'search_page' ) ?>">
							<?php echo plugin_lang_get( 'new_search' ) ?>
						</a>
						<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'index' ) ?>">
							<?php echo plugin_lang_get( 'back' ) ?>
						</a>
					</div>
	<table class="table table-striped table-bordered table-condensed table-hover">
		<?php Source_View_Changesets( $t_changesets, $t_repos ); ?>
	</table>
				</div>
			</div>

	<div class="widget-toolbox padding-8 clearfix">
		<?php
			Source_View_Pagination(
				plugin_page('search') . $t_permalink,
				$f_offset,
				$t_count,
				$f_perpage
			);
		?>
	</div>
		</div>
	</div>
</div>
<?php
layout_page_end();