<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'update_threshold' ) );
require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$t_changeset = SourceChangeset::load( $f_changeset_id );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	error_parameters( $f_changeset_id );
	plugin_error( SourcePlugin::ERROR_REPO_MISSING_CHANGESET );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();
$t_vcs = SourceVCS::repo( $t_repo );

$t_use_porting = plugin_config_get( 'enable_porting' );

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();
?>

<div class="form-container width75">
	
	<form action="<?php echo plugin_page( 'edit' ), '&id=', $t_changeset->id ?>" method="post">
		
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php
		echo string_display_line( $t_repo->name ), ': ',
			$t_vcs->show_changeset( $t_repo, $t_changeset ); ?>
			</h4>
		</div>
		<div class="widget-body">
				<div class="widget-main no-padding">
					<div class="table-responsive">
						
						<div class="widget-toolbox padding-8 clearfix">
							<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'view' ) . '&id=' . $t_changeset->id . '&offset=' . $f_offset ?>">
								<?php echo plugin_lang_get( 'back_changeset' ) ?>
							</a>
						</div>	
		<table class="table table-striped table-bordered table-condensed table-hover">
			<?php echo form_security_field( 'plugin_Source_edit' ) ?>

			<input type="hidden" name="offset" value="<?php echo $f_offset ?>"/>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'author' ) ?></td>
				<td>
					<span class="select">
						<select name="user_id">
							<option value="0" <?php
								check_selected( 0, (int)$t_changeset->user_id )
								?>>--</option>
							<?php print_user_option_list( (int)$t_changeset->user_id ) ?>
	
						</select>
					</span>
					<span class="label-style"></span>
				</td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'committer' ) ?></td>
				<td>
				<span class="select">
					<select name="committer_id">
						<option value="0" <?php
							check_selected( 0, (int)$t_changeset->committer_id )
							?>>--</option>
						<?php print_assign_to_option_list( (int)$t_changeset->committer_id ) ?>

					</select>
				</span>
				<span class="label-style"></span>
				</td>
			</tr>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'branch' ) ?></td>
				<td>
				<span class="select">
					<select name="branch">
<?php
	if( $t_changeset->branch == "" ) {
?>
						<option value="" <?php
							check_selected( "", $t_changeset->branch )
							?>>--</option>
<?php
	}

	foreach( $t_repo->branches as $t_branch ) {
?>
						<option value="<?php echo string_attribute( $t_branch ) ?>" <?php
							check_selected( $t_branch, $t_changeset->branch )
						?>><?php
							echo string_display_line( $t_branch )
						?></option>
<?php
	}
?>
					</select>
				</span>
				<span class="label-style"></span>
				</td>
			</tr>

<?php
	if( $t_use_porting ) {
?>
			<tr>
				<td class="category"><?php echo plugin_lang_get( 'ported' ) ?></td>
				<td>
				<span class="select">
					<select>
						<option value="" <?php
							check_selected( "", $t_changeset->ported )
						?>><?php
							echo plugin_lang_get( 'pending' )
						?></option>
						<option value="0" <?php
							check_selected( "0", $t_changeset->ported )
						?>><?php
							echo plugin_lang_get( 'na' )
						?></option>
						<option value="">--</option>
<?php
		foreach( $t_repo->branches as $t_branch ) {
			if( $t_branch == $t_changeset->branch ) {
				continue;
			}
?>
						<option value="<?php
							echo string_attribute( $t_branch ) ?>" <?php
							check_selected( $t_branch, $t_changeset->ported )
						?>><?php
							echo string_display_line( $t_branch )
						?></option>
<?php
		}
?>
					</select>
				</span>
				<span class="label-style"></span>
				</td>
			</tr>
<?php
	} // if porting
?>

			<tr>
				<td class="category"><?php echo plugin_lang_get( 'message' ) ?></td>
				<td>
				<span class="textarea">
					<textarea name="message" cols="80" rows="8"><?php echo string_textarea( $t_changeset->message ) ?></textarea>
				</span>
				<span class="label-style"></span>
				</td>
			</tr>

		</table>
				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<input type="submit" class="btn btn-primary btn-white btn-sm btn-round" value="<?php echo plugin_lang_get( 'edit' ) ?>" />
			</div>
		</div>
		
	</div>
	</form>
</div>


<?php
layout_page_end();

