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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<form action="<?php echo plugin_page( 'repo_update.php' ) ?>" method="post">
<input type="hidden" name="repo_id" value="<?php echo $t_repo->id ?>"/>
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title"><?php echo plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'repository' ) ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, "Back to Repository" ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'name' ) ?></td>
<td><input name="repo_name" maxlength="128" size="40" value="<?php echo string_attribute( $t_repo->name ) ?>"/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'type' ) ?></td>
<td><?php echo string_display( $t_type ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'url' ) ?></td>
<td><input name="repo_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_repo->url ) ?>"/></td>
</tr>

<?php event_signal( 'EVENT_SOURCE_UPDATE_REPO_FORM', array( $t_repo ) ) ?>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo  plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'repository' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

