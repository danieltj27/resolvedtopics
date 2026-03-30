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
	'RESOLVED_TOPIC_NOTIFICATION_RESOLVED_TITLE'		=> '<strong>%s</strong> marked your post as a resolution.',
	'RESOLVED_TOPIC_NOTIFICATION_RESOLVED_REASON'		=> 'Someone marked your post as a topic resolution',

	// UCP
	'RESOLVED_TOPIC_NOTIFICATION_RESOLVED_SAMPLE'		=> 'Someone marks your post as a topic resolution',
] );
