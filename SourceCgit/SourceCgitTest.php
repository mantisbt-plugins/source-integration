<?php

# Copyright (c) 2011 asm89
# Licensed under the MIT license

// A simple file for testing if the cgit plugin parses everything in the right way.

// define testing mode
define('testing', true);
include 'SourceCgit.php';

// Get a testpage
$testpage = file_get_contents('http://hjemli.net/git/cgit/commit/?id=d885158f6ac29e04bd14dd132331c7e3a93e7490');

$plugin = new SourceCgitPlugin();

$t_input = $plugin->clean_input( $testpage );
print_c( $plugin->commit_revision( $t_input ) );
print_c( $plugin->commit_author( $t_input ) );
print_c( $plugin->commit_parents( $t_input ) );
print_c( $plugin->commit_message( $t_input ) );
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
 * MantisSourcePlugin stub
 */
class MantisSourcePlugin {}
