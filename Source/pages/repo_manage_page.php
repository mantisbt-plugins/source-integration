<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
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

/**
 * Prints Product versions options list.
 *
 * @param int|null $t_version_id
 *
 * @noinspection PhpUndefinedClassInspection Code is only called when the
 * ProductMatrix plugin is available.
 */
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

/**
 * Converts raw Repository information to ready-for-display values.
 *
 * @param array $p_array Repository information (SourceRepo::$info)
 *
 * @return array key => value escaped for output
 */
function convert_to_key_value( $p_array ) {
	$t_result = array();

	foreach( $p_array as $t_key => $t_value ) {
		if( is_bool( $t_value ) ) {
			$t_value = trans_bool($t_value);
		} else {
			if( is_array( $t_value ) ) {
				$t_value = var_export( $t_value, true );
			} else {
				// Hide data from fields holding sensitive information
				$t_sensitive_fields = array( 'password', 'pwd', 'secret', 'token');
				foreach( $t_sensitive_fields as $t_string ) {
					if( strpos( $t_key, $t_string ) !== false ) {
						$t_value = str_repeat( '&bull;', strlen( $t_value ) );
						break;
					}
				}
			}
			$t_value = string_display_line( $t_value );
		}
		$t_result[$t_key] = $t_value;
	}

	return $t_result;
}


layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'manage_repository' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="widget-toolbox padding-8 clearfix">
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'list' ) . "&id=$f_repo_id" ?>">
						<?php echo plugin_lang_get( 'browse' ) ?>
					</a>
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'index' ) ?>">
						<?php echo plugin_lang_get( 'back' ) ?>
					</a>
				</div>
				<div class="table-responsive">

	<table class="table table-bordered table-condensed">
		<tr>
			<td class="category width-35"><?php echo plugin_lang_get( 'name' ) ?></td>
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

<?php
	foreach( convert_to_key_value( $t_repo->info ) as $t_key => $t_value ) {
?>
		<tr>
			<td class="category">
				<?php echo plugin_lang_get_defaulted( $t_key, $t_key, $t_vcs->basename ) ?>
			</td>
			<td><?php echo $t_value ?></td>
		</tr>
<?php
	}
?>
	</table>

				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<div class="col-md-6 col-xs-12 no-padding">
					<?php
					print_link_button(
						plugin_page( 'repo_update_page' ) . '&id=' . $t_repo->id,
						plugin_lang_get( 'update_repository' ),
						'btn-sm pull-left'
					);
					?>
					<form action="<?php echo plugin_page( 'repo_delete' ) . '&amp;id=' . $t_repo->id ?>" method="post" class="pull-left">
						<?php echo form_security_field( 'plugin_Source_repo_delete' ) ?>
						<input type="submit" class="btn btn-primary btn-white btn-sm btn-round" value="<?php echo plugin_lang_get( 'delete_repository' ) ?>"/>
					</form>
				</div>
				<div class="col-md-6 col-xs-12 no-padding">
					<form action="<?php echo plugin_page( 'repo_import_full' ) . '&amp;id=' . $t_repo->id ?>" method="post" class="pull-right">
						<?php echo form_security_field( 'plugin_Source_repo_import_full' ) ?>
						<input type="submit" class="btn btn-primary btn-white btn-sm btn-round" value="<?php echo plugin_lang_get( 'import_full' ) ?>"/>
					</form>
					<form action="<?php echo plugin_page( 'repo_import_latest' ) . '&amp;id=' . $t_repo->id ?>" method="post" class="pull-right">
						<?php echo form_security_field( 'plugin_Source_repo_import_latest' ) ?>
						<input type="submit" class="btn btn-primary btn-white btn-sm btn-round " value="<?php echo plugin_lang_get( 'import_latest' ) ?>"/>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="space-10"></div>


<?php if( plugin_config_get( 'enable_mapping' ) ) { ?>

<div class="form-container">
<form action="<?php echo plugin_page( 'repo_update_mappings' ) . '&id=' . $t_repo->id ?>" method="post">

	<?php echo form_security_field( 'plugin_Source_repo_update_mappings' ) ?>

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'branch_mapping' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">

	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr class="category">
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
		# Since it is not possible to update the branch's name (see #230),
		# the input field is disabled, except for the 'new mapping' row
		$t_disabled = 'disabled';
		if( is_null( $t_mapping->branch ) ) {
			$t_disabled = '';
			if( count( $t_mappings ) > 1 ) {
				echo '<tr class="spacer"></tr>';
			}
		}
?>
			<tr>
				<td class="center">
					<!--suppress HtmlFormInputWithoutLabel -->
					<input type="text" name="<?php echo $t_branch ?>_branch" value="<?php
						echo string_attribute( $t_mapping->branch )
						?>" class="input-sm"
						<?php echo $t_disabled; ?>
					/>
				</td>
				<td class="center">
					<!--suppress HtmlFormInputWithoutLabel -->
					<select class="input-sm" name="<?php echo $t_branch ?>_type"><?php
						display_strategies( $t_mapping->type ) ?>
					</select>
				</td>
<?php if( Source_PVM() ) { ?>
				<td class="center">
					<!--suppress HtmlFormInputWithoutLabel -->
					<select class="input-sm" name="<?php echo $t_branch ?>_pvm_version_id"><?php
						display_pvm_versions( $t_mapping->pvm_version_id ) ?>
					</select>
				</td>
<?php } else { ?>
				<td class="center">
					<!--suppress HtmlFormInputWithoutLabel -->
					<select class="input-sm" name="<?php echo $t_branch ?>_version"><?php
						print_version_option_list( $t_mapping->version, ALL_PROJECTS, false ) ?>
					</select>
				</td>
<?php } ?>
				<td class="center">
					<!--suppress HtmlFormInputWithoutLabel -->
					<input type="text" name="<?php echo $t_branch ?>_regex" value="<?php
						echo string_attribute( $t_mapping->regex )
						?>" class="input-sm" />
				</td>
				<td class="center">
					<label>
						<input name="<?php echo $t_branch ?>_delete" type="checkbox" value="1" class="ace"/>
						<span class="lbl"></span>
					</label>
				</td>

			</tr>
<?php
	} # foreach
?>
		</tbody>
	</table>
				</div>
			</div>

			<div class="widget-toolbox padding-8 clearfix">
				<button class="btn btn-primary btn-white btn-sm btn-round">
					<?php echo plugin_lang_get( 'mapping_update' ) ?>
				</button>
			</div>
		</div>
	</div>

</form>
</div>

<?php } # end if enable_mapping ?>
</div>

<?php
layout_page_end();

