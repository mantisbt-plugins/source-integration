<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_manage = access_has_global_level( plugin_config_get( 'manage_threshold' ) );

$t_show_stats = plugin_config_get( 'show_repo_stats' );
$t_show_file_stats = plugin_config_get( 'show_file_stats' );

$t_repos = SourceRepo::load_all();

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'repositories' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">	

					<div class="widget-toolbox padding-8 clearfix">
						<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'search_page' ) ?>">
							<?php echo plugin_lang_get( 'search' ) ?>
						</a>
					<?php
						if ( $t_can_manage ) { ?>
							<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'manage_config_page' ) ?>">
								<?php echo plugin_lang_get( 'configuration' ) ?>
							</a>
					<?php } ?>
					</div>
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr class="row-category">
				<th width="30%"><?php echo plugin_lang_get( 'repository' ) ?></th>
				<th width="15%"><?php echo plugin_lang_get( 'type' ) ?></th>
<?php
	if( $t_show_stats ) {
?>
				<th width="10%"><?php echo plugin_lang_get( 'changesets' ) ?></th>
<?php
		if( $t_show_file_stats ) {
?>
				<th width="10%"><?php echo plugin_lang_get( 'files' ) ?></th>
<?php
		}
?>
				<th width="10%"><?php echo plugin_lang_get( 'issues' ) ?></th>
<?php
	}
?>
				<th width="25%"><?php echo plugin_lang_get( 'actions' ) ?></th>
			</tr>
		</thead>

		<tbody>
<?php
	foreach( $t_repos as $t_repo ) {
?>
			<tr>
				<td><?php echo string_display( $t_repo->name ) ?></td>
				<td><?php echo string_display( SourceType( $t_repo->type ) ) ?></td>
<?php
		if( $t_show_stats ) {
			$t_stats = $t_repo->stats();
?>
				<td><?php echo $t_stats['changesets'] ?></td>
<?php
			if( $t_show_file_stats ) {
?>
				<td><?php echo $t_stats['files'] ?></td>
<?php
			}
?>
				<td><?php echo $t_stats['bugs'] ?></td>
<?php
		}
?>
				<td>
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'list' ) . '&id=' . $t_repo->id ?>">
						<?php echo plugin_lang_get( 'changesets' ) ?>
					</a>
				<?php
					if( $t_can_manage ) {
						# Import repositories can be deleted from here
						if( preg_match( '/^Import \d+-\d+\d+/', $t_repo->name ) ) { ?>
							<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'repo_delete' ) . '&id=' . $t_repo->id
									. form_security_param( 'plugin_Source_repo_delete' ) ?>">
								<?php echo plugin_lang_get( 'delete' ) ?>
							</a>
					<?php } ?>	
					<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id ?>">
						<?php echo plugin_lang_get( 'manage' ) ?>
					</a>
				<?php }
				?></td>
			</tr>
<?php
	} # foreach
?>
		</tbody>
	</table>
				</div>
			</div>
		</div>
		
		</div>
	</div>

<?php
	if( $t_can_manage ) {
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<form action="<?php echo plugin_page( 'repo_create' ) ?>" method="post">

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'create_repository' ) ?>
			</h4>
			<div class="widget-toolbar">
				<?php echo form_security_field( 'plugin_Source_repo_create' ) ?>
			</div>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">

	<table class="table table-striped table-bordered table-condensed">
		<tr>
			<td class="category">
				<?php echo plugin_lang_get( 'name' ) ?>
			</td>
			<td>
				<input id="repo_name" name="repo_name" type="text" maxlength="200" size="40" />
			</td>
		</tr>

		<tr>
			<td class="category">
				<?php echo plugin_lang_get( 'type' ) ?>
			</td>
			<td>
				<select name="repo_type">
					<option value=""><?php echo plugin_lang_get( 'select_one' ) ?></option>
<?php
		foreach( SourceTypes() as $t_type => $t_type_name ) {
?>
					<option value="<?php echo $t_type ?>"><?php echo
						string_display( $t_type_name )
					?></option>
<?php
		}
?>
				</select>
			</td>
		</tr>
	</table>
				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<input class="btn btn-primary btn-white btn-round" type="submit" value="<?php echo plugin_lang_get( 'create_repository' ) ?>" />
			</div>

		</div>
	</div>
	</form>	
</div>
<?php
	} # if( $t_can_manage )
?>

<?php
layout_page_end();
