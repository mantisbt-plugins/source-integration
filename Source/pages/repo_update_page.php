<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<form action="<?php echo plugin_page( 'repo_update.php' ) ?>" method="post">
<?php echo form_security_field( 'plugin_Source_repo_update' ) ?>
<input type="hidden" name="repo_id" value="<?php echo $t_repo->id ?>"/>
<table class="width60" align="center" cellspacing="1">

<tr>
<td class="form-title"><?php echo plugin_lang_get( 'update_repository' ) ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) ) ?></td>
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

<?php $t_vcs->update_repo_form( $t_repo ) ?>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo  plugin_lang_get( 'update_repository' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

