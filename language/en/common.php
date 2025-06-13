<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2025 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

if ( ! defined( 'IN_PHPBB' ) ) {

	exit;

}

if ( empty( $lang ) || ! is_array( $lang ) ) {

	$lang = [];

}

$lang = array_merge( $lang, [
	'RESOLVE_TOPIC'		=> 'Resolve topic',
	'UNRESOLVE_TOPIC'	=> 'Unresolve topic',

	'RESOLVED_TOPIC_MESSAGE'	=> 'Topic has been resolved',
	'RESOLVED_POST_MESSAGE'		=> '%s marked this topic as resolved with this post.',
] );
