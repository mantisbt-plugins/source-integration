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
<div id="repo-update-page-div" class="form-container">
	<form action="<?php echo plugin_page( 'repo_update.php' ) ?>" method="post">
		<fieldset>
			<legend><span><?php echo plugin_lang_get( 'update_repository' ) ?></span></legend>

			<?php echo form_security_field( 'plugin_Source_repo_update' ) ?>
			<input type="hidden" name="repo_id" value="<?php echo $t_repo->id ?>"/>

			<div class="section-link"><?php print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, plugin_lang_get( 'back_repo' ) ) ?></div>

			<div class="field-container">
				<label><span><?php echo plugin_lang_get( 'name' ) ?></span></label>
				<span class="input">
					<input name="repo_name" maxlength="128" size="40" value="<?php echo string_attribute( $t_repo->name ) ?>"/>
				</span>
				<span class="label-style"></span>
			</div>

			<div class="field-container">
				<label><span><?php echo plugin_lang_get( 'type' ) ?></span></label>
				<span class="input"><?php echo string_display( $t_type ) ?></span>
				<span class="label-style"></span>
			</div>

			<div class="field-container">
				<label><span><?php echo plugin_lang_get( 'url' ) ?></span></label>
				<span class="input">
					<input name="repo_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_repo->url ) ?>"/>
				</span>
				<span class="label-style"></span>
			</div>

			<?php $t_vcs->update_repo_form( $t_repo ) ?>

			<span class="submit-button">
				<input type="submit" value="<?php echo plugin_lang_get( 'update_repository' ) ?>"/>
			</span>
		</fieldset>
</form>
</div>

<?php
html_page_bottom1( __FILE__ );

