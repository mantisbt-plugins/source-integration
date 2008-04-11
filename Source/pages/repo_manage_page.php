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

html_page_top1( lang_get( 'plugin_Source_title' ) );
html_page_top2();
?>

<br/>
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo lang_get( 'plugin_Source_manage' ), ' ', lang_get( 'plugin_Source_repository' ) ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'index' ), lang_get( 'plugin_Source_back' ) ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_name' ) ?></td>
<td colspan="2"><?php echo string_display( $t_repo->name ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_type' ) ?></td>
<td colspan="2"><?php echo string_display( $t_type ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_url' ) ?></td>
<td colspan="2"><?php echo string_display( $t_repo->url ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_info' ) ?></td>
<td colspan="2"><pre><?php
foreach( $t_repo->info as $t_key => $t_value ) {
	echo string_display( $t_key . ' => ' );
	var_dump( $t_value );
}
?></pre></td>
</tr>

<tr>
<td width="50%"></td>
<td width="30%"></td>
<td width="20%"></td>
</tr>

<tr>
<td colspan="2">
<form action="<?php echo plugin_page( 'repo_update_page' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo lang_get( 'plugin_Source_update' ), ' ', lang_get( 'plugin_Source_repository' ) ?>"/></form>
<form action="<?php echo plugin_page( 'repo_delete' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo lang_get( 'plugin_Source_delete' ), ' ', lang_get( 'plugin_Source_repository' ) ?>"/></form>
</td>
<td class="right">
<form action="<?php echo plugin_page( 'repo_import' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo lang_get( 'plugin_Source_import_data' ) ?>"/></form>
</td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

