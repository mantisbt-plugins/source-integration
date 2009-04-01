<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

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
<form action="<?php echo plugin_page( 'manage_config' ) ?>" method="post">
<?php echo form_security_field( 'plugin_Source_manage_config' ) ?>
<table class="width100" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'view_threshold' ) ?></td>
<td><select name="view_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold' ) ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'update_threshold' ) ?></td>
<td><select name="update_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'update_threshold' ) ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'manage_threshold' ) ?></td>
<td><select name="manage_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'manage_threshold' ) ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'menu_links' ) ?></td>
<td>
	<label><input type="checkbox" name="show_repo_link" <?php echo ( plugin_config_get( 'show_repo_link' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'show_repo_link' ) ?></label><br/>
	<label><input type="checkbox" name="show_search_link" <?php echo ( plugin_config_get( 'show_search_link' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'show_search_link' ) ?></label><br/>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'enabled_features' ) ?></td>
<td>
	<label><input type="checkbox" name="enable_porting" <?php echo ( plugin_config_get( 'enable_porting' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'enable_porting' ) ?></label><br/>
</td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'buglink_regex_1' ) ?></td>
<td>
	<input name="buglink_regex_1" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'buglink_regex_1' ) ) ?>"/>
	<br/><label><input name="buglink_reset_1" type="checkbox"/><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'buglink_regex_2' ) ?></td>
<td>
	<input name="buglink_regex_2" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'buglink_regex_2' ) ) ?>"/>
	<br/><label><input name="buglink_reset_2" type="checkbox"/><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
</td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'bugfix_resolving' ) ?></td>
<td><input name="bugfix_resolving" type="checkbox" <?php echo (ON == plugin_config_get( 'bugfix_resolving' ) ? 'checked="checked"' : '') ?>></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'bugfix_regex_1' ) ?></td>
<td>
	<input name="bugfix_regex_1" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'bugfix_regex_1' ) ) ?>"/>
	<br/><label><input name="bugfix_reset_1" type="checkbox"/><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'bugfix_regex_2' ) ?></td>
<td>
	<input name="bugfix_regex_2" size="50" maxlength="500" value="<?php echo string_attribute( plugin_config_get( 'bugfix_regex_2' ) ) ?>"/>
	<br/><label><input name="bugfix_reset_2" type="checkbox"/><span class="small"><?php echo plugin_lang_get( 'reset' ) ?></span></label>
</td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'allow_remote_checkin' ) ?></td>
<td><input name="remote_checkin" type="checkbox" <?php echo (ON == $t_remote_checkin ? 'checked="checked"' : '') ?>></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'remote_checkin_urls' ) ?></td>
<td><textarea name="checkin_urls" rows="8" cols="50"><?php
foreach( $t_checkin_urls as $t_ip ) {
	echo string_textarea( $t_ip ),"\n";
}
?></textarea></td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'allow_remote_import' ) ?></td>
<td><input name="remote_imports" type="checkbox" <?php echo (ON == $t_remote_imports ? 'checked="checked"' : '') ?>></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'remote_import_urls' ) ?></td>
<td><textarea name="import_urls" rows="8" cols="50"><?php
foreach( $t_import_urls as $t_ip ) {
	echo string_textarea( $t_ip ),"\n";
}
?></textarea></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'configuration' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

