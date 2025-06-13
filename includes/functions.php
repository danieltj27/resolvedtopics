<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2025 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\includes;

use phpbb\auth\auth;
use phpbb\db\driver\driver_interface as database;
use phpbb\routing\helper;
use phpbb\user;

final class functions {

	/**
	 * @var auth
	 */
	protected $auth;

	/**
	 * @var driver_interface
	 */
	protected $database;

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
	public function __construct( auth $auth, database $database, helper $routing_helper, user $user ) {

		$this->auth = $auth;
		$this->database = $database;
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
	 * @return string  The route to resolve a topic.
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
	 * @param integer $topic_id The ID of the topic to check permissions for.
	 * @param array   $data     An array containing topic data. If the 'forum_id' and 'topic_poster'
	 *                          keys exist, the values will be used instead of querying the database
	 *                          which will save on queries if this is called inside a loop.
	 * 
	 * @return boolean  True if the user can resolve the topic, false if they cannot.
	 */
	public function can_resolve_topic( $topic_id, $data = [] ) {

		$topic_id = (int) $topic_id;

		if ( is_array( $data ) && ! empty( $data ) && isset( $data[ 'forum_id' ] ) && isset( $data[ 'topic_poster' ] ) ) {

			$forum_id = (int) $data[ 'forum_id' ];
			$topic_starter = (int) $data[ 'topic_poster' ];

		} else {

			$result = $this->database->sql_query(
				'SELECT *
					FROM ' . TOPICS_TABLE . '
					WHERE topic_id = ' . $this->database->sql_escape( $topic_id )
				);

			$topic = $this->database->sql_fetchrow( $result );
			$this->database->sql_freeresult( $result );

			if ( false === $topic ) {

				return false;

			}

			$forum_id = (int) $topic[ 'forum_id' ];
			$topic_starter = (int) $topic[ 'topic_poster' ];

		}

		// Forum moderators only.
		if ( $this->auth->acl_getf( 'm_resolve_all_topics', $forum_id ) ) {

			return true;

		}

		/**
		 * Check that the user is not a guest, they started the topic
		 * and that they have the forum based resolve permission.
		 */
		if (
			ANONYMOUS !== $topic_starter &&
			$this->user->data[ 'user_id' ] === $topic_starter &&
			$this->auth->acl_getf( 'f_resolve_own_topics', $forum_id )
		) {

			return true;

		}

		return false;

	}

	/**
	 * Returns the post data of a resolved topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The ID of the topic to check.
	 * 
	 * @return array|boolean  Returns the array of post data for the post that is
	 *                        marked as the resolution or false if none is set.
	 */
	public function is_topic_resolved( $topic_id ) {

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
	 * Returns the user data that resolved the specified topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The ID of the resolved topic.
	 * 
	 * @return array|boolean  An array of user data or false if not found.
	 */
	public function get_resolved_topic_user( $topic_id ) {

		$topic_id = (int) $topic_id;

		$result = $this->database->sql_query(
			'SELECT u.*
				FROM ' . USERS_TABLE . ' AS u, ' . TOPICS_TABLE . ' AS t
				WHERE u.user_id = t.topic_resolved_user_id AND t.topic_id = ' . $this->database->sql_escape( $topic_id )
			);

		$user = $this->database->sql_fetchrow( $result );
		$this->database->sql_freeresult( $result );

		return $user;

	}

	/**
	 * Resolves a topic based on a related post ID.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $post_id The ID of the post to mark as resolved.
	 * 
	 * @return boolean  True if successful and false on failure.
	 */
	public function resolve_topic_by_post_id( $post_id ) {

		$post_id = (int) $post_id;

		$user_id = (int) $this->user->data[ 'user_id' ];

		$result = $this->database->sql_query(
			'SELECT t.*, p.post_visibility
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

		// Set the value of the post visibility status.
		$post_visibility = (int) $topic[ 'post_visibility' ];
		unset( $topic[ 'post_visibility' ] );

		if ( ! $this->can_resolve_topic( $topic[ 'topic_id' ], $topic ) ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_NO_PERMISSION', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		/**
		 * Check if the specified post ID matches the ID of the current
		 * resolved post. If it does then the topic should be 'unresolved'
		 * and the `topic_resolved_post_id` column set to 0. If it doesn't
		 * match then it's a different post being set as resolved so continue.
		 */
		$resolved_id = ( $post_id === (int) $topic[ 'topic_resolved_post_id' ] ) ? 0 : $post_id;

		/**
		 * Only allow visible posts to be set as the resolution for a topic
		 * where the value is 1. Values of 0 mean not approved yet and values
		 * of 2 means soft deleted.
		 */
		if ( 0 !== $resolved_id && 1 !== $post_visibility ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_FUNC_POST_HIDDEN', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return false;

		}

		$this->database->sql_query(
			'UPDATE ' . TOPICS_TABLE . '
				SET ' . $this->database->sql_build_array( 'UPDATE', [
					'topic_resolved_post_id' => $this->database->sql_escape( $resolved_id ),
					'topic_resolved_user_id' => $this->database->sql_escape( $user_id ),
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

		return true;

	}

	/**
	 * Unresolves a topic by the specified topic ID.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $topic_id The ID of the topic to unresolve.
	 * 
	 * @return boolean  True if successful and false on failure.
	 */
	public function unresolve_topic_by_id( $topic_id ) {

		$topic_id = (int) $topic_id;

		$user_id = (int) $this->user->data[ 'user_id' ];

		$this->database->sql_query(
			'UPDATE ' . TOPICS_TABLE . '
				SET ' . $this->database->sql_build_array( 'UPDATE', [
					'topic_resolved_post_id' => 0,
					'topic_resolved_user_id' => $this->database->sql_escape( $user_id ),
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

		return true;

	}

}
