<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);
$t_repo_commit_needs_issue = isset( $t_repo->info['repo_commit_needs_issue'] ) ?  $t_repo->info['repo_commit_needs_issue'] : false;
$t_repo_commit_issues_must_exist = isset( $t_repo->info['repo_commit_issues_must_exist'] ) ?  $t_repo->info['repo_commit_issues_must_exist'] : false;
$t_repo_commit_ownership_must_match = isset( $t_repo->info['repo_commit_ownership_must_match'] ) ?  $t_repo->info['repo_commit_ownership_must_match'] : false;
$t_repo_commit_committer_must_be_member = isset( $t_repo->info['repo_commit_committer_must_be_member'] ) ?  $t_repo->info['repo_commit_committer_must_be_member'] : false;
$t_repo_commit_committer_must_be_level = isset( $t_repo->info['repo_commit_committer_must_be_level'] ) ?  $t_repo->info['repo_commit_committer_must_be_level'] : MantisEnum::getValues( config_get( 'access_levels_enum_string' ) ) ;
$t_repo_commit_status_restricted = isset( $t_repo->info['repo_commit_status_restricted'] ) ?  $t_repo->info['repo_commit_status_restricted'] : false;
$t_repo_commit_status_allowed = isset( $t_repo->info['repo_commit_status_allowed'] ) ?  $t_repo->info['repo_commit_status_allowed'] : MantisEnum::getValues( config_get( 'status_enum_string' ));
$t_repo_commit_project_restricted = isset( $t_repo->info['repo_commit_project_restricted'] ) ?  $t_repo->info['repo_commit_project_restricted'] : false;
$t_repo_commit_project_allowed = isset( $t_repo->info['repo_commit_project_allowed'] ) ?  $t_repo->info['repo_commit_project_allowed'] : Array( 0 );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

if( ON == config_get( 'use_javascript' ) ) {
    $t_useJS = ' onclick="Source_Update_CheckOpts();" ';
    html_javascript_link( 'addLoadEvent.js' );
?>
<script type="text/javascript"><!--
function Source_Update_CheckOpts()
{
    var committer_must_be_member=document.getElementById( 'repo_commit_committer_must_be_member' );
    var committer_must_be_level=document.getElementById( 'repo_commit_committer_must_be_level' );

    committer_must_be_level.disabled = !(committer_must_be_member.checked);

    var commit_status_restricted=document.getElementById( 'repo_commit_status_restricted' );
    var commit_status_allowed=document.getElementById( 'repo_commit_status_allowed' );

    commit_status_allowed.disabled = !(commit_status_restricted.checked);

    var commit_project_restricted=document.getElementById( 'repo_commit_project_restricted' );
    var commit_project_allowed=document.getElementById( 'repo_commit_project_allowed' );

    commit_project_allowed.disabled = !(commit_project_restricted.checked);
}
addLoadEvent(Source_Update_CheckOpts);
--></script>
<?php
} else {
    $t_useJS = "";
}
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

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'pre_commit_checks' ); ?></td>
<td>
<table>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_needs_issue' ); ?></td>
<td><input name="repo_commit_needs_issue" type="checkbox" <?php echo ($t_repo_commit_needs_issue ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_issues_must_exist' ); ?></td>
<td><input name="repo_commit_issues_must_exist" type="checkbox" <?php echo ($t_repo_commit_issues_must_exist ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_ownership_must_match' ); ?></td>
<td><input name="repo_commit_ownership_must_match" type="checkbox" <?php echo ($t_repo_commit_ownership_must_match ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_committer_must_be_member' ); ?></td>
<td><input <?php echo( $t_useJS ); ?> id="repo_commit_committer_must_be_member" name="repo_commit_committer_must_be_member" type="checkbox" <?php echo ($t_repo_commit_committer_must_be_member ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_committer_must_be_level' ) ?></td>
<td><select multiple="multiple" id="repo_commit_committer_must_be_level" name="repo_commit_committer_must_be_level[]"><?php print_enum_string_option_list( 'access_levels', $t_repo_commit_committer_must_be_level ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_status_restricted' ); ?></td>
<td><input <?php echo( $t_useJS ); ?> name="repo_commit_status_restricted" id="repo_commit_status_restricted" type="checkbox" <?php echo ($t_repo_commit_status_restricted ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_status_restricted_list' ) ?></td>
<td><select multiple="multiple" id="repo_commit_status_allowed" name="repo_commit_status_allowed[]"><?php print_enum_string_option_list( 'status', $t_repo_commit_status_allowed ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_project_restricted' ); ?></td>
<td><input <?php echo( $t_useJS ); ?> id="repo_commit_project_restricted" name="repo_commit_project_restricted" type="checkbox" <?php echo ($t_repo_commit_project_restricted ? 'checked="checked"' : '') ?>/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'commit_project_restricted_list' ) ?></td>
<td><select multiple="multiple" id="repo_commit_project_allowed" name="repo_commit_project_allowed[]"><?php print_project_option_list( $t_repo_commit_project_allowed ) ?></select></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo  plugin_lang_get( 'update_repository' ) ?>"/></td>
</tr>
</table>
</td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

