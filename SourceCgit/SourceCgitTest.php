<?php

# Copyright (c) 2011 asm89
# Licensed under the MIT license

/**
 * This simple test fetches the specified url and outputs the parsed content.
 * This output can be compared with the content of the webpage to verify that 
 * the cgit parser works.
 *
 * Usage: php SourceCgitTest.php
 */

// Url pointing to a commit on Cgit (default is a commit of the cgit project)
$url = 'http://hjemli.net/git/cgit/commit/?id=d885158f6ac29e04bd14dd132331c7e3a93e7490';

// Define testing mode
define('testing', true);
include 'SourceCgit.php';

// Get the testpage
$testpage = file_get_contents($url);

// Initialize the plugin
$plugin = new SourceCgitPlugin();

// Sanatize the input
$t_input = $plugin->clean_input( $testpage );

print_n( 'Revision:' );
print_c( $plugin->commit_revision( $t_input ) );
print_n();

print_n( 'Author/commiter info:' );
print_c( $plugin->commit_author( $t_input ) );
print_n();

print_n( 'Parent commits:' );
print_c( $plugin->commit_parents( $t_input ) );
print_n();

print_n( 'Commit message:' );
print_c( $plugin->commit_message( $t_input ) );
print_n();

print_n( 'Committed files:' );
print_c( $plugin->commit_files( $t_input ) );

/**
 * Print the data and a newline.
 *
 * @param mixed $data 
 */
function print_c($data) {
    print_r($data);
    echo "\n";
}

/**
 * Print the text and a newline.
 *
 * @param string $text
 */
function print_n($text = '') {
    echo $text . "\n";
}

/**
 * MantisSourcePlugin stub
 */
class MantisSourcePlugin {}
