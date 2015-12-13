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
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();
$t_vcs = SourceVCS::repo( $t_repo );

$t_use_porting = plugin_config_get( 'enable_porting' );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<div class="form-container width75">
	<h2><?php
		echo string_display_line( $t_repo->name ), ': ',
			$t_vcs->show_changeset( $t_repo, $t_changeset ); ?>
	</h2>
	<div class="floatright">
		<?php print_bracket_link(
			plugin_page( 'view' ) . '&id=' . $t_changeset->id . '&offset=' . $f_offset,
			plugin_lang_get( 'back_changeset' )
		); ?>
	</div>

	<form action="<?php echo plugin_page( 'edit' ), '&id=', $t_changeset->id ?>" method="post">
		<fieldset>
			<?php echo form_security_field( 'plugin_Source_edit' ) ?>

			<input type="hidden" name="offset" value="<?php echo $f_offset ?>"/>

			<div class="field-container">
				<label><?php echo plugin_lang_get( 'author' ) ?></label>
				<span class="select">
					<select name="user_id">
						<option value="0" <?php
							echo check_selected( 0, (int)$t_changeset->user_id )
							?>>--</option>
						<?php print_user_option_list( (int)$t_changeset->user_id ) ?>

					</select>
				</span>
				<span class="label-style"></span>
			</div>

			<div class="field-container">
				<label><?php echo plugin_lang_get( 'committer' ) ?></label>
				<span class="select">
					<select name="committer_id">
						<option value="0" <?php
							echo check_selected( 0, (int)$t_changeset->committer_id )
							?>>--</option>
						<?php print_assign_to_option_list( (int)$t_changeset->user_id ) ?>

					</select>
				</span>
				<span class="label-style"></span>
			</div>

			<div class="field-container">
				<label><?php echo plugin_lang_get( 'branch' ) ?></label>
				<span class="select">
					<select name="branch">
<?php
	if( $t_changeset->branch == "" ) {
?>
						<option value="" <?php
							echo check_selected( "", $t_changeset->branch )
							?>>--</option>
<?php
	}

	foreach( $t_repo->branches as $t_branch ) {
?>
						<option value="<?php echo string_attribute( $t_branch ) ?>" <?php
							echo check_selected( $t_branch, $t_changeset->branch )
						?>><?php
							echo string_display_line( $t_branch )
						?></option>
<?php
	}
?>
					</select>
				</span>
				<span class="label-style"></span>
			</div>

<?php
	if( $t_use_porting ) {
?>
			<div class="field-container spacer">
				<label><?php echo plugin_lang_get( 'ported' ) ?></label>
				<span class="select">
					<select>
						<option value="" <?php
							echo check_selected( "", $t_changeset->ported )
						?>><?php
							echo plugin_lang_get( 'pending' )
						?></option>
						<option value="0" <?php
							echo check_selected( "0", $t_changeset->ported )
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
							echo check_selected( $t_branch, $t_changeset->ported )
						?>><?php
							echo string_display_line( $t_branch )
						?></option>
<?php
		}
?>
					</select>
				</span>
				<span class="label-style"></span>
			</div>
<?php
	} // if porting
?>

			<div class="field-container spacer">
				<label><?php echo plugin_lang_get( 'message' ) ?></label>
				<span class="textarea">
					<textarea name="message" cols="80" rows="8"><?php echo string_textarea( $t_changeset->message ) ?></textarea>
				</span>
				<span class="label-style"></span>
			</div>

			<div class="submit-button">
				<input type="submit" value="<?php echo plugin_lang_get( 'edit' ) ?>" />
			</div>

		</fieldset>
	</form>
</div>


<?php
html_page_bottom1( __FILE__ );

