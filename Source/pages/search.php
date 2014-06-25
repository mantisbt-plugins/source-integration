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
list( $t_changesets, $t_count ) = $t_filter->find( $f_offset );
$t_repos = SourceRepo::load_by_changesets( $t_changesets );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

?>

<br>

<div class="table-container">

	<h2><?php echo plugin_lang_get( 'search_changesets' ) ?></h2>

	<div class="right">
		<?php
			print_bracket_link( plugin_page( 'search' ) . $t_permalink, plugin_lang_get( 'permalink' ) );
			print_bracket_link( plugin_page( 'search_page' ) . $t_permalink, plugin_lang_get( 'modify_search' ) );
			print_bracket_link( plugin_page( 'search_page' ), plugin_lang_get( 'new_search' ) );
			print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
		?>
	</div>

	<table>
		<?php Source_View_Changesets( $t_changesets, $t_repos ); ?>
	</table>

	<div style="text-align: center;">
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

<?php
html_page_bottom1( __FILE__ );
