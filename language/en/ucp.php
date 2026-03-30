<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2026 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

if ( ! defined( 'IN_PHPBB' ) ) {

	exit;

}

if ( empty( $lang ) || ! is_array( $lang ) ) {

	$lang = [];

}

$lang = array_merge( $lang, [
	'UCP_RESOLVED_TOPICS_LABEL'			=> 'Resolved topics',
	'UCP_RESOLVED_TOPICS_SEARCH_LINK'	=> 'Search topics resolved by user',
] );
