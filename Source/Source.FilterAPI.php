<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

define( 'SOURCE_ANY', 0 );
define( 'SOURCE_NONE', 1 );

function Source_Twomap( $func, $list ) {
    $new_list2 = array();
    $new_list = array();

    foreach( $list as $key => $item ) {
        list( $new_list[$key], $new_list2[$key] ) = call_user_func( $func, $key, $item );
    }

    return array( $new_list, $new_list2 );
}

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
			$this->filters['c.user_id'] = new SourceFilterOption();
			$this->filters['c.ported'] = new SourceFilterOption();

			$this->filters['r.id'] = new SourceFilterOption();
			$this->filters['r.type'] = new SourceFilterOption();

			$this->filters['b.bug_id'] = new SourceFilterOption();

			$this->filters['f.filename'] = new SourceFilterOption();
			$this->filters['f.revision'] = new SourceFilterOption();
			$this->filters['f.action'] = new SourceFilterOption();

			$this->filters['date_start'] = new SourceFilterOption();
			$this->filters['date_end'] = new SourceFilterOption();
		}
	}

	/**
	 * Retrieves the data based on current filter
	 * @param int $p_page page to display; defaults to 1, use null for all pages
	 * @param int $p_limit number of records per page, defaults to 25
	 * @return array containing list of changesets and number of records
	 */
	function find( $p_page=1, $p_limit=25 ) {
		list( $t_filters, $t_filter_params ) = Source_Twomap( 'Source_Process_FilterOption', $this->filters );
		list( $t_query_tail, $t_order, $t_params ) = Source_Process_Filters( $t_filters, $t_filter_params );

		$t_count_query = "SELECT COUNT(c.id) $t_query_tail";
		$t_full_query = "SELECT DISTINCT( c.id ), c.* $t_query_tail $t_order";

		$t_count = db_result( db_query( $t_count_query, $t_params ) );

		if ( is_null( $p_page ) ) {
			$t_result = db_query( $t_full_query, $t_params );
		} else {
			$t_result = db_query( $t_full_query, $t_params, $p_limit, ( $p_page - 1 ) * $p_limit );
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

/**
 * Processes the filter criteria to define the query join/where clause, order by
 * clause and parameters
 * @param string $p_filters
 * @param array $p_filter_params
 * @return array query join+where clause, order clause and parameters
 */
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

	return array( "$t_join $t_where", $t_order, $t_params );
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

	# Date searching
	if ( $key == 'date_start' && !is_null( $value ) ) {
		$wc = db_param();
		$sql = "c.timestamp >= $wc";

		return array( $sql, $value );
	}
	if ( $key == 'date_end' && !is_null( $value ) ) {
		$wc = db_param();
		$sql = "c.timestamp <= $wc";

		return array( $sql, $value );
	}

	# Revision Searching
	if ( $key == 'f.revision' && !is_null( $value ) )	{
		$wc1 = db_param();
		$wc2 = db_param();
		$value = "%$value%";
		$sql = "( c.revision LIKE $wc1 OR f.revision LIKE $wc2 )";

		return array( $sql, array( $value, $value ) );
	}

	# Full-text searching
	if ( in_array( $key, array( 'c.author', 'c.message', 'f.filename' ) ) ) {

		if ( !is_array( $value ) ) {
			$value = explode( ' ', $value );
		}

		$wc = array_map( 'db_param', $value );
		$wc = array_map( function ( $item ) use ( $key ) { return "$key LIKE $item"; }, $wc);
		$value = array_map( function( $item ) { return "%$item%"; }, $value );

		$sql = '(' . implode( ' OR ', $wc ) . ')';

		return array( $sql, $value );
	}

	# Porting status
	if ( $key == 'c.ported' ) {
		$clauses = array();

		foreach( $value as $ported ) {
			# ported
			if ( $ported == "-2" ) {
				$clauses[] = "( $key != '' AND $key != '0' )";
			}
			# pending
			if ( $ported == "-1" ) {
				$clauses[] = "$key = ''";
			}
			# n/a
			if ( $ported == "0" ) {
				$clauses[] = "$key = '0'";
			}
		}

		if ( SOURCE_ANY == $how ) {
			if ( count( $clauses ) > 0 ) {
				return array( '(' . implode( ' OR ', $clauses ) . ')', array() );
			} else {
				return array( null, null );
			}
		} else {
			if ( count( $clauses ) > 0 ) {
				return array( 'NOT (' . implode( ' OR ', $clauses ) . ')', array() );
			} else {
				return array( null, null );
			}
		}
	}

	# Standard values
	if ( is_array( $value ) ) {
		$wc = array_map( 'db_param', $value );

		$count = count( $value );
		if ( $count > 1 ) {
			if ( SOURCE_ANY == $how ) {
				$sql = $key . ' IN (' . implode( ',', $wc ) . ')';
			} else {
				$sql = $key . ' NOT IN (' . implode( ',', $wc ) . ')';
			}
		} elseif ( $count == 1 ) {
			$wc = $wc[0];

			if ( SOURCE_ANY == $how ) {
				$sql = "$key = $wc";
			} else {
				$sql = "$key != $wc";
			}
		}
	} else {
		$wc = db_param();

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
	$f_repo_id = Source_FilterOption_Permalink( 'repo_id', true, 'int' );
	$f_branch = Source_FilterOption_Permalink( 'branch', true );
	$f_file_action = Source_FilterOption_Permalink( 'file_action', true );
	$f_ported = Source_FilterOption_Permalink( 'ported', true );

	$f_revision = Source_FilterOption_Permalink( 'revision' );
	$f_author = Source_FilterOption_Permalink( 'author' );
	$f_user_id = Source_FilterOption_Permalink( 'user_id', false, 'int' );
	$f_bug_id = Source_FilterOption_Permalink( 'bug_id', false, 'int' );


	$f_filename = Source_FilterOption_Permalink( 'filename' );
	$f_message = Source_FilterOption_Permalink( 'message' );

	$f_date_start = Source_FilterOption_Permalink( 'date_start', false, 'date' );
	$f_date_end = Source_FilterOption_Permalink( 'date_end', false, 'date' );

	# Get permalink
	$t_permalink = Source_FilterOption_Permalink();

	# Create filter
	$t_filter = new SourceFilter();

	$t_filter->filters['c.branch']->value = $f_branch;
	$t_filter->filters['c.message']->value = $f_message;
	$t_filter->filters['c.author']->value = $f_author;
	$t_filter->filters['c.user_id']->value = $f_user_id;
	$t_filter->filters['c.ported']->value = $f_ported;

	$t_filter->filters['r.id']->value = $f_repo_id;
	$t_filter->filters['r.type']->value = $f_repo_type;

	$t_filter->filters['b.bug_id']->value = ( !is_blank( $f_bug_id ) ? explode( '[ ,]', $f_bug_id ) : array() );

	$t_filter->filters['f.filename']->value = $f_filename;
	$t_filter->filters['f.revision']->value = $f_revision;
	$t_filter->filters['f.action']->value = $f_file_action;

	$t_filter->filters['date_start']->value = $f_date_start;
	$t_filter->filters['date_end']->value = $f_date_end;

	return array( $t_filter, $t_permalink );
}

/**
 * Validate and return a full date/time string.
 * @param string $p_string     The timestamp to validate
 * @param bool   $p_end_of_day If true, and the trailing bits of the time
 *                             are zero or not defined, they will be set to
 *                             "end of day" (i.e. 11:00 becomes 11:59:59)
 * @return null|string Null if date is invalid
 */
function Source_Date_Validate( $p_string, $p_end_of_day=false ) {
	$t_date = gpc_get_string( $p_string, null );

	if( $t_date === null ) {
		return null;
	}
	try {
		$t_date = new DateTime( $t_date );
	}
	catch( Exception $e ) {
		return null;
	}
	if( $t_date->format( 'Y' ) < 1970 ) {
		return null;
	}

	# Upper boundary timestamp processing
	if( $p_end_of_day ) {
		$t_time = date_parse( $t_date->format( 'H:i:s' ) );
		$t_sec = $t_time['second'];
		$t_min = $t_time['minute'];
		$t_hour = $t_time['hour'];

		if( $t_sec == 0 ) {
			$t_sec = 59;
			if( $t_min == 0 ) {
				$t_min = 59;
				if( $t_hour == 0 ) {
					$t_hour = 23;
				}
			}
		}
		$t_date->setTime( $t_hour, $t_min, $t_sec );
	}

	return $t_date->format( 'Y-m-d H:i:s' );
}

function Source_FilterOption_Permalink( $p_string=null, $p_array=false, $p_type='string' ) {
	static $s_permalink = '';

	if ( is_null( $p_string ) ) {
		$t_string = $s_permalink;
		$s_permalink = '';
		return $t_string;
	}

	if ( $p_array ) {
		if( $p_type == 'int') {
			$t_input = gpc_get_int_array( $p_string, array() );
		} else {
			$t_input = gpc_get_string_array( $p_string, array() );
		}
		$t_input_clean = array();

		if ( is_array( $t_input ) && count( $t_input ) > 0 ) {
			foreach( $t_input as $t_value ) {
				if ( !is_blank( $t_value ) ) {
					$t_input_clean[] = $t_value;
					$s_permalink .= "&${p_string}[]=$t_value";
				}
			}
		}

	} else {
		switch( $p_type ) {
			case 'int':
				$t_input_clean = gpc_get_int( $p_string, 0 );
				if( $t_input_clean == 0) {
					$t_input_clean = null;
				}
				break;
			case 'date':
				$t_input_clean = Source_Date_Validate( $p_string, $p_string == 'date_end' );
				break;
			default:
				$t_input_clean = gpc_get_string( $p_string, null );
		}

		if ( !is_blank( $t_input_clean ) ) {
			$s_permalink .= "&$p_string=$t_input_clean";
		}
	}

	return $t_input_clean;
}

### Search filter printing

function Source_Repo_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	$t_repo_table = plugin_table( 'repository' );

	$t_query = "SELECT id,name,type FROM $t_repo_table ORDER BY name ASC";
	$t_result = db_query( $t_query );

	echo '<select name="repo_id[]" class="SourceRepo form-control" multiple="multiple" size="6">',
		'<option class="SourceAny" value="">', plugin_lang_get( 'select_any' ), '</option>';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option class="SourceType', string_attribute( $t_row['type'] ), '" value="', (int)$t_row['id'],
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
	$t_result = db_query( $t_query );

	echo '<select name="repo_type[]" class="SourceType form-control" multiple="multiple" size="6">',
		'<option class="SourceAny" value="">', plugin_lang_get( 'select_any' ), '</option>';

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

	$t_query = "SELECT DISTINCT( branch ), repo_id FROM $t_changeset_table ORDER BY branch ASC";
	$t_result = db_query( $t_query );

	echo '<select name="branch[]" class="SourceBranch form-control" multiple="multiple" size="6">',
		'<option class="SourceAny" value="">', plugin_lang_get( 'select_any' ), '</option>';

	while( $t_row = db_fetch_array( $t_result ) ) {
		if ( is_blank( $t_row['branch'] ) ) { continue; }
		echo '<option class="SourceRepo', string_attribute( $t_row['repo_id'] ), '" value="', string_attribute( $t_row['branch'] ),
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
	$t_result = db_query( $t_query );

	echo '<select name="file_action[]" class="form-control" multiple="multiple" size="6">',
		'<option value="">', plugin_lang_get( 'select_any' ), '</option>';

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

	$t_query = "SELECT DISTINCT( author ) FROM $t_changeset_table ORDER BY author ASC";
	$t_result = db_query( $t_query );

	echo '<select name="author">',
		'<option value="">', plugin_lang_get( 'select_any' ), '</option>';

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
	$t_user_table = db_get_table( 'user' );

	$t_query = "SELECT DISTINCT( c.user_id ), u.username FROM $t_changeset_table AS c
		JOIN $t_user_table AS u ON c.user_id=u.id ORDER BY u.username ASC";
	$t_result = db_query( $t_query );

	echo '<select name="user_id">',
		'<option value="">', plugin_lang_get( 'select_any' ), '</option>';

	while( $t_row = db_fetch_array( $t_result ) ) {
		echo '<option value="', (int) $t_row['user_id'],
			( in_array( $t_row['user_id'], $t_selected ) ? '" selected="selected">' : '">' ),
			string_display( $t_row['username'] ), '</option>';
	}

	echo '</select>';
}

function Source_Ported_Select( $p_selected=null ) {
	if ( !is_array( $p_selected ) ) {
		$t_selected = array( $p_selected );
	} else {
		$t_selected = $p_selected;
	}

	echo '<select name="ported[]" multiple="multiple">',
		'<option value="">', plugin_lang_get( 'select_any' ), '</option>',
		'<option value="-1">', plugin_lang_get( 'pending' ), '</option>',
		'<option value="0">', plugin_lang_get( 'na' ), '</option>',
		'<option value="-2">', plugin_lang_get( 'ported' ), '</option>',
		'</select>';
}

/**
 * Print a date entry field with selector and default value set
 * @param string $p_name     Name of the field
 * @param string $p_default  Default date value
 */
function Source_Date_Select( $p_name, $p_default = null ) {
	static $s_timestamp_min = null;

	$t_format = config_get( 'normal_date_format' );

	switch( $p_default ) {
		case 'start':
			if( $s_timestamp_min === null ) {
				# Retrieve earliest changeset's timestamp
				$t_query = 'SELECT MIN(timestamp) FROM ' . plugin_table( 'changeset'
					);
				$s_timestamp_min = db_result( db_query( $t_query ) );
				# If there are no changesets in the table, $t_min will be null,
				# so timestamp will be set to current time
				# Set the day to Jan 1st and clear the time
				$t_timestamp = new DateTime( $s_timestamp_min );
				$t_timestamp->setDate( $t_timestamp->format( 'Y' ), 1, 1 );
				$t_timestamp->setTime( 0, 0 );
				$s_timestamp_min = $t_timestamp;
			} else {
				$t_timestamp = $s_timestamp_min;
			}
			break;
		case 'now':
			$t_timestamp = new DateTime();
			break;
		default:
			$t_timestamp = new DateTime( $p_default );
	}

?>
	<input type="text" name="<?php echo $p_name; ?>"
		class="datetimepicker input-sm"
		data-picker-locale="<?php echo lang_get_current_datetime_locale() ?>"
		data-picker-format="<?php echo config_get( 'datetime_picker_format' ) ?>"
		size="16" value="<?php echo $t_timestamp->format( $t_format ); ?>"
	/>
	<i class="fa fa-calendar fa-xlg datetimepicker"></i>
<?php
}
