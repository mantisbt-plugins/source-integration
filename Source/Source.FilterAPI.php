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
			$this->filters['c.branch'] = new SourceFilterOption();
			$this->filters['c.author'] = new SourceFilterOption();
			$this->filters['c.message'] = new SourceFilterOption();

			$this->filters['r.id'] = new SourceFilterOption();
			$this->filters['r.type'] = new SourceFilterOption();

			$this->filters['b.bug_id'] = new SourceFilterOption();

			$this->filters['f.filename'] = new SourceFilterOption();
			$this->filters['f.revision'] = new SourceFilterOption();
			$this->filters['f.action'] = new SourceFilterOption();
		}
	}

	function find( $p_page=1, $p_limit=25 ) {
		list( $t_filters, $t_filter_params ) = twomap( 'Source_Process_FilterOption', $this->filters );
		list ( $t_query_tail, $t_params ) = Source_Process_Filters( $t_filters, $t_filter_params );

		$t_count_query = "SELECT COUNT(c.id) $t_query_tail";
		$t_full_query = "SELECT DISTINCT( c.id ), c.* $t_query_tail";

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
		( $t_join_bug_table ? " LEFT JOIN $t_bug_table AS b ON c.id=b.change_id" : '' ) .
		( $t_join_file_table ? " LEFT JOIN $t_file_table AS f ON c.id=f.change_id" : '' );

	if ( count( $t_where ) > 0 ) {
		$t_where = 'WHERE ' . implode( ' AND ', $t_where );
	} else {
		$t_where = '';
	}

	$t_order = 'ORDER BY c.timestamp DESC';

	return array( "$t_join $t_where $t_order", $t_params );
}

function Source_Process_FilterOption( $key, $option ) {
	if ( !is_a( $option, 'SourceFilterOption' ) ) {
		return null;
	} else {
		$how = $option->how;
		$value = $option->value;
	}

	if ( is_null( $value ) || ( !is_array( $value ) && is_blank( $value ) ) ) {
		return array( null, null );
	}

	$sql = null;
	$text = false;

	# Date searching
	if ( $key == 'date_start' && !is_null( $value ) ) {
		$wc = db_aparam();
		$sql = "c.timestamp >= $wc";

		return array( $sql, $value );
	}
	if ( $key == 'date_end' && !is_null( $value ) ) {
		$wc = db_aparam();
		$sql = "c.timestamp <= $wc";

		return array( $sql, $value );
	}

	# Revision Searching
	if ( $key == 'f.revision' && !is_null( $value ) )	{
		$wc1 = db_aparam();
		$wc2 = db_aparam();
		$value = "%$value%";
		$sql = "( c.revision LIKE $wc1 OR f.revision LIKE $wc2 )";

		return array( $sql, array( $value, $value ) );
	}

	# Full-text searching
	if ( in_array( $key, array( 'c.author', 'c.message', 'f.filename' ) ) ) {

		if ( !is_array( $value ) ) {
			$value = explode( ' ', $value );
		}

		$wc = map( 'db_aparam', $value );
		$wc = map( create_function( '$item','return "' . $key . ' LIKE $item";' ), $wc );
		$value = map( create_function( '$item', 'return "%$item%";' ), $value );

		$sql = '(' . implode( ' OR ', $wc ) . ')';

		return array( $sql, $value );
	}

	# Standard values
	if ( is_array( $value ) ) {
		$wc = map( 'db_aparam', $value );

		if ( count( $value ) > 1 ) {
			if ( SOURCE_ANY == $how ) {
				$sql = $key . ' IN (' . implode( ',', $wc ) . ')';
			} else {
				$sql = $key . ' NOT IN (' . implode( ',', $wc ) . ')';
			}
		} else {
			$wc = $wc[0];

			if ( SOURCE_ANY == $how ) {
				$sql = "$key = $wc";
			} else {
				$sql = "$key != $wc";
			}
		}
	} else {
		$wc = db_aparam();

		if ( SOURCE_ANY == $how ) {
			$sql = "$key = $wc";
		} else {
			$sql = "$key != $wc";
		}
	}

	return array( $sql, $value );
}

### Search filter input/link handling

function Source_Generate_Filter() {
	# Get form inputs
	$f_repo_type = Source_FilterOption_Permalink( 'repo_type', true );
	$f_repo_id = Source_FilterOption_Permalink( 'repo_id', true );
	$f_branch = Source_FilterOption_Permalink( 'branch', true );
	$f_file_action = Source_FilterOption_Permalink( 'file_action', true );
	$f_user_id = Source_FilterOption_Permalink( 'user_id' );

	$f_revision = Source_FilterOption_Permalink( 'revision' );
	$f_author = Source_FilterOption_Permalink( 'author' );
	$f_user_id = Source_FilterOption_Permalink( 'user_id' );

	$f_filename = Source_FilterOption_Permalink( 'filename' );
	$f_message = Source_FilterOption_Permalink( 'message' );

	# Get permalink
	$t_permalink = Source_FilterOption_Permalink();

	# Create filter
	$t_filter = new SourceFilter();

	$t_filter->filters['c.branch']->value = $f_branch;
	$t_filter->filters['c.message']->value = $f_message;
	$t_filter->filters['c.author']->value = $f_author;
	$t_filter->filters['c.user_id']->value = $f_user_id;

	$t_filter->filters['r.id']->value = $f_repo_id;
	$t_filter->filters['r.type']->value = $f_repo_type;

	$t_filter->filters['f.filename']->value = $f_filename;
	$t_filter->filters['f.revision']->value = $f_revision;
	$t_filter->filters['f.action']->value = $f_file_action;

	return array( $t_filter, $t_permalink );
}

function Source_FilterOption_Permalink( $p_string=null, $p_array=false ) {
	static $s_permalink = '';

	if ( is_null( $p_string ) ) {
		$t_string = $s_permalink;
		$s_permalink = '';
		return $t_string;
	}

	if ( $p_array ) {
		$t_input = gpc_get_string_array( $p_string, null );

		if ( is_array( $t_input ) && count( $t_input ) > 0 ) {
			foreach( $t_input as $t_value ) {
				if ( !is_blank( $t_value ) ) {
					$s_permalink .= "&${p_string}[]=$t_value";
				}
			}
		}

	} else {
		$t_input = gpc_get_string( $p_string, null );
		if ( !is_blank( $t_input ) ) {
			$s_permalink .= "&$p_string=$t_input";
		}
	}

	return $t_input;
}

### Search filter printing

function Source_Repo_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_repo_table = plugin_table( 'repository' );

	$t_query = "SELECT id,name FROM $t_repo_table ORDER BY name ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="repo_id[]" multiple="multiple" size="6">';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option value="', (int)$t_row['id'],
			( in_array( $t_row['id'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['name'] ), '</option>';
	}

	echo '</select>';
}

function Source_Type_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_types = SourceTypes();
	$t_repo_table = plugin_table( 'repository' );

	$t_query = "SELECT DISTINCT( type ) FROM $t_repo_table ORDER BY type ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="repo_type[]" multiple="multiple" size="6">';

	while( $t_row = db_fetch_array( $t_result ) ) {
		if ( !isset( $t_types[ $t_row['type'] ] ) ) {
			$t_types[ $t_row['type'] ] = $t_row['type'];
		}

		echo '<option value="', string_attribute( $t_row['type'] ),
			( in_array( $t_row['type'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_types[ $t_row['type'] ] ), '</option>';
	}

	echo '</select>';
}

function Source_Branch_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_changeset_table = plugin_table( 'changeset' );

	$t_query = "SELECT DISTINCT( branch ) FROM $t_changeset_table ORDER BY branch ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="branch[]" multiple="multiple" size="6">';

	while( $t_row = db_fetch_array( $t_result ) ) {
		if ( is_blank( $t_row['branch'] ) ) { continue; }
		echo '<option value="', string_attribute( $t_row['branch'] ),
			( in_array( $t_row['branch'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['branch'] ), '</option>';
	}

	echo '</select>';
}

function Source_Action_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_file_table = plugin_table( 'file' );

	$t_query = "SELECT DISTINCT( action ) FROM $t_file_table ORDER BY action ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="file_action[]" multiple="multiple" size="6">';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option value="', string_attribute( $t_row['action'] ),
			( in_array( $t_row['action'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['action'] ), '</option>';
	}

	echo '</select>';
}

function Source_Author_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_changeset_table = plugin_table( 'changeset' );
	$t_user_table = db_get_table( 'mantis_user_table' );

	$t_query = "SELECT DISTINCT( author ) FROM $t_changeset_table ORDER BY author ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="author">',
		'<option value="">[Choose]</option>';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option value="', string_attribute( $t_row['author'] ),
			( in_array( $t_row['author'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['author'] ), '</option>';
	}

	echo '</select>';
}

function Source_Username_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_changeset_table = plugin_table( 'changeset' );
	$t_user_table = db_get_table( 'mantis_user_table' );

	$t_query = "SELECT DISTINCT( c.user_id ), u.username FROM $t_changeset_table AS c
		JOIN $t_user_table AS u ON c.user_id=u.id ORDER BY u.username ASC";
	$t_result = db_query_bound( $t_query );

	echo '<select name="user_id">',
		'<option value="">[Choose]</option>';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option value="', (int) $t_row['user_id'],
			( in_array( $t_row['user_id'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['username'] ), '</option>';
	}

	echo '</select>';
}

