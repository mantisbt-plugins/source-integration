#!/usr/bin/php
<?php
/**
 * Helper script to increase Source Integration versions
 *
 * Command-line options:
 *
 * -v [version] Set Framework version (in Source/MantisSourceBase.class.php)
 *              and generate a bump commit; if version is not specified,
 *              or the framework is already at that version, the script will
 *              just print the commit message.
 *
 * -t           Create a signed tag for the new version (based on framework
 *              version, or the version specified in -v option), listing all
 *              included VCS-specific plugins names and versions.
 */

// Path to MantisBT root, relative to the Source Integration Plugin's git root
// Change this based on your dev environment's setting
$g_mantis_root = '../../mantisbt';


// ---------------------------------------------------------------------------
// Main program
//

// Process command-line options
$t_options = getopt( 'v::th' );
$t_bump_version = array_key_exists( 'v', $t_options );
$t_create_tag = array_key_exists( 't', $t_options );
if( array_key_exists( 'h', $t_options ) || !$t_bump_version && !$t_create_tag ) {
	print_help();
	exit(0);
}

// Change to framework's root dir
chdir( dirname( __DIR__ ) );

// Load plugins and get their version numbers
foreach( new DirectoryIterator( getcwd() ) as $t_file ) {
	$t_name = $t_file->getFilename();
	if( $t_file->isDir() && strpos( $t_name, 'Source' ) === 0 ) {
		if( plugin_load_class( $t_name ) ) {
			$g_plugins[$t_name] = plugin_get_version( $t_name );
		} else {
			echo "ERROR: plugin '$t_name' could not be loaded";
		}
	}
}
ksort( $g_plugins );

$t_framework_version = array_shift( $g_plugins );

// Set version and create bump commit
if( $t_bump_version ) {
    bump_version_and_commit( $t_options['v'], $t_framework_version );
}

// Create Tag
if( $t_create_tag ) {
	create_tag( $t_framework_version );
}


// ---------------------------------------------------------------------------
// Helper functions
//

/**
 * Prints command-line help
 */
function print_help() {
	echo basename( $argv[0] ) .  "[-v version] [-t] [-h]\n\n", <<<EOF
Helper script to increase Source Integration versions

  -v [version] Set version if specified, and create bump commit
               If no version given, prints commit message
  -t           Create signed tag
  -h           Help


EOF;
}

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
 * Set the Framework version in MantisSourceBase class and create bump commit.
 *
 * @param string $p_version
 * @param string $p_framework_version
 * @return void
 */
function bump_version_and_commit( $p_version, &$p_framework_version ) {
    global $g_plugins;
	$t_filename = 'Source/MantisSourceBase.class.php';

	// Check if framework version needs to be updated
	if( $p_version ) {
		echo "New version '$p_version' specified; ";
		if( $p_version == $p_framework_version ) {
			echo "framework already at";
			$p_version = false;
		} else {
			echo "bump from";
		}
	} else {
		echo "Unspecified version bump; framework at";
	}
	echo " '$p_framework_version'\n";

	// Update framework version
	if( $p_version ) {
		echo "Update version in $t_filename\n";
		exec( 'sed -r -i "s/(const FRAMEWORK_VERSION = \').*(\';)/\1'
			. $p_version . '\2/" ' . $t_filename
		);
	}

	// Generate commit message
	$t_message = "Bump version to $p_version\n\n";
	$t_message .= "VCS plugins changes:\n";
	foreach( get_changed_plugins() as $t_plugin ) {
		$t_message .= "- $t_plugin " . $g_plugins[$t_plugin] . "\n";
	}

	// Commit
	if( $p_version ) {
		echo "Committing version bump\n";
		exec( 'git add -- ' . $t_filename );
		exec( "git commit -m '$t_message' 2>&1", $t_output, $t_result );
		foreach( $t_output as $t_line ) {
			echo $t_line . "\n";
		}
		if( $t_result ) {
			echo "ERROR: Commit failed\n";
			exit( 1 );
		}
		$p_framework_version = $p_version;
	} else {
		echo "Commit message:\n";
		echo "------------------\n";
		echo $t_message . "\n";
		echo "------------------\n";
	}
}

/**
 * Retrieves the list of changed plugins since previous tag
 * @return array
 */
function get_changed_plugins() {
	$t_previous_tag = exec( "git describe --abbrev=0" );

	echo "Retrieving changed plugins since previous tag '$t_previous_tag'\n";

	// List all changed VCS plugins (exclude Source framework)
	exec(
		"git diff --name-only $t_previous_tag | cut -d'/' -f1 | sort -u | grep '^Source.'",
		$t_changed_plugins
	);

	return $t_changed_plugins;
}

/**
 * Create a signed tag for the release
 * @param $p_framework_version
 * @return void
 */
function create_tag( $p_framework_version ) {
	global $g_plugins;

	// Generate message
	$t_message = "Release $p_framework_version\n\n";
	$t_message .= "Includes the following VCS-specific plugins:\n";
	foreach( $g_plugins as $t_plugin => $t_version ) {
		$t_message .= "- $t_plugin $t_version\n";
	}

	// Create Tag
	$t_tag = 'v' . $p_framework_version;
	exec( "git tag -s $t_tag -m '$t_message' 2>&1", $t_output, $t_result );
	if( $t_result ) {
		echo "ERROR: Tag creation failed\n";
		foreach( $t_output as $t_line ) {
			echo $t_line . "\n";
		}
		exit( 1 );
	}
	echo "Tag $t_tag created\n";
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
