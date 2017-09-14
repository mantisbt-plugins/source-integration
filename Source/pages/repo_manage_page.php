<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

$t_mappings = $t_repo->load_mappings();


function display_strategies( $p_type=null ) {
	$t_strategies = array();
	if ( is_null( $p_type ) ) {
		$t_strategies[] = array( 0, 'select_one' );
	}
	$t_strategies[] = array( SOURCE_EXPLICIT, 'mapping_explicit' );
	if( !Source_PVM() ) {
		$t_strategies[] = array( SOURCE_NEAR, 'mapping_near' );
		$t_strategies[] = array( SOURCE_FAR, 'mapping_far' );
		$t_strategies[] = array( SOURCE_FIRST, 'mapping_first' );
		$t_strategies[] = array( SOURCE_LAST, 'mapping_last' );
	}

	foreach( $t_strategies as $t_strategy ) {
		echo "\n" . '<option value="' . $t_strategy[0] . '"';
		check_selected( (int)$p_type, $t_strategy[0] );
		echo '>' . plugin_lang_get( $t_strategy[1] ) . '</option>';
	}
}

function display_pvm_versions($t_version_id=null) {
	static $s_products = null;

	if ( is_null( $s_products ) ) {
		$s_products = PVMProduct::load_all( true );
	}

	if ( is_null( $t_version_id ) ) {
		echo "\n" . '<option value=""></option>';
	}

	foreach( $s_products as $t_product ) {
		foreach( $t_product->versions as $t_version ) {
			echo "\n" . '<option value="' . $t_version->id . '"';
			check_selected( $t_version->id, $t_version_id );
			echo ">$t_product->name $t_version->name</option>";
		}
	}
}

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<div class="form-container">

	<h2><?php echo plugin_lang_get( 'manage_repository' ) ?></h2>
	<div class="floatright">
		<?php
			print_bracket_link( plugin_page( 'list' ) . "&id=$f_repo_id", plugin_lang_get( 'browse' ) );
			print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
		?>
	</div>

	<table>
		<tr>
			<td class="category" width="30%"><?php echo plugin_lang_get( 'name' ) ?></td>
			<td><?php echo string_display( $t_repo->name ) ?></td>
		</tr>

		<tr>
			<td class="category"><?php echo plugin_lang_get( 'type' ) ?></td>
			<td><?php echo string_display( $t_type ) ?></td>
		</tr>

		<tr>
			<td class="category"><?php echo plugin_lang_get( 'url' ) ?></td>
			<td><?php echo string_display( $t_repo->url ) ?></td>
		</tr>

		<tr>
			<td class="category"><?php echo plugin_lang_get( 'info' ) ?></td>
			<td><pre><?php var_dump($t_repo->info) ?></pre></td>
		</tr>
	</table>

	<div class="floatleft">
		<form action="<?php echo plugin_page( 'repo_update_page' ) . '&amp;id=' . $t_repo->id ?>" method="post">
			<input type="submit" value="<?php echo plugin_lang_get( 'update_repository' ) ?>"/>
		</form>
		<form action="<?php echo plugin_page( 'repo_delete' ) . '&amp;id=' . $t_repo->id ?>" method="post">
			<?php echo form_security_field( 'plugin_Source_repo_delete' ) ?>
			<input type="submit" value="<?php echo plugin_lang_get( 'delete_repository' ) ?>"/>
		</form>
	</div>
	<div class="floatright">
		<form action="<?php echo plugin_page( 'repo_import_latest' ) . '&amp;id=' . $t_repo->id ?>" method="post">
			<?php echo form_security_field( 'plugin_Source_repo_import_latest' ) ?>
			<input type="submit" value="<?php echo plugin_lang_get( 'import_latest' ) ?>"/>
		</form>
		<form action="<?php echo plugin_page( 'repo_import_full' ) . '&amp;id=' . $t_repo->id ?>" method="post">
			<?php echo form_security_field( 'plugin_Source_repo_import_full' ) ?>
			<input type="submit" value="<?php echo plugin_lang_get( 'import_full' ) ?>"/>
		</form>
	</div>
	<br>

</div>


<?php if( plugin_config_get( 'enable_mapping' ) ) { ?>

<div class="form-container">
<h2><?php echo plugin_lang_get( 'branch_mapping' ) ?></h2>
<form action="<?php echo plugin_page( 'repo_update_mappings' ) . '&id=' . $t_repo->id ?>" method="post">
<fieldset>

	<?php echo form_security_field( 'plugin_Source_repo_update_mappings' ) ?>

	<table>
		<thead>
			<tr class="row-category">
				<th><?php echo plugin_lang_get( 'branch' ) ?></th>
				<th><?php echo plugin_lang_get( 'mapping_strategy' ) ?></th>
				<th><?php echo plugin_lang_get( 'mapping_version' ), ' ', plugin_lang_get( 'mapping_version_info' ) ?></th>
				<th><?php echo plugin_lang_get( 'mapping_regex' ), ' ', plugin_lang_get( 'mapping_regex_info' ) ?></th>
				<th><?php echo plugin_lang_get( 'delete' ) ?></th>
			</tr>
		</thead>

		<tbody>
<?php
	# Add dummy empty mapping so the loop displays a line to for new mappings
	$t_mappings[] = new SourceMapping( null, null, null );

	foreach( $t_mappings as $t_mapping ) {
		$t_branch = str_replace( '.', '_', $t_mapping->branch );
		if( is_null( $t_mapping->branch ) ) {
?>
			<tr class="spacer"></tr><tr></tr>
<?php
		}
?>
			<tr>
				<td class="center">
					<input name="<?php echo $t_branch ?>_branch" value="<?php
						echo string_attribute( $t_mapping->branch )
						?>" size="12" maxlength="128" />
				</td>
				<td class="center">
					<select name="<?php echo $t_branch ?>_type"><?php
						display_strategies( $t_mapping->type ) ?>
					</select>
				</td>
<?php if( Source_PVM() ) { ?>
				<td class="center">
					<select name="<?php echo $t_branch ?>_pvm_version_id"><?php
						display_pvm_versions( $t_mapping->pvm_version_id ) ?>
					</select>
				</td>
<?php } else { ?>
				<td class="center">
					<select name="<?php echo $t_branch ?>_version"><?php
						print_version_option_list( $t_mapping->version, ALL_PROJECTS, false, true, true ) ?>
					</select>
				</td>
<?php } ?>
				<td class="center">
					<input name="<?php echo $t_branch ?>_regex" value="<?php
						echo string_attribute( $t_mapping->regex )
						?>" size="18" maxlength="128" />
				</td>
				<td class="center">
					<input name="<?php echo $t_branch ?>_delete" type="checkbox" value="1" />
				</td>

			</tr>
<?php
	} # foreach
?>
		</tbody>
	</table>
	</fieldset>

	<div class="submit-button">
		<input type="submit" value="<?php echo plugin_lang_get( 'mapping_update' ) ?>"/>
	</div>

</form>
</div>

<?php } # end if enable_mapping ?>

<?php
html_page_bottom1( __FILE__ );

