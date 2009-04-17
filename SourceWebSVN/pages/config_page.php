<?php
# Copyright (C) 2008 John Reese, LeetCode.net
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
auth_reauthenticate();

html_page_top();
print_manage_menu();

?>

<br/>
<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
<?php echo form_security_field( 'plugin_Mibbit_config_update' ) ?>
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'svnpath' ) ?></td>
<td><input name="svnpath" value="<?php echo string_attribute( plugin_config_get( 'svnpath' ) ) ?>" size="40"/></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom( __FILE__ );

