#!/usr/bin/php
<?php
/**
 * Helper script to increase Source Integration versions
 *
 * Will retrieve all plugin versions numbers, generate a message and create a
 * tag for the new version (based on Source framework version number), listing
 * individual, VCS-specific plugins names and versions.
 */

// Path to MantisBT root, relative to the Source Integration Plugin's git root
// Change this based on your dev environment's setting
$g_mantis_root = '../../mantisbt';


// ---------------------------------------------------------------------------
// Main program
//

// Change to framework's root dir
chdir( dirname( __DIR__ ) );

// Load plugins and get their version numbers
foreach( new DirectoryIterator( getcwd() ) as $t_file ) {
	$t_name = $t_file->getFilename();
	if( $t_file->isDir() && strpos( $t_name, 'Source' ) === 0 ) {
		if( plugin_load_class( $t_name ) ) {
			$t_plugins[$t_name] = plugin_get_version( $t_name );
		} else {
			echo "ERROR: plugin '$t_name' could not be loaded";
		}
	}
}
ksort( $t_plugins );

// Generate message
$t_framework_version = array_shift( $t_plugins );
$t_tag = 'v' . $t_framework_version;
$t_message = "Release $t_framework_version\n\n";
$t_message .= "Includes the following VCS-specific plugins:\n";
foreach( $t_plugins as $t_plugin => $t_version ) {
	$t_message .= "- $t_plugin $t_version\n";
}

// Create Tag
exec( "git tag -s $t_tag -m '$t_message' 2>&1", $t_output, $t_result );
if( $t_result ) {
	echo "ERROR: Tag creation failed\n";
	foreach( $t_output as $t_line ) {
		echo $t_line . "\n";
	}
	exit( 1 );
}
echo "Tag $t_tag created\n";


// ---------------------------------------------------------------------------
// Helper functions
//

/**
 * Load VCS plugin base
 * @param string $p_basename
 * @return bool
 */
function plugin_load_class( $p_basename ) {
	$t_path = $p_basename . '/' . $p_basename . '.php';

	// Suppressing errors since we don't need to actually run the classes
	$t_result = @include_once $t_path;

	if( !$t_result ) {
		echo "Failed to load $p_basename\n";
		return false;
	}
	return $t_result;
}

/**
 * Returns the given plugin's version number
 * @param string $p_basename
 * @return string
 */
function plugin_get_version( $p_basename ) {
	$t_class = $p_basename . 'Plugin';
	return $t_class::PLUGIN_VERSION;
}

/**
 * Return MantisBT root dir
 * @return string
 */
function get_mantis_root() {
	global $g_mantis_root;
	$g_mantis_root = rtrim( $g_mantis_root, '/' ) . '/';

	if( file_exists( $g_mantis_root . 'core.php' ) ) {
		return $g_mantis_root;
	}

	die( "ERROR: MantisBT not found in '$g_mantis_root'\n" );
}

/**
 * Fake Mantis core functions.
 * - config_get()
 * - config_get_global()
 * - require_api()
 * - require_lib
 * These are required so that the Source Integration classes can be initialized
 * without actually loading the Mantis core.
 */
function config_get( $p_string ) {
	if( $p_string == 'plugin_path' ) {
		return getcwd() . '/';
	} else {
		return config_get_global( $p_string );
	}
}

function config_get_global( $p_string ) {
	$t_root = get_mantis_root() . 'core/';

	switch( $p_string ) {
		case 'core_path':
			return $t_root;
		case 'class_path':
			return $t_root . 'classes/';
	}
	return null;
}

function require_api( $p_path ) {
	return;
}

function require_lib( $p_path ) {
	return;
}
