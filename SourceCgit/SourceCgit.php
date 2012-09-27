<?php
/**
 * This script assumes cgit's virtual-root option is set
 */

# Copyright (c) 2011 asm89
# Licensed under the MIT license

if ( !defined('testing') ) {
	if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
		return;
	}

	require_once( config_get( 'core_path' ) . 'url_api.php' );
}

class SourceCgitPlugin extends MantisSourcePlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.16';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.16',
		);

		$this->author = 'Alexander';
		$this->contact = 'iam.asm89@gmail.com';
		$this->url = 'http://noswap.com/';
	}

	public $type = 'cgit';

	public function show_type() {
		return plugin_lang_get( 'cgit' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return	"$p_file->action - $p_file->filename";
	}

	private function uri_base( $p_repo ) {
		$t_uri_base = $p_repo->info['cgit_root'] . '' . $p_repo->info['cgit_project'] . '/';

		return $t_uri_base;
	}

	public function url_repo( $p_repo, $t_changeset=null ) {
		return $this->uri_base( $p_repo ) . ( $t_changeset ? 'commit/?id=' . $t_changeset->revision : '' );
	}

	public function url_commit ( $p_repo, $commit_rev) {
		return $this->uri_base( $p_repo ) . 'commit/?id=' . $commit_rev;
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->uri_base( $p_repo ) . ( $p_changeset ? 'commit/?id=' . $p_changeset->revision : '' );
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'tree/' . $p_file->filename . '?id=' . $p_changeset->revision;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'diff/' . $p_file->filename . '?id=' . $p_changeset->revision;

	}

	public function update_repo_form( $p_repo ) {
		$t_gitweb_root = null;
		$t_gitweb_project = null;

		if ( isset( $p_repo->info['cgit_root'] ) ) {
			$t_cgit_root = $p_repo->info['cgit_root'];
		}

		if ( isset( $p_repo->info['cgit_project'] ) ) {
			$t_cgit_project = $p_repo->info['cgit_project'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'cgit_root' ) ?></td>
<td><input name="cgit_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_cgit_root ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'cgit_project' ) ?></td>
<td><input name="cgit_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_cgit_project ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'master_branch' ) ?></td>
<td><input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/></td>
</tr>
<?php
	}

	public function update_repo( $p_repo ) {
		$f_cgit_root = gpc_get_string( 'cgit_root' );
		$f_cgit_project = gpc_get_string( 'cgit_project' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$p_repo->info['cgit_root'] = $f_cgit_root;
		$p_repo->info['cgit_project'] = $f_cgit_project;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	public function precommit( ) {
		# TODO: Implement real commit sequence.
		return;
	}

	public function commit( $p_repo, $p_data ) {
		# The -d option from curl requires you to encode your own data.
		# Once it reaches here it is decoded. Hence we split by a space
		# were as the curl command uses a '+' character instead.
		# i.e. DATA=`echo $INPUT | sed -e 's/ /+/g'`
		list ( , $t_commit_id, $t_branch) = split(' ', $p_data);
		list ( , , $t_branch) = split('/', $t_branch);
		$master_branches = map( 'trim', explode( ',', $p_repo->info['master_branch']));
		if (!in_array($t_branch,$master_branches) )
		{
				return;
		}

		return $this->import_commits($p_repo, null, $t_commit_id, $t_branch);
	}

	public function import_full( $p_repo ) {
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
			$t_query = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
				'ORDER BY timestamp ASC';
			$t_result = db_query_bound( $t_query, array( $p_repo->id, $t_branch ), 1 );

			$t_commits = array( $t_branch );

			if ( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo "Oldest '$t_branch' branch parent: '$t_parent'\n";

				if ( !empty( $t_parent ) ) {
					$t_commits[] = $t_parent;
				}
			}

			$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits, $t_branch  ) );
		}

		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	private function import_commits( $p_repo, $p_uri_base, $p_commit_ids, $p_branch='' ) {
		static $s_parents = array();
		static $s_counter = 0;

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );

			echo "Retrieving $t_commit_id ... ";

			$t_commit_url = $this->url_commit( $p_repo, $t_commit_id);
			$t_input = url_get( $t_commit_url );

			if ( false === $t_input ) {
				echo "failed.\n";
				continue;
			}

			list( $t_changeset, $t_commit_parents ) = $this->commit_changeset( $p_repo, $t_input, $p_branch );
			if ( !is_null( $t_changeset ) ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
		}

		$s_counter = 0;
		return $t_changesets;
	}

	/**
	 * Parses the revision from a cgit page.
	 * 
	 * @param string $p_input cgit html page
	 * @return string the revision
	 */
	public function commit_revision( $p_input ) {
		preg_match( "#<tr><th>commit</th><td colspan='2' class='sha1'><a href='/(.*?)/commit/\?id=([a-f0-9]*)'>([a-f0-9]*)</a>#", $p_input, $t_matches);
		return $t_matches[2];
	}

	/**
	 * Parses the author and comitter from a cgit page.
	 * 
	 * @param string $p_input cgit html page
	 * @return array author / committer
	 */
	public function commit_author( $p_input ) {
		preg_match( "#<tr><th>author</th><td>(.*?)<(.*?)></td><td class='right'>(.*?)</td>#", $p_input, $t_matches);
		$t_commit['author'] = trim($t_matches[1]);
		$t_commit['author_email'] = $t_matches[2];
		$t_commit['date'] = date( 'Y-m-d H:i:s', strtotime( $t_matches[3] ) );

		if( preg_match( "#<tr><th>committer</th><td>(.*?)<(.*?)></td><td class='right'>(.*?)</td>#", $p_input, $t_matches) ) {
			$t_commit['committer'] = trim($t_matches[1]);
			$t_commit['committer_email'] = $t_matches[2];
		}

		return $t_commit;
	}

	/**
	 * Parses the parent commits from a cgit page.
	 *
	 * @param string $p_input cgit html page
	 * @return array the parents
	 */
	public function commit_parents( $p_input ) {
		$t_parents = array();
		if( preg_match_all( "#<tr><th>parent</th><td colspan='2' class='sha1'><a href='/(.*?)/commit/\?id=([a-f0-9]*)'>([a-f0-9]*)</a>#", $p_input, $t_matches) ) {
			foreach( $t_matches[2] as $t_match ) {
				$t_parents[] = $t_commit['parent'] = $t_match;
			}
		}
		return $t_parents;
	}

	/**
	 * Parses the message from a cgit page.
	 * 
	 * @param string $p_input cgit html page
	 * @return string
	 */
	public function commit_message( $p_input ) {
		preg_match( "#<div class='commit-subject'>(.*?)(<a class=|</div>)#", $p_input, $t_matches);
		$t_message = trim( str_replace( '<br/>', PHP_EOL, $t_matches[1] ) );

		# Strip ref links and signoff spans from commit message
		$t_message = preg_replace( array(
				'@<a[^>]*>([^<]*)<\/a>@',
				'@<span[^>]*>([^<]*<[^>]*>[^<]*)<\/span>@', #finds <span..>signed-off by <email></span>
			), '$1', $t_message );
		return $t_message;
	}

	/**
	 * Parses the commit file from a cgit page.
	 *
	 * @param string $p_input cgit html page
	 * @return array files
	 */
	public function commit_files( $p_input ) {
		$t_files = array();
		preg_match_all( "#<td class='mode'>(.*?)</td><td class='(.*?)'><a href='(.*?)\?id=([0-9a-f]*)'>(.*?)</a>#", $p_input, $t_matches, PREG_SET_ORDER);
		foreach( $t_matches as $t_file_matches ) {
			$t_file = array();
			$t_file['filename'] = $t_file_matches[5];
			$t_file['revision'] = $t_file_matches[4];

			if ( 'add' == $t_file_matches[2] ) {
				$t_file['action'] = 'add';
			} else if ( 'del' == $t_file_matches[2] ) {
				$t_file['action'] = 'rm';
			} else {
				$t_file['action'] = 'mod';
			}
			$t_files[] = $t_file;
		}
		return $t_files;
	}

	/**
	 * Cleans the input html.
	 * 
	 * @param string $p_input cgit html page
	 * @return string
	 */
	public function clean_input( $p_input ) {
		return	str_replace( array(PHP_EOL, '&lt;', '&gt;', '&nbsp;'), array('', '<', '>', ' '), $p_input );
	}

	private function commit_changeset( $p_repo, $p_input, $p_branch='' ) {

		// Clean the input
		$t_input = $this->clean_input( $p_input );

		// Get the revision
		$t_commit['revision'] = $this->commit_revision( $t_input );;

		echo "processing $t_commit[revision] ... ";

		// Only process if it doesn't exist yet
		if ( !SourceChangeset::exists( $p_repo->id, $t_commit['revision'] ) ) {

			// Author and committer data
			$t_commit = array_merge($this->commit_author( $t_input ), $t_commit);

			// Commit parents
			$t_parents = $this->commit_parents( $t_input );

			// Set the last parent as the main parent of this commit
			$t_commit['parent'] = $t_parents[ count($t_parents) - 1 ];

			// Set the message
			$t_commit['message'] = $this->commit_message( $t_input );

			# Parse for changed file data
			$t_commit['files'] = $this->commit_files( $t_input );

			// Create the changeset
			$t_changeset = new SourceChangeset( $p_repo->id, $t_commit['revision'], $p_branch,
				$t_commit['date'], $t_commit['author'], $t_commit['message'], 0,
				( isset( $t_commit['parent'] ) ? $t_commit['parent'] : '' ) );

			$t_changeset->author_email = $t_commit['author_email'];
			$t_changeset->committer = $t_commit['committer'];
			$t_changeset->committer_email = $t_commit['committer_email'];

			foreach( $t_commit['files'] as $t_file ) {
				$t_changeset->files[] = new SourceFile( 0, $t_file['revision'], $t_file['filename'], $t_file['action'] );
			}

			$t_changeset->save();

			echo "saved.\n";
			return array( $t_changeset, $t_parents );
		} else {
			echo "already exists.\n";
			return array( null, array() );
		}
	}
}
