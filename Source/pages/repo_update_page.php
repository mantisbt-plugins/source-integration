<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );
$f_status = gpc_get_int( 'status', null );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

# Display Repository Updated message if status is true
if( $f_status ) {
?>
<div class="col-md-12 col-xs-12">
	<div class="alert alert-success center">
		<p class="bold bigger-110">
			<?php echo plugin_lang_get( 'repository_updated' ) ?>
		</p>
	</div>
</div>
<?php
}
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	<form action="<?php echo plugin_page( 'repo_update.php' ) ?>" method="post">
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'update_repository' ) ?>
			</h4>
			<?php echo form_security_field( 'plugin_Source_repo_update' ) ?>
			<input type="hidden" name="repo_id" id="repo_id" value="<?php echo $t_repo->id ?>"/>
		</div>
		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="widget-toolbox padding-8 clearfix">
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id ?>">
						<?php echo plugin_lang_get( 'back_repo' ) ?>
					</a>
				</div>
				<div class="table-responsive">
		<table class="table table-striped table-bordered table-condensed">
			<tr>
				<td class="category" width="35%"><?php echo plugin_lang_get( 'name' ) ?></td>
				<td>
					<input name="repo_name" type="text" maxlength="128" size="40" value="<?php echo string_attribute( $t_repo->name ) ?>"/>
				</td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'type' ) ?></td>
				<td><?php echo string_display( $t_type ) ?></td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'url' ) ?></td>
				<td>
					<input name="repo_url" type="text" maxlength="250" size="40" value="<?php echo string_attribute( $t_repo->url ) ?>"/>
				</td>
			</tr>

			<?php $t_vcs->update_repo_form( $t_repo ) ?>
		</table>
				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get( 'update_repository' ) ?>" />
			</div>
		</div>

	</div>
	</form>
</div>

<?php
layout_page_end();

