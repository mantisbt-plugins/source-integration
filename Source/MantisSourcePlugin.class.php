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

/**
 * Abstract class for simplifying creation of source control plugins.
 * @author John Reese
 */ 
abstract class MantisSourcePlugin extends MantisPlugin {
	function hooks() {
		return array(
			'EVENT_SOURCE_GET_TYPES'		=> 'get_types',

			'EVENT_SOURCE_SHOW_TYPE'		=> 'show_type',
			'EVENT_SOURCE_SHOW_CHANGESET'	=> 'show_changeset',
			'EVENT_SOURCE_SHOW_FILE'		=> 'show_file',

			'EVENT_SOURCE_URL_REPO'			=> 'url_repo',
			'EVENT_SOURCE_URL_CHANGESET'	=> 'url_changeset',
			'EVENT_SOURCE_URL_FILE'			=> 'url_file',
			'EVENT_SOURCE_URL_FILE_DIFF'	=> 'url_diff',

			'EVENT_SOURCE_UPDATE_REPO_FORM'	=> 'update_repo_form',
			'EVENT_SOURCE_UPDATE_REPO'		=> 'update_repo',

			'EVENT_SOURCE_PRECOMMIT'		=> 'precommit',
			'EVENT_SOURCE_COMMIT'			=> 'commit',

			'EVENT_SOURCE_IMPORT_REPO'		=> 'import_repo',
		);
	}

	abstract function get_types( $p_event );

	abstract function show_type( $p_event, $p_type );
	abstract function show_changeset( $p_event, $p_repo, $p_changeset);
	abstract function show_file( $p_event, $p_repo, $p_changeset, $p_file );

	abstract function url_repo( $p_event, $p_repo, $t_changeset=null );
	abstract function url_changeset( $p_event, $p_repo, $p_changeset );
	abstract function url_file( $p_event, $p_repo, $p_changeset, $p_file );
	abstract function url_diff( $p_event, $p_repo, $p_changeset, $p_file );

	function update_repo_form( $p_event, $p_repo ) {}
	function update_repo( $p_event, $p_repo ) {
		return $p_repo;
	}

	function precommit( $p_event ) {
		return null;
	}

	abstract function commit( $p_event, $p_repo, $p_data );

	function import_repo( $p_event, $p_repo ) {
		return;
	}

}

/**
 * Generic Source integration plugin to handle anything it can once
 * other plugins have passed on handling the information.
 * Most generic source control integration possible.
 * Does not check source types, and should always be the last plugin
 * to execute the event.
 */
class SourceGenericPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = lang_get( 'plugin_Source_title' );
		$this->version = lang_get( 'plugin_Source_version' );
	}

	function get_types( $p_event ) {
		return array('generic' => 'Generic');
	}

	function show_type( $p_event, $p_type ) {
		if ( 'generic' == strtolower( $p_type ) ) {
			return 'Generic';
		}

		return $p_type;
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		return $p_repo->name . ' ' . $p_repo->type . ' ' . $p_changeset->revision;
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_file->filename . ' (' . $p_changeset->revision . ')';
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
		return $p_repo->url;
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		return $p_repo->url;
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}

	function commit( $p_event, $p_repo, $p_data ) {
		return null;
	}

}
