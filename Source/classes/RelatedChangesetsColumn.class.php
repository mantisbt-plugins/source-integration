<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

class SourceRelatedChangesetsColumn extends MantisColumn {

	public $column = "related_changesets";
	public $sortable = false;

	private $changeset_cache = array();

	public function __construct() {
		plugin_push_current( 'Source' );

		$this->title = plugin_lang_get( 'changeset_column_title' );

		plugin_pop_current();
	}

	public function cache( $p_bugs ) {
		if ( count( $p_bugs ) < 1 ) {
			return;
		}

		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_bug_ids = array();
		foreach ( $p_bugs as $t_bug ) {
			$t_bug_ids[] = $t_bug->id;
		}

		$t_bug_ids = implode( ',', $t_bug_ids );

		$t_query = "SELECT * FROM $t_bug_table WHERE bug_id IN ( $t_bug_ids )";
		$t_result = db_query_bound( $t_query );

		while ( $t_row = db_fetch_array( $t_result ) ) {
			if ( isset( $this->changeset_cache[ $t_row['bug_id'] ] ) ) {
				$this->changeset_cache[ $t_row['bug_id'] ]++;

			} else {
				$this->changeset_cache[ $t_row['bug_id'] ] = 1;
			}
		}
	}

	public function display( $p_bug, $p_columns_target ) {
		plugin_push_current( 'Source' );

		if ( isset( $this->changeset_cache[ $p_bug->id ] ) ) {
			echo '<a href="view.php?id=', $p_bug->id, '#changesets">', $this->changeset_cache[ $p_bug->id ], '</a>';
		}

		plugin_pop_current();
	}

}
