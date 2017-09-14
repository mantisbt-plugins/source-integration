<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

/**
 * Display a list of changeset objects in tabular format.
 * Assumes that a table with four columns has already been defined.
 * @param array Changeset objects
 * @param array Repository objects
 */
function Source_View_Changesets( $p_changesets, $p_repos=null, $p_show_repos=true ) {
	if ( !is_array( $p_changesets ) ) {
		return;
	}

	if ( is_null( $p_repos ) || !is_array( $p_repos ) ) {
		$t_repos = SourceRepo::load_by_changesets( $p_changesets );
	} else {
		$t_repos = $p_repos;
	}

	$t_use_porting = config_get( 'plugin_Source_enable_porting' );

	foreach( $p_changesets as $t_changeset ) {
		$t_repo = $t_repos[ $t_changeset->repo_id ];
		$t_vcs = SourceVCS::repo( $t_repo );

		$t_changeset->load_bugs();
		$t_changeset->load_files();

		$t_author = Source_View_Author( $t_changeset, false );
		$t_committer = Source_View_Committer( $t_changeset, false );
		?>

<tr>

<td class="category" width="25%" rowspan="<?php echo count( $t_changeset->files ) + 1 ?>">
	<a id="<?php echo $t_changeset->revision; ?>"></a>
	<p class="no-margin" name="changeset<?php echo $t_changeset->id ?>"><?php echo string_display(
		( $p_show_repos ? $t_repo->name . ': ' : '' ) .
		$t_vcs->show_changeset( $t_repo, $t_changeset )
		) ?></p>
	<p class="no-margin small lighter">
		<i class="fa fa-clock-o grey"></i> <?php echo string_display_line( $t_changeset->timestamp ) ?>
	</p>
	<p class="no-margin lighter">
		<i class="fa fa-user grey"></i> <?php echo $t_author ?></a>
	</p>
	<?php if ( $t_committer && $t_committer != $t_author ) { ?><br/><span class="small"><?php echo plugin_lang_get( 'committer', 'Source' ), ': ', $t_committer ?></span><?php } ?>
	<?php if ( $t_use_porting ) { ?>
		<p class="no-margin small lighter"><?php echo plugin_lang_get( 'ported', 'Source' ), ': ',
		( $t_changeset->ported ? string_display_line( $t_changeset->ported ) :
			( is_null( $t_changeset->ported ) ? plugin_lang_get( 'pending', 'Source' ) : plugin_lang_get( 'na', 'Source' ) ) ) ?>
		</p>
	<?php } ?>
		<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo plugin_page( 'view', false, 'Source' ) . '&id=' . $t_changeset->id ?>">
			<?php echo plugin_lang_get( 'details', 'Source' ) ?>
		</a>
		<?php
		if ( $t_url = $t_vcs->url_changeset( $t_repo, $t_changeset ) ) { ?>
		<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo $t_url ?>">
			<?php echo plugin_lang_get( 'diff', 'Source' ) ?>
		</a>
		<?php }
		?>
</td>
<td colspan="2"><?php
	# The commit message is manually transformed (adding href, bug and bugnote
	# links + nl2br) instead of calling string_display_links(), which avoids
	# unwanted html tags processing by the MantisCoreFormatting plugin.
	# Rationale: commit messages being plain text, any html they may contain
	# should not be considered as formatting and must be displayed as-is.
	echo string_nl2br(
			string_process_bugnote_link(
				string_process_bug_link(
					string_insert_hrefs(
						string_html_specialchars( $t_changeset->message )
		) ) ) );
	?>
</td>
<td>
<?php
		# Build list of related issues with link
		$t_bugs = array_map( 'string_get_bug_view_link', $t_changeset->bugs );

		if( $t_bugs ) {
			echo '<span class="bold">',
				plugin_lang_get( 'affected_issues', 'Source' ),
				'</span><br>';
			echo '<span>', implode( ', ', $t_bugs ), '</span>';
		} else {
?>
		<form action="<?php echo plugin_page( 'attach' )  ?>" method="post">
			<?php echo form_security_field( 'plugin_Source_attach' ) ?>
			<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
			<input type="hidden" name="redirect" value="<?php echo $t_changeset->revision ?>"/>
			<?php echo plugin_lang_get( 'attach_to_issue' ) ?><br>
			<input type="text" class="input-sm" name="bug_ids" size="12"/>
			<input type="submit"
				   class="btn btn-sm btn-primary btn-white btn-round"
				   value="<?php echo plugin_lang_get( 'attach' ) ?>" />
		</form>
<?php
		}
?>
</td>
</tr>

		<?php foreach ( $t_changeset->files as $t_file ) { ?>
<tr>
<td class="small" colspan="2"><?php echo string_display_line( $t_vcs->show_file( $t_repo, $t_changeset, $t_file ) ) ?></td>
<td class="center" width="15%">
		<?php
		if ( $t_url = $t_vcs->url_diff( $t_repo, $t_changeset, $t_file ) ) { ?>
			<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo $t_url ?>">
				<?php echo plugin_lang_get( 'diff', 'Source' ) ?>
			</a>
		<?php }
		if ( $t_url = $t_vcs->url_file( $t_repo, $t_changeset, $t_file ) ) { ?>
			<a class="btn btn-xs btn-primary btn-white btn-round" href="<?php echo $t_url ?>">
				<?php echo plugin_lang_get( 'file', 'Source' ) ?>
			</a>
		<?php }
		?></td>
</tr>
		<?php } ?>
		<?php
	}
}

/**
 * Display the author information for a changeset.
 * @param object Changeset object
 * @param boolean Echo information
 */
function Source_View_Author( $p_changeset, $p_echo=true ) {
	$t_author_name = !is_blank( $p_changeset->author ) ? string_display_line( $p_changeset->author ) : false;
	$t_author_email = !is_blank( $p_changeset->author_email ) ? string_display_line( $p_changeset->author_email ) : false;
	$t_author_username = $p_changeset->user_id > 0 ? prepare_user_name( $p_changeset->user_id ) : false;

	if ( $t_author_username ) {
		$t_output =  $t_author_username;

	} else if ( $t_author_name ) {
		$t_output =  $t_author_name;

	} else {
		$t_output =  $t_author_email;
	}

	if ( $p_echo ) {
		echo $t_output;
	} else {
		return $t_output;
	}
}

/**
 * Display the committer information for a changeset.
 * @param object Changeset object
 * @param boolean Echo information
 */
function Source_View_Committer( $p_changeset, $p_echo=true ) {
	$t_committer_name = !is_blank( $p_changeset->committer ) ? string_display_line( $p_changeset->committer ) : false;
	$t_committer_email = !is_blank( $p_changeset->committer_email ) ? string_display_line( $p_changeset->committer_email ) : false;
	$t_committer_username = $p_changeset->committer_id > 0 ? prepare_user_name( $p_changeset->committer_id ) : false;

	if ( $t_committer_username ) {
		$t_output =  $t_committer_username;

	} else if ( $t_committer_name ) {
		$t_output =  $t_committer_name;

	} else {
		$t_output =  $t_committer_email;
	}

	if ( $p_echo ) {
		echo $t_output;
	} else {
		return $t_output;
	}
}

/**
 * Display pagination links for changesets
 * @param string $p_link       URL to target page
 * @param int    $p_count      Total number of changesets
 * @param int    $p_current    Current page number
 * @param int    $p_perpage    Number of changesets per page
 */
function Source_View_Pagination( $p_link, $p_current, $p_count, $p_perpage = 25 ) {
	if( $p_count > $p_perpage ) {

		$t_pages = ceil( $p_count / $p_perpage );
		$t_block = max( 5, min( round( $t_pages / 10, -1 ), ceil( $t_pages / 6 ) ) );
		$t_page_set = array();

		$p_link .= '&offset=';

		$t_page_link = function( $p_page, $p_text = null ) use( $p_current, $p_link ) {
			if( is_null( $p_text ) ) {
				$p_text = $p_page;
			}
			if( is_null( $p_page ) ) {
				return '...';
			} elseif( $p_page == $p_current ) {
				return "<strong>$p_page</strong>";
			} else {
				$page_button = '<a class="btn btn-xs btn-primary btn-white btn-round" href="'. $p_link . $p_page .'">'.$p_text.'</a>';
				return $page_button;
			}
		};

		if( $t_pages > 15 ) {
			$t_used_page = false;
			$t_pages_per_block = 3;
			for( $i = 1; $i <= $t_pages; $i++ ) {
				if( $i <= $t_pages_per_block
				 || $i > $t_pages - $t_pages_per_block
				 || ( $i >= $p_current - $t_pages_per_block && $i <= $p_current + $t_pages_per_block )
				 || $i % $t_block == 0)
				{
					$t_page_set[] = $i;
					$t_used_page = true;
				} else if( $t_used_page ) {
					$t_page_set[] = null;
					$t_used_page = false;
				}
			}

		} else {
			$t_page_set = range( 1, $t_pages );
		}

		if( $p_current > 1 ) {
			echo '&nbsp;', $t_page_link( 1, lang_get( 'first' ) );
			echo '&nbsp;&nbsp;', $t_page_link( $p_current - 1, lang_get( 'prev' ) );
			echo '&nbsp;&nbsp;';
		}

		$t_page_set = array_map( $t_page_link, $t_page_set );
		echo join( ' ', $t_page_set );

		if( $p_current < $t_pages ) {
			echo '&nbsp;&nbsp;', $t_page_link( $p_current + 1, lang_get( 'next' ) );
			echo '&nbsp;&nbsp;', $t_page_link( $t_pages, lang_get( 'last' ) );
		}
	}
}
