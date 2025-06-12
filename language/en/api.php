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
	'RESOLVED_TOPICS_API_CONFIRM_RESOLVE_TOPIC'		=> 'Are you sure you want to resolve this topic?',
	'RESOLVED_TOPICS_API_CONFIRM_UNRESOLVE_TOPIC'	=> 'Are you sure you want to unresolve this topic?',

	'RESOLVED_TOPICS_API_TOPIC_RESOLVED'			=> 'The topic was successfully marked as resolved.',
	'RESOLVED_TOPICS_API_TOPIC_UNRESOLVE'			=> 'The topic was successfully marked as unresolved.',
	'RESOLVED_TOPICS_API_FAILED_RESOLUTION'			=> 'There was a problem updating the topic resolution data.',

	// LOGS
	'RESOLVED_TOPICS_ERROR_CSRF_FAILURE'		=> '<strong>Resolved Topics extension</strong>:<br />» The user submitted an invalid form token.',
	'RESOLVED_TOPICS_ERROR_INVALID_METHOD'		=> '<strong>Resolved Topics extension</strong>:<br />» The user accessed the API via an invalid method.',
	'RESOLVED_TOPICS_ERROR_RESOLVE_FAILURE'		=> '<strong>Resolved Topics extension</strong>:<br />» The database could not be updated with the resolution data.',
] );
