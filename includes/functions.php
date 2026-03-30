<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2026 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\includes;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as database;
use phpbb\log\log;
use phpbb\notification\manager as notifications;
use phpbb\routing\helper;
use phpbb\user;

final class functions {

	/**
	 * @var auth
	 */
	protected $auth;

	/**
	 * @var config
	 */
	protected $config;

	/**
	 * @var driver_interface
	 */
	protected $database;

	/**
	 * @var log
	 */
	protected $log;

	/**
	 * @var notifications
	 */
	protected $notifications;

	/**
	 * @var router
	 */
	protected $router;

	/**
	 * @var user
	 */
	protected $user;

	/**
	 * Constructor
	 */
	public function __construct( auth $auth, config $config, database $database, log $log, notifications $notifications, helper $routing_helper, user $user ) {

		$this->auth = $auth;
		$this->config = $config;
		$this->database = $database;
		$this->log = $log;
		$this->notifications = $notifications;
		$this->router = $routing_helper;
		$this->user = $user;

	}

	/**
	 * Returns the name of the extensions CSRF token.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string  The name of the CSRF token.
	 */
	public function get_ext_csrf_token_name() {

		return 'resolved_topics_api';

	}

	/**
	 * Returns the route to resolve a topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string  The route used to resolve a topic.
	 */
	public function get_resolve_topic_route( $post_id ) {

		return $this->router->route( 'resolved_topics_api_resolve_topic', [
			'id'	=> (int) $post_id,
			'csrf'	=> generate_link_hash( $this->get_ext_csrf_token_name() ),
		] );

	}

	/**
	 * Returns whether the current user can resolve a topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_author The user ID of the topic author which is used to check
	 *                              against the current authenticated user to ensure that
	 *                              they match. This is because only the topic author and
	 *                              moderators with permission can resolve topics.
	 * @param integer $forum_id     The forum ID to check permissions for the current user.
	 * 
	 * @return boolean  True if the user has permission to resolve topics
	 *                  and false if they do not.
	 */
	public function can_resolve_topic( $topic_author, $forum_id ) {

		$topic_author = (int) $topic_author;
		$forum_id = (int) $forum_id;

		// Must have a valid user/forum ID and cannot be a guest.
		if ( ANONYMOUS === $topic_author || 0 === $topic_author || 0 === $forum_id ) {

			return false;

		}

		// Check the moderator based permissions.
		if ( $this->auth->acl_getf( 'm_resolve_all_topics', $forum_id ) ) {

			return true;

		}

		// Check the forum based permissions.
		if ( (int) $this->user->data[ 'user_id' ] === $topic_author && $this->auth->acl_getf( 'f_resolve_own_topics', $forum_id ) ) {

			return true;

		}

		return false;

	}

	/**
	 * Returns the post data of a resolved topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The topic ID used to query.
	 * 
	 * @return array|boolean  Array of topic data or false if the query failed.
	 */
	public function get_resolved_topic_post( $topic_id ) {

		$topic_id = (int) $topic_id;

		$result = $this->database->sql_query(
			'SELECT p.*
				FROM ' . TOPICS_TABLE . ' AS t, ' . POSTS_TABLE . ' AS p
				WHERE t.topic_id = ' . $this->database->sql_escape( $topic_id ) . ' AND p.post_id = t.topic_resolved_post_id'
			);

		$post = $this->database->sql_fetchrow( $result );
		$this->database->sql_freeresult( $result );

		return $post;

	}

	/**
	 * Returns the post data of a resolved topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The topic ID used to query.
	 * 
	 * @return array|boolean  Array of topic data or false if the query failed.
	 */
	public function get_resolved_topic_user( $topic_id ) {

		$topic_id = (int) $topic_id;

		$result = $this->database->sql_query(
			'SELECT u.*
				FROM ' . USERS_TABLE . ' AS u, ' . TOPICS_TABLE . ' AS t
				WHERE u.user_id = t.topic_resolved_by_user_id AND t.topic_id = ' . $this->database->sql_escape( $topic_id )
			);

		$user = $this->database->sql_fetchrow( $result );
		$this->database->sql_freeresult( $result );

		return $user;

	}

	/**
	 * Resolve a topic by the post ID.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $post_id The ID of the post that resolves the selected topic.
	 * 
	 * @return boolean  True on success or false on failure.
	 */
	public function resolve_topic_by_post_id( $post_id ) {

		$post_id = (int) $post_id;

		$user_id = (int) $this->user->data[ 'user_id' ];

		$result = $this->database->sql_query(
			'SELECT t.*, p.poster_id, p.post_visibility
				FROM ' . TOPICS_TABLE . ' AS t, ' . POSTS_TABLE . ' AS p
				WHERE p.post_id = ' . $this->database->sql_escape( $post_id ) . ' AND t.topic_id = p.topic_id'
			);

		$topic = $this->database->sql_fetchrow( $result );
		$this->database->sql_freeresult( $result );

		if ( false === $topic ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_INVALID_TOPIC', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		// Save post data that was fetched and clean the $topic variable.
		$poster_id = (int) $topic[ 'poster_id' ];
		$post_visibility = (int) $topic[ 'post_visibility' ];

		unset( $topic[ 'poster_id' ] );
		unset( $topic[ 'post_visibility' ] );

		if ( ! $this->can_resolve_topic( $topic[ 'topic_poster' ], $topic[ 'forum_id' ] ) ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_NO_PERMISSION', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		/**
		 * Set the post ID to `0` if this post is already marked as
		 * the resolution for this topic (toggle behaviour).
		 */
		if ( $post_id === (int) $topic[ 'topic_resolved_post_id' ] ) {

			$post_id = 0;

		}

		// Stop soft deleted and unapproved posts from being marked as a resolution.
		if ( 0 !== $post_id && 1 !== $post_visibility ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_POST_HIDDEN', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		// Don't allow the first post to be marked as the reoslution.
		if ( $post_id === (int) $topic[ 'topic_first_post_id' ] ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_FIRST_POST', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		$this->database->sql_query(
			'UPDATE ' . TOPICS_TABLE . '
				SET ' . $this->database->sql_build_array( 'UPDATE', [
					'topic_resolved_post_id' => $this->database->sql_escape( $post_id ),
					'topic_resolved_poster_id' => $this->database->sql_escape( $poster_id ),
					'topic_resolved_by_user_id' => $this->database->sql_escape( $user_id ),
				] ) . '
				WHERE topic_id = ' . $this->database->sql_escape( $topic[ 'topic_id' ] )
		);

		if ( 1 !== $this->database->sql_affectedrows() ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_QUERY_FAILED', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		/**
		 * Only notify the post author when the post is being marked as
		 * the topic resolution and the person marking the post as the
		 * resolution is not the author of said post.
		 */ 
		if ( 0 !== $post_id ) {

			$this->notifications->add_notifications( 'danieltj.resolvedtopics.notification.type.resolved', [
				'item_id'			=> $this->create_notification_item_id(),
				'post_id'			=> $post_id,
				'poster_id'			=> $poster_id, // The author of the post.
				'user_id'			=> $user_id, // The user changing the topic resolution.
				'topic_title'		=> $topic[ 'topic_title' ],
			] );

		}

		$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_TOPIC_RESOLVED', time(), [
			'reportee_id' => $user_id,
			'topic_id' => $topic[ 'topic_id' ],
		] );

		return true;

	}

	/**
	 * Unresolve a topic by the post ID.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The ID of the topic that is unresolved.
	 * 
	 * @return boolean  True on success or false on failure.
	 */
	public function unresolve_topic_by_id( $topic_id ) {

		$topic_id = (int) $topic_id;

		$user_id = (int) $this->user->data[ 'user_id' ];

		$this->database->sql_query(
			'UPDATE ' . TOPICS_TABLE . '
				SET ' . $this->database->sql_build_array( 'UPDATE', [
					'topic_resolved_post_id' => 0,
					'topic_resolved_poster_id' => 0,
					'topic_resolved_by_user_id' => $this->database->sql_escape( $user_id ),
				] ) . '
				WHERE topic_id = ' . $this->database->sql_escape( $topic_id )
		);

		if ( 1 !== $this->database->sql_affectedrows() ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_QUERY_FAILED', time(), [
				'reportee_id' => $user_id,
				'topic_id' => $topic_id,
			] );

			return false;

		}

		$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_TOPIC_UNRESOLVED', time(), [
			'reportee_id' => $user_id,
			'topic_id' => $topic_id,
		] );

		return true;

	}

	/**
	 * Return a unique identifier for notifications.
	 * 
	 * @since 1.0.0
	 * 
	 * @return integer  An integer to use as an item_id for notifications.
	 */
	public function create_notification_item_id() {

		$item_id = (int) $this->config[ 'resolved_topics_notify_item_id' ];

		$item_id += 1;

		$this->config->set( 'resolved_topics_notify_item_id', $item_id );

		return $item_id;

	}

}
