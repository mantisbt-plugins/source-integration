<?php

# Copyright (c) 2017 Damien Regad
# Licensed under the MIT license

require_once( config_get_global( 'class_path' ) . 'MantisPlugin.class.php' );

/**
 * Class MantisSourceBase
 *
 * Base class for all Source Integration Plugin classes
 */
abstract class MantisSourceBase extends MantisPlugin
{
	/**
	 * Source Integration framework version.
	 *
	 * Numbering follows Semantic Versioning. Major version increments indicate
	 * a change in the minimum required MantisBT version: 0=1.2; 1=1.3, 2=2.x.
	 * The framework version is incremented when the plugin's core files change.
	 */
	const FRAMEWORK_VERSION = '2.5.1';

	/**
	 * Minimum required MantisBT version.
	 * Used to define the default MantisCore dependency for all child plugins;
	 * VCS plugins may override this based on their individual requirements.
	 */
	const MANTIS_VERSION = '2.24.0';
}
