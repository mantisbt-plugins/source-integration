<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

require_once( config_get( 'plugin_path' ) . 'Source' . DIRECTORY_SEPARATOR . 'Source.FilterAPI.php' );

list( $t_filter, $t_permalink ) = Source_Generate_Filter();

$t_date_start = ( is_null( $t_filter->filters['date_start']->value ) ? 'start' : $t_filter->filters['date_start']->value );
$t_date_end = ( is_null( $t_filter->filters['date_end']->value ) ? 'now' : $t_filter->filters['date_end']->value );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

?>

<?php if ( plugin_is_loaded( 'jQuery' ) ) { ?>
<script src="<?php echo plugin_file( 'search.js' ) ?>"></script>
<?php } ?>

<br/>

<div class="form-container">
<form action="<?php echo helper_mantis_url( 'plugin.php' ) ?>" method="get">

	<h2><?php echo plugin_lang_get( 'search_changesets' ) ?></h2>

	<div class="floatright">
		<?php
			print_bracket_link( plugin_page( 'search_page' ), plugin_lang_get( 'new_search' ) );
			print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
		?>
	</div>

	<fieldset>
		<input type="hidden" name="page" value="Source/search"/>

		<table>
			<thead>
				<tr class="row-category">
					<th><?php echo plugin_lang_get( 'type' ) ?></th>
					<th><?php echo plugin_lang_get( 'repository' ) ?></th>
					<th><?php echo plugin_lang_get( 'branch' ) ?></th>
					<th><?php echo plugin_lang_get( 'action' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="center" width="25%"><?php Source_Type_Select( $t_filter->filters['r.type']->value ) ?></td>
					<td class="center" width="25%"><?php Source_Repo_Select( $t_filter->filters['r.id']->value ) ?></td>
					<td class="center" width="25%"><?php Source_Branch_Select( $t_filter->filters['c.branch']->value ) ?></td>
					<td class="center" width="25%"><?php Source_Action_Select( $t_filter->filters['f.action']->value ) ?></td>
				</tr>
				<tr class="spacer"></tr>
			</tbody>

			<thead>
				<tr class="row-category">
					<th><?php echo plugin_lang_get( 'username' ) ?></th>
					<th><?php echo plugin_lang_get( 'author' ) ?></th>
					<th><?php echo plugin_lang_get( 'revision' ) ?></th>
					<th><?php echo plugin_lang_get( 'issue' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="center" ><?php Source_Username_Select( $t_filter->filters['c.user_id']->value ) ?></td>
					<td class="center"><?php Source_Author_Select( $t_filter->filters['c.author']->value ) ?></td>
					<td class="center"><input name="revision" size="10" value="<?php echo string_attribute( $t_filter->filters['f.revision']->value ) ?>"/></td>
					<td class="center"><input name="bug_id" size="10" value="<?php echo string_attribute( join( ',', $t_filter->filters['b.bug_id']->value ) ) ?>"/></td>
				</tr>
			</tbody>
		</table>

		<div class="field-container spacer">
			<label for="date_begin" style="width: 25%">
				<span><?php echo plugin_lang_get( 'date_begin' ) ?></span>
			</label>
			<span class="select">
				<?php Source_Date_Select( 'date_start', $t_date_start ); ?>
			</span>
			<span class="label-style" style="width: 25%"></span>
		</div>

		<div class="field-container">
			<label for="date_end" style="width: 25%">
				<span><?php echo plugin_lang_get( 'date_end' ) ?></span>
			</label>
			<span class="select">
				<?php Source_Date_Select( 'date_end', $t_date_end); ?>
			</span>
			<span class="label-style" style="width: 25%"></span>
		</div>

<?php if ( plugin_config_get( 'enable_porting' ) ): ?>
		<div class="field-container spacer">
			<label for="enable_porting" style="width: 25%">
				<span><?php echo plugin_lang_get( 'enable_porting' ) ?></span>
			</label>
			<span class="select">
				<?php Source_Ported_Select( $t_filter->filters['c.ported']->value ); ?>
			</span>
			<span class="label-style" style="width: 25%"></span>
		</div>
<?php endif ?>

		<div class="field-container spacer">
			<label for="message" style="width: 25%">
				<span><?php echo plugin_lang_get( 'message' ) ?></span>
			</label>
			<span class="input">
				<input name="message" size="40" value="<?php
					echo string_attribute( $t_filter->filters['c.message']->value ) ?>" />
			</span>
			<span class="label-style" style="width: 25%"></span>
		</div>

		<div class="field-container">
			<label for="filename" style="width: 25%">
				<span><?php echo plugin_lang_get( 'filename' ) ?></span>
			</label>
			<span class="input">
				<input name="filename" size="40" value="<?php
					echo string_attribute( $t_filter->filters['f.filename']->value ) ?>" />
			</span>
			<span class="label-style" style="width: 25%"></span>
		</div>

		<div class="submit-button">
			<input class="button" type="submit" value="<?php echo plugin_lang_get( 'search' ) ?>" />
		</div>

	</fieldset>
</form>
</div>

<?php
html_page_bottom1( __FILE__ );
