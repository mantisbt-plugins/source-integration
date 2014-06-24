<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

$t_remote_checkin = plugin_config_get( 'remote_checkin' );
$t_checkin_urls = unserialize( plugin_config_get( 'checkin_urls' ) );

$t_remote_imports = plugin_config_get( 'remote_imports' );
$t_import_urls = unserialize( plugin_config_get( 'import_urls' ) );

?>

<br/>
<div class="form-container">

<form action="<?php echo plugin_page( 'manage_config' ) ?>" method="post">
<fieldset>
	<legend>
		<?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?>
	</legend>

	<div class="floatright">
		<?php
			print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'repositories' ) );
		?>
	</div>

	<?php echo form_security_field( 'plugin_Source_manage_config' ) ?>

	<div class="field-container">
		<label for="view_threshold"><span><?php echo plugin_lang_get( 'view_threshold' ) ?></span></label>
		<span class="select">
			<select name="view_threshold">
				<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="update_threshold"><span><?php echo plugin_lang_get( 'update_threshold' ) ?></span></label>
		<span class="select">
			<select name="update_threshold">
				<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'update_threshold' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="manage_threshold"><span><?php echo plugin_lang_get( 'manage_threshold' ) ?></span></label>
		<span class="select">
			<select name="manage_threshold">
				<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'manage_threshold' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="username_threshold"><span><?php echo plugin_lang_get( 'username_threshold' ) ?></span></label>
		<span class="select">
			<select name="username_threshold">
				<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'username_threshold' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="menu_links"><span><?php echo plugin_lang_get( 'menu_links' ) ?></span></label>
		<span class="checkbox">
			<input type="checkbox" name="show_repo_link" <?php check_checked( ON == plugin_config_get( 'show_repo_link' ) ) ?>/>
			<label for="show_repo_link"><?php echo plugin_lang_get( 'show_repo_link' ) ?></label>
			<br>
			<input type="checkbox" name="show_search_link" <?php check_checked( ON == plugin_config_get( 'show_search_link' ) ) ?>/>
			<label for="show_search_link"><?php echo plugin_lang_get( 'show_search_link' ) ?></label>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="menu_links"><span><?php echo plugin_lang_get( 'enabled_features' ) ?></span></label>
		<span class="checkbox">
			<input type="checkbox" name="show_repo_stats" <?php check_checked( ON == plugin_config_get( 'show_repo_stats' ) ) ?>/>
			<label for="show_repo_stats"><?php echo plugin_lang_get( 'show_repo_stats' ) ?></label>
			<br>
			<input type="checkbox" name="enable_linking" <?php check_checked( ON == plugin_config_get( 'enable_linking' ) ) ?>/>
			<label for="enable_linking"><?php echo plugin_lang_get( 'enable_linking' ) ?></label>
			<br>
			<input type="checkbox" name="enable_mapping" <?php check_checked( ON == plugin_config_get( 'enable_mapping' ) ) ?>/>
			<label for="enable_mapping"><?php echo plugin_lang_get( 'enable_mapping' ) ?></label>
			<br>
			<input type="checkbox" name="enable_resolving" <?php check_checked( ON == plugin_config_get( 'enable_resolving' ) ) ?>/>
			<label for="enable_resolving"><?php echo plugin_lang_get( 'enable_resolving' ) ?></label>
			<br>
			<input type="checkbox" name="enable_message" <?php check_checked( ON == plugin_config_get( 'enable_message' ) ) ?>/>
			<label for="enable_message"><?php echo plugin_lang_get( 'enable_message' ) ?></label>
			<br>
			<input type="checkbox" name="enable_porting" <?php check_checked( ON == plugin_config_get( 'enable_porting' ) ) ?>/>
			<label for="enable_porting"><?php echo plugin_lang_get( 'enable_porting' ) ?></label>
<?php
	if( plugin_is_installed( 'ProductMatrix' ) || plugin_config_get( 'enable_product_matrix' ) ) {
?>
			<br>
			<input type="checkbox" name="enable_product_matrix" <?php check_checked( ON == plugin_config_get( 'enable_product_matrix' ) ) ?>/>
			<label for="enable_product_matrix"><?php echo plugin_lang_get( 'enable_product_matrix' ) ?></label>
<?php
	}
?>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container spacer">
		<label for="buglink_regex_1"><span><?php echo plugin_lang_get( 'buglink_regex_1' ) ?></span></label>
		<span class="input">
			<input name="buglink_regex_1" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'buglink_regex_1' ) ) ?>"/>
			<br>
			<input name="buglink_reset_1" type="checkbox"/>
			<label for="buglink_reset_1"><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="buglink_regex_2"><span><?php echo plugin_lang_get( 'buglink_regex_2' ) ?></span></label>
		<span class="input">
			<input name="buglink_regex_2" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'buglink_regex_2' ) ) ?>"/>
			<br>
			<input name="buglink_reset_2" type="checkbox"/>
			<label for="buglink_reset_2"><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container spacer">
		<label for="bugfix_regex_1"><span><?php echo plugin_lang_get( 'bugfix_regex_1' ) ?></span></label>
		<span class="input">
			<input name="bugfix_regex_1" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'bugfix_regex_1' ) ) ?>"/>
			<br>
			<input name="bugfix_reset_1" type="checkbox"/>
			<label for="bugfix_reset_1"><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="bugfix_regex_2"><span><?php echo plugin_lang_get( 'bugfix_regex_2' ) ?></span></label>
		<span class="input">
			<input name="bugfix_regex_2" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'bugfix_regex_2' ) ) ?>"/>
			<br>
			<input name="bugfix_reset_2" type="checkbox"/>
			<label for="bugfix_reset_2"><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="bugfix_status"><span><?php echo plugin_lang_get( 'bugfix_status' ) ?></span></label>
		<span class="select">
			<select name="bugfix_status">
				<option value="0" <?php check_selected( 0, plugin_config_get( 'bugfix_status' ) ) ?>>
					<?php echo plugin_lang_get( 'bugfix_status_off' ) ?>
				</option>
				<option value="-1" <?php check_selected( -1, plugin_config_get( 'bugfix_status' ) ) ?>>
					<?php echo plugin_lang_get( 'bugfix_status_default' ) ?>
				</option>
				<?php print_enum_string_option_list( 'status', plugin_config_get( 'bugfix_status' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="bugfix_resolution"><span><?php echo plugin_lang_get( 'bugfix_resolution' ) ?></span></label>
		<span class="select">
			<select name="bugfix_resolution">
				<?php print_enum_string_option_list( 'resolution', plugin_config_get( 'bugfix_resolution' ) ) ?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>

<?php
	if( plugin_is_installed( 'ProductMatrix' ) || plugin_config_get( 'enable_product_matrix' ) ) {
?>
	<div class="field-container">
		<label for="bugfix_status_pvm"><span><?php echo plugin_lang_get( 'bugfix_status_pvm' ) ?></span></label>
		<span class="select">
			<select name="bugfix_status_pvm">
				<option value="0" <?php check_selected( 0, plugin_config_get( 'bugfix_status_pvm' ) ) ?>>
					<?php echo plugin_lang_get( 'bugfix_status_off' ) ?>
				</option>
<?php
		foreach( config_get( 'plugin_ProductMatrix_status' ) as $t_status => $t_name ) {
?>
				<option value="<?php echo string_attribute( $t_status ) ?>" <?php check_selected( $t_status, plugin_config_get( 'bugfix_status_pvm' ) ) ?>>
					<?php echo string_display_line( $t_name ) ?>
				</option>
<?php
		}
?>
			</select>
		</span>
		<span class="label-style"></span>
	</div>
<?php
	}
?>

	<div class="field-container">
		<label for="bugfix_message"><span><?php echo plugin_lang_get( 'bugfix_message' ) ?></span></label>
		<span class="input">
			<input name="bugfix_message" size="50" value="<?php echo string_attribute( plugin_config_get( 'bugfix_message' ) ) ?>"/>
			<br>
			<span class="small"><?php echo plugin_lang_get( 'bugfix_message_info' ) ?></span>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="bugfix_message_view_status"><span><?php echo plugin_lang_get( 'bugfix_message_view_status' ) ?></span></label>
		<span class="select">
			<select name="bugfix_message_view_status">
				<?php print_enum_string_option_list( 'view_state', plugin_config_get( 'bugfix_message_view_status' ) ) ?>
			</select>
			<br>
			<span class="small"><?php echo plugin_lang_get( 'bugfix_message_view_status_info' ) ?></span>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="bugfix_handler"><span><?php echo plugin_lang_get( 'bugfix_handler' ) ?></span></label>
		<span class="checkbox">
			<input name="bugfix_handler" type="checkbox" <?php check_checked( ON == plugin_config_get( 'bugfix_handler' ) ) ?>/>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container spacer">
		<label for="bugfix_handler"><span><?php echo plugin_lang_get( 'api_key' ) ?></span></label>
		<span class="input">
			<input name="api_key" size="50" value="<?php echo string_attribute( plugin_config_get( 'api_key' ) ) ?>"/>
			<br>
			<span class="small"><?php echo plugin_lang_get( 'api_key_info' ) ?></span>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container spacer">
		<label for="remote_checkin"><span><?php echo plugin_lang_get( 'allow_remote_checkin' ) ?></span></label>
		<span class="checkbox">
			<input name="remote_checkin" type="checkbox" <?php check_checked( ON == $t_remote_checkin ) ?>/>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="checkin_urls"><span><?php echo plugin_lang_get( 'remote_checkin_urls' ) ?></span></label>
		<span class="textarea">
			<textarea name="checkin_urls" rows="8" cols="50"><?php
				foreach( $t_checkin_urls as $t_ip ) {
					echo string_textarea( $t_ip ),"\n";
				}
			?></textarea>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container spacer">
		<label for="remote_imports"><span><?php echo plugin_lang_get( 'allow_remote_import' ) ?></span></label>
		<span class="checkbox">
			<input name="remote_imports" type="checkbox" <?php check_checked( ON == $t_remote_imports ) ?>/>
		</span>
		<span class="label-style"></span>
	</div>

	<div class="field-container">
		<label for="import_urls"><span><?php echo plugin_lang_get( 'remote_import_urls' ) ?></span></label>
		<span class="textarea">
			<textarea name="import_urls" rows="8" cols="50"><?php
				foreach( $t_import_urls as $t_ip ) {
					echo string_textarea( $t_ip ),"\n";
				}
			?></textarea>
		</span>
		<span class="label-style"></span>
	</div>

<?php
	foreach( SourceVCS::all() as $t_type => $t_vcs ) {
		if ( $t_vcs->configuration ) {
			$t_vcs->update_config_form();
		}
	}
?>

	<span class="submit-button">
		<input type="submit" value="<?php echo plugin_lang_get( 'update_configuration' ) ?>"/>
	</span>

</fieldset>
</form>
</div>

<?php
html_page_bottom1( __FILE__ );

