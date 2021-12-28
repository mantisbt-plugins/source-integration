<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

/**
 * Display a list of changeset objects in tabular format.
 *
 * Assumes that a table with four columns has already been defined.
 *
 * @param SourceChangeset[] $p_changesets
 * @param array|SourceRepo  $p_repos      List of repositories, if null will be
 *                                        loaded based on changesets
 * @param bool              $p_show_repos
 */
function Source_View_Changesets( $p_changesets, $p_repos=null, $p_show_repos=true ) {
	if ( !is_array( $p_changesets ) ) {
		return;
	}

	plugin_push_current( 'Source' );
	$t_can_update = access_has_global_level( plugin_config_get( 'update_threshold' ) );
	plugin_pop_current();
	
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

		bug_cache_array_rows( $t_changeset->bugs );

		$t_author = Source_View_Author( $t_changeset, false );
		$t_committer = Source_View_Committer( $t_changeset, false );
?>
<tr>
<td class="category width-25" rowspan="<?php echo count( $t_changeset->files ) + 1 ?>">
	<a id="<?php echo $t_changeset->revision; ?>"></a>
	<p class="no-margin"><?php
		echo string_display(
			( $p_show_repos ? $t_repo->name . ': ' : '' )
			. $t_vcs->show_changeset( $t_repo, $t_changeset )
		)
	?></p>
	<p class="no-margin small lighter">
		<i class="fa fa-clock-o grey"></i>
		<?php echo string_display_line( $t_changeset->getLocalTimestamp() ) ?>
	</p>
	<p class="no-margin lighter">
		<i class="fa fa-user grey"></i> <?php echo $t_author ?>
	</p>
<?php
		if( $t_committer && $t_committer != $t_author ) {
?>
	<br>
	<span class="small">
		<?php echo plugin_lang_get( 'committer', 'Source' ), ': ', $t_committer ?>
	</span>
<?php
		}

		if( $t_use_porting ) {
?>
	<p class="no-margin small lighter"><?php
		echo plugin_lang_get( 'ported', 'Source' ), ': ',
			$t_changeset->ported
				? string_display_line( $t_changeset->ported )
				: ( is_null( $t_changeset->ported )
					? plugin_lang_get( 'pending', 'Source' )
					: plugin_lang_get( 'na', 'Source' )
				)
	?></p>
<?php
		}

		print_link_button(
			plugin_page( 'view', false, 'Source' ) . '&id=' . $t_changeset->id,
			plugin_lang_get( 'details', 'Source' ),
			'btn-xs'
		);

		if( $t_url = $t_vcs->url_changeset( $t_repo, $t_changeset ) ) {
			echo "\n";
			print_link_button(
				$t_url,
				plugin_lang_get( 'diff', 'Source' ),
				'btn-xs'
			);
		}
?>
</td>

<?php
		# Build list of related issues the user has access to, with link
		$t_view_bug_threshold = config_get('view_bug_threshold');
		$t_bugs = array_map(
			'string_get_bug_view_link',
			array_filter(
				$t_changeset->bugs,
				function( $p_bug_id ) use ( $t_view_bug_threshold ) {
					return bug_exists( $p_bug_id )
						&& access_has_bug_level( $t_view_bug_threshold, $p_bug_id );
				}
			)
		);

		# Only display the table cell for attached issues if necessary
		$t_show_linked_bugs_column = $t_bugs || $t_can_update;
?>
<td colspan=<?php echo $t_show_linked_bugs_column ? 2 : 3 ?>><?php
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

<?php
		if( $t_show_linked_bugs_column ) {
?>
<td>
<?php
			if( $t_bugs ) {
				echo '<span class="bold">',
					plugin_lang_get( 'affected_issues', 'Source' ),
					'</span><br>';
				echo '<span>', implode( ', ', $t_bugs ), '</span>';
			} elseif( $t_can_update ) {
?>
	<form action="<?php echo plugin_page( 'attach' )  ?>" method="post">
		<?php echo form_security_field( 'plugin_Source_attach' ) ?>
		<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
		<input type="hidden" name="redirect" value="<?php echo $t_changeset->revision ?>"/>
		<label>
			<?php echo plugin_lang_get( 'attach_to_issue' ) ?><br>
			<input type="text" class="input-sm" name="bug_ids" size="12"/>
		</label>
		<input type="submit"
			   class="btn btn-sm btn-primary btn-white btn-round"
			   value="<?php echo plugin_lang_get( 'attach' ) ?>" />
	</form>
<?php
			}
		}
?>
</td>

</tr>
<?php
		foreach( $t_changeset->files as $t_file ) {
?>
<tr>
<td class="small" colspan="2">
	<?php echo string_display_line( $t_vcs->show_file( $t_repo, $t_changeset, $t_file ) ) ?>
</td>
<td class="center width-13">
<?php
			if( $t_url = $t_vcs->url_diff( $t_repo, $t_changeset, $t_file ) ) {
				print_link_button(
					$t_url,
					plugin_lang_get( 'diff', 'Source' ),
					'btn-xs'
				);
			}
			echo "\n";
			if( $t_url = $t_vcs->url_file( $t_repo, $t_changeset, $t_file ) ) {
				print_link_button(
					$t_url,
					plugin_lang_get( 'file', 'Source' ),
					'btn-xs'
				);
			}
?>
</td>
</tr>
<?php
		} # end foreach changeset files
	} # end foreach changesets
}

/**
 * Display the author information for a changeset.
 *
 * @param SourceChangeset $p_changeset Changeset object
 * @param bool            $p_echo      Echo information if true, returns it otherwise
 *
 * @return string|void
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
 *
 * @param SourceChangeset $p_changeset Changeset object
 * @param bool            $p_echo      Echo information if true, returns it otherwise
 *
 * @return string|void
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
				return '<a class="btn btn-xs btn-primary btn-white btn-round" href="'
					. $p_link . $p_page .'">' . $p_text . '</a>';
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
