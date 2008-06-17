<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

define( 'SOURCE_ANY', 0 );
define( 'SOURCE_NONE', 1 );

class SourceFilterOption {
	var $how = SOURCE_ANY;
	var $value;

	function __construct() {
		$args = func_get_args();
		$count = func_num_args();

		if ( $count > 0 ) {
			$this->how = $args[0];
		}

		if ( $count == 2 ) {
			$this->value = $args[1];
		}
		else if ( $count > 2 ) {
			$this->value = array_slice( $args, 1 );
		}
	}
}

class SourceFilter {
	var $filters;

	function __construct( $init = true ) {
		if ( $init ) {
			$this->filters['c.author'] = new SourceFilterOption();
			$this->filters['c.message'] = new SourceFilterOption();
			$this->filters['c.repo_id'] = new SourceFilterOption();
			$this->filters['r.type'] = new SourceFilterOption();
			$this->filters['b.bug_id'] = new SourceFilterOption();
			$this->filters['f.filename'] = new SourceFilterOption();
		}
	}

	function find( $p_page=1, $p_limit=25 ) {
		list( $t_filters, $t_filter_params ) = twomap( 'Source_Process_FilterOption', $this->filters );
		list ( $t_query_tail, $t_params ) = Source_Process_Filters( $t_filters, $t_filter_params );

		$t_count_query = "SELECT COUNT(c.id) $t_query_tail";
		$t_full_query = "SELECT c.* $t_query_tail";

		$t_count = db_result( db_query_bound( $t_count_query, $t_params ) );

		if ( is_null( $p_page ) ) {
			$t_result = db_query_bound( $t_full_query, $t_params );
		} else {
			$t_result = db_query_bound( $t_full_query, $t_params, $p_limit, ( $p_page - 1 ) * $p_limit );
		}

		$t_changesets = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'], $t_row['branch'],
				$t_row['timestamp'], $t_row['author'], $t_row['message'], $t_row['user_id'] );
			$t_changeset->id = $t_row['id'];

			$t_changesets[] = $t_changeset;
		}

		return array( $t_changesets, $t_count );
	}
}

function Source_Process_Filters( $p_filters, $p_filter_params ) {
	$t_changeset_table = plugin_table( 'changeset', 'Source' );
	$t_repo_table = plugin_table( 'repository', 'Source' );
	$t_bug_table = plugin_table( 'bug', 'Source' );
	$t_file_table = plugin_table( 'file', 'Source' );

	$t_join_file_table = false;
	$t_join_bug_table = false;

	$t_where = array();
	$t_params = array();

	foreach( $p_filters as $key => $value ) {
		if ( is_null( $value ) ) {
			continue;
		}

		$t_table = substr( $key, 0, 1 );
		switch( $t_table ) {
			case 'b':
				$t_join_bug_table = true;
				break;
			case 'f':
				$t_join_file_table = true;
				break;
		}

		$t_where[] = $value;

		if ( is_array( $p_filter_params[$key] ) ) {
			$t_params = array_merge( $t_params, $p_filter_params[$key] );
		} else {
			$t_params[] = $p_filter_params[$key];
		}
	}

	$t_join = "FROM $t_changeset_table AS c LEFT JOIN $t_repo_table AS r ON c.repo_id=r.id" .
		( $t_join_bug_table ? ' LEFT JOIN $t_bug_table AS b ON c.id=b.change_id' : '' ) .
		( $t_join_file_table ? ' LEFT JOIN $t_file_table AS f ON c.id=f.change_id' : '' );

	if ( count( $t_where ) > 0 ) {
		$t_where = 'WHERE ' . implode( ' AND ', $t_where );
	}

	$t_order = 'ORDER BY c.timestamp DESC';

	return array( "$t_join $t_where $t_order", $t_params );
}

function Source_Process_FilterOption( $key, $option ) {
	if ( !is_a( $option, 'SourceFilterOption' ) ) {
		return null;
	} else {
		$how = $option->how;
		$var = $option->value;
	}

	if ( is_null( $var ) ) {
		return array( null, null );
	}

	$value = null;
	$text = false;

	if ( in_array( $key, array( 'c.author', 'c.message', 'c.revision',
								'c.branch', 'f.filename', 'f.revision' ) ) ) {
		$text = true;

		if ( !is_array( $var ) ) {
			$var = explode( ' ', $var );
		}

		$wc = map( 'db_aparam', $var );
		$wc = map( create_function( '$item','return "' . $key . ' LIKE $item";' ), $wc );
		$var = map( create_function( '$item', 'return "%$item%";' ), $var );

		$value = '(' . implode( ' OR ', $wc ) . ')';

		return array( $value, $var );
	}

	if ( is_array( $var ) ) {
		$wc = map( 'db_aparam', $var );

		if ( count( $var ) > 1 ) {
			if ( SOURCE_ANY == $how ) {
				$value = $key . ' IN (' . implode( ',', $wc ) . ')';
			} else {
				$value = $key . ' NOT IN (' . implode( ',', $wc ) . ')';
			}
		} else {
			if ( SOURCE_ANY == $how ) {
				$value = "$key = $wc";
			} else {
				$value = "$key != $wc";
			}
		}
	} else {
		$wc = db_aparam();

		if ( SOURCE_ANY == $how ) {
			$value = "$key = $wc";
		} else {
			$value = "$key != $wc";
		}
	}

	return array( $value, $var );
}

