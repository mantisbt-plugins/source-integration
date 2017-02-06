<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );

class SourceGitphpPlugin extends MantisSourcePlugin {

	const PLUGIN_VERSION = '2.0.0';
	const FRAMEWORK_VERSION_REQUIRED = '2.0.0';

	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
		);

		$this->author = 'John Reese';
		$this->contact = 'john@noswap.com';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration/';
	}

	public $type = 'gitphp';

	public function show_type() {
		return plugin_lang_get( 'gitphp' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return  "$p_file->action - $p_file->filename";
	}

	private function uri_base( $p_repo ) {
		$t_uri_base = $p_repo->info['gitphp_root'] . '?p=' . $p_repo->info['gitphp_project'] . '&';
		return $t_uri_base;
	}

	public function url_repo( $p_repo, $t_changeset=null ) {
		return $this->uri_base( $p_repo ) . ( $t_changeset ? 'h=' . $t_changeset->revision : '' );
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->uri_base( $p_repo ) . 'a=commitdiff&h=' . $p_changeset->revision;
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'a=blob&f=' . $p_file->filename .
			'&h=' . $p_file->revision . '&hb=' . $p_changeset->revision;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'a=blobdiff&f=' . $p_file->filename .
			'&h=' . $p_file->revision . '&hb=' . $p_changeset->revision . '&hp=' . $p_changeset->parent;
	}

	public function update_repo_form( $p_repo ) {
		$t_gitphp_root = null;
		$t_gitphp_project = null;

		if ( isset( $p_repo->info['gitphp_root'] ) ) {
			$t_gitphp_root = $p_repo->info['gitphp_root'];
		}

		if ( isset( $p_repo->info['gitphp_project'] ) ) {
			$t_gitphp_project = $p_repo->info['gitphp_project'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>
<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'gitphp_root' ) ?></span></label>
	<span class="input">
		<input name="gitphp_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_gitphp_root ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'gitphp_project' ) ?></span></label>
	<span class="input">
		<input name="gitphp_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_gitphp_project ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'master_branch' ) ?></span></label>
	<span class="input">
		<input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<?php
	}

	public function update_repo( $p_repo ) {
		$f_gitphp_root = gpc_get_string( 'gitphp_root' );
		$f_gitphp_project = gpc_get_string( 'gitphp_project' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$p_repo->info['gitphp_root'] = $f_gitphp_root;
		$p_repo->info['gitphp_project'] = $f_gitphp_project;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	public function precommit( ) {
		# TODO: Implement real commit sequence.
		return;
	}

	public function commit( $p_repo, $p_data ) {
		# Handle branch names with '+' character
		$p_data = str_replace('_plus_', '+', $p_data);

		# The -d option from curl requires you to encode your own data.
		# Once it reaches here it is decoded. Hence we split by a space
		# were as the curl command uses a '+' character instead.
		# i.e. DATA=`echo $INPUT | sed -e 's/ /+/g'`
		list ( , $t_commit_id, $t_branch) = explode(' ', $p_data);
		list ( , , $t_branch) = explode('/', $t_branch, 3);

		# master_branch contains comma-separated list of branches
		$t_branches = explode(',', $p_repo->info['master_branch']);
		if (!in_array('*', $t_branches) and !in_array($t_branch, $t_branches))
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

		if ($t_branch != '*')
		{
			$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		}
		else
		{
			$t_heads_url = $this->uri_base( $p_repo ) . 'a=heads';
			$t_branches_input = url_get( $t_heads_url );

			$t_branches_input = str_replace( array("\r", "\n", '&lt;', '&gt;', '&nbsp;'), array('', '', '<', '>', ' '), $t_branches_input );

			$t_branches_input_p1 = strpos( $t_branches_input, '<table class="heads">' );
			$t_branches_input_p2 = strpos( $t_branches_input, '<div class="page_footer">' );
			$t_gitphp_heads = substr( $t_branches_input, $t_branches_input_p1, $t_branches_input_p2 - $t_branches_input_p1 );
			preg_match_all( '/<a class="list name".*>(.*)<\/a>/iU', $t_gitphp_heads, $t_matches, PREG_SET_ORDER );

			$t_branches = array();
			foreach ($t_matches as $match)
			{
				$t_branch = trim($match[1]);
				if ($match[1] != 'origin' and !in_array($t_branch,$t_branches))
				{
					$t_branches[] = $t_branch;
				}
			}
		}

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

			echo "Retrieving $t_commit_id ...\n ";

			# Handle branch names with '+' character
			$t_fixed_id = str_replace('+', '%2B', $t_commit_id);
			$t_commit_url = $this->uri_base( $p_repo ) . 'a=commit&h=' . $t_fixed_id;
			$t_input = url_get( $t_commit_url );

			if ( !$t_input ) {
				echo "failed.\n";
				echo "'$t_commit_url' did not return any data.\n";
				die();
			}

			list( $t_changeset, $t_commit_parents ) = $this->commit_changeset( $p_repo, $t_input, $p_branch );
			if ( !is_null( $t_changeset ) ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
			$s_counter += 1;
		}

		$s_counter = 0;
		return $t_changesets;
	}

	private function commit_changeset( $p_repo, $p_input, $p_branch='' ) {

		$t_input = str_replace( array("\r", "\n", '&lt;', '&gt;', '&nbsp;'), array('', '', '<', '>', ' '), $p_input );

		# Extract sections of commit data and changed files
		$t_input_p1 = strpos( $t_input, '<div class="title">' );
		$t_input_p2 = strpos( $t_input, '<div class="list_head">' );
		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo "commit data failure.\n";
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			die();
		}
		$t_gitphp_data = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );

		$t_input_p1 = strpos( $t_input, '<table class="diff_tree">' );

		if ( false === $t_input_p1 ) {
			$t_input_p1 = strpos( $t_input, '<tr class="light">' );
			if ( false === $t_input_p1 ) {
				$t_input_p1 = strpos( $t_input, '<tr class="dark">' );
			}
		}

		$t_input_p2 = strpos( $t_input, '<div class="page_footer">' );

		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo 'file data failure.';
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			print_r($t_input);
			die();
		}

		$t_gitphp_files = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );


		# Get commit revsion and make sure it's not a dupe
		preg_match( '#<td class="monospace">([a-f0-9]*)#', $t_gitphp_data, $t_matches );

		$t_commit['revision'] = $t_matches[1];

		echo "processing $t_commit[revision] ... \n";

		if ( !SourceChangeset::exists( $p_repo->id, $t_commit['revision'] ) ) {

			# Parse for commit data
			# m modifier means "make . include newlines"
			# x modifier means "ignore whitespace"
			preg_match( '#<td>author</td>.*?<td>(.*?)<.*?<time.*?>(.*?)<.*?<td>committer</td>.*?<td>(.*?)<.*<div.class="page_body">(.*?)</div>.*#mx',
				$t_gitphp_data, $t_matches );

			$t_commit['author'] = $t_matches[1];
			# gitphp doesn't parse email address for some reason?
			$t_commit['author_email'] = "n/a";
			$t_commit['date'] = date( 'Y-m-d H:i:s', strtotime( $t_matches[2] ) );
			$t_commit['committer'] = $t_matches[3];
			$t_commit['committer_email'] = "n/a";
			$t_commit['message'] = trim( preg_replace( '#<br.*?>#', PHP_EOL, $t_matches[4] ) );

			$t_parents = array();

			if ( preg_match_all( '#parent</td>.*?<td.class="monospace">.*?<a.*?>(.*?)<.*#mx', $t_gitphp_data, $t_matches ) ) {
				foreach( $t_matches[1] as $t_match ) {
					$t_parents[] = $t_commit['parent'] = $t_match;
				}
			}

			# Strip ref links and signoff spans from commit message
			$t_commit['message'] = preg_replace( array( '#<a[^>]*>([^<]*)</a>#', '#<span[^>]*>(.*?)</span>#' ),
				'$1', $t_commit['message'] );

			# Prepend a # sign to mantis number
			$t_commit['message'] = preg_replace( '#(mantis)\s+(\d+)#i', '$1 #$2',$t_commit['message'] );

			# Parse for changed file data
			$t_commit['files'] = array();

			preg_match_all( '#class="list".*?h=(\w*).*?f=(.*?)".*?<.a>#',
				$t_gitphp_files, $t_matches, PREG_SET_ORDER );

			foreach( $t_matches as $t_file_matches ) {
				$t_file = array();
				$t_file['filename'] = str_replace('%2F', '/', $t_file_matches[2]);
				$t_file['revision'] = $t_file_matches[1];

				if ( isset( $t_file_matches[3] ) ) {
					if ( $t_file_matches[3] == 'new' or $t_file_matches[3] == 'moved' ) {
						$t_file['action'] = 'add';
					} else if ( $t_file_matches[3] == 'deleted' or $t_file_matches[3] == 'similarity' ) {
						$t_file['action'] = 'rm';
					} else {
						$t_file['action'] = 'mod';
					}
				} else {
					$t_file['action'] = 'mod';
				}

				$t_commit['files'][] = $t_file;
			}

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
