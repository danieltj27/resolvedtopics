<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2026 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\event;

use phpbb\auth\auth;
use phpbb\language\language;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use danieltj\resolvedtopics\includes\functions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface {

	/**
	 * @var auth
	 */
	protected $auth;

	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @var request
	 */
	protected $request;

	/**
	 * @var template
	 */
	protected $template;

	/**
	 * @var user
	 */
	protected $user;

	/**
	 * @var functions
	 */
	protected $functions;

	/**
	 * Constructor
	 */
	public function __construct( auth $auth, language $language, request $request, template $template, user $user, functions $functions ) {

		$this->auth = $auth;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->functions = $functions;

	}

	/**
	 * Register Events
	 */
	static public function getSubscribedEvents() {

		return [
			'core.user_setup_after'									=> 'add_languages',
			'core.permissions'										=> 'add_permissions',
			'core.page_header'										=> 'add_template_vars',
			'core.memberlist_modify_view_profile_template_vars'		=> 'update_memberlist_template_vars',
			'core.search_get_posts_data'							=> 'update_search_sql_query',
			'core.viewforum_modify_topicrow'						=> 'update_topic_template_vars',
			'core.viewtopic_assign_template_vars_before'			=> 'add_topic_template_vars',
			'core.viewtopic_modify_post_row'						=> 'update_topic_post_row',
		];

	}

	/**
	 * Add Languages
	 */
	public function add_languages() {

		$this->language->add_lang( [
			'api', 'common', 'notifications', 'permissions', 'ucp'
		], 'danieltj/resolvedtopics' );

	}

	/**
	 * Add Permissions
	 */
	public function add_permissions( $event ) {

		$event->update_subarray( 'permissions', 'f_resolve_own_topics', [
			'lang'	=> 'ACL_F_RESOLVE_OWN_TOPICS',
			'cat'	=> 'post'
		] );

		$event->update_subarray( 'permissions', 'm_resolve_all_topics', [
			'lang'	=> 'ACL_M_RESOLVE_ALL_TOPICS',
			'cat'	=> 'topic_actions'
		] );

	}

	/**
	 * includes/functions:page_header
	 */
	public function add_template_vars( $event ) {

		$user_id = (int) $this->user->data[ 'user_id' ];

		$forum_id = (int) $this->user->page[ 'forum' ];

		$this->template->assign_vars( [
			'S_RESOLVE_TOPICS' => ( 'viewtopic.php' === $this->user->page[ 'page_name' ] && ( $this->auth->acl_getf( 'm_resolve_all_topics', $forum_id ) || $this->auth->acl_getf( 'f_resolve_own_topics', $forum_id ) ) ) ? true : false,
		] );

	}

	/**
	 * memberlist
	 */
	public function update_memberlist_template_vars( $event ) {

		$event[ 'template_ary' ] = array_merge( $event[ 'template_ary' ], [
			'TOTAL_RESOLVED_TOPICS'		=> $this->functions->get_users_total_resolved_topics( $event[ 'user_id' ] ),
			'SEARCH_RESOLVED_TOPICS'	=> append_sid( 'search.php', [
				'author_id'		=> (int) $event[ 'user_id' ],
				'search_id'		=> 'resolved_topics',
			] ),
		] );

	}

	/**
	 * search
	 * 
	 * @todo missing lots of page info (breadcrumbs, title etc)
	 * 
	 * also breaks when the user has no resolved topics
	 */
	public function update_search_sql_query( $event ) {

		if ( 'resolved_topics' === $this->request->variable( 'search_id', '' ) ) {

			$post_ids = $this->functions->get_resolved_topic_posts_by_user_id( $event[ 'author_id_ary' ][ 0 ] );

			$post_where = implode( ', ', $post_ids );

			$event[ 'sql_array' ] = array_merge( $event[ 'sql_array' ], [
				'WHERE' => 'p.post_id IN (' . $post_where . ')',
			] );

		}

	}

	/**
	 * viewforum
	 */
	public function update_topic_template_vars( $event ) {

		$event[ 'topic_row' ] = array_merge( $event[ 'topic_row' ], [
			'TOPIC_RESOLVED' => ( 0 !== (int) $event[ 'row' ][ 'topic_resolved_post_id' ] && false !== $this->functions->get_resolved_topic_post( $event[ 'row' ][ 'topic_id' ] ) ) ? true : false,
		] );

	}

	/**
	 * viewtopic
	 */
	public function add_topic_template_vars( $event ) {

		$post = $this->functions->get_resolved_topic_post( $event[ 'topic_id' ] );

		$this->template->assign_vars( [
			'TOPIC_RESOLVED' => ( false !== $post && isset( $post[ 'post_id' ] ) ) ? trim( generate_board_url(), '/' ) . '/viewtopic.php?p=' . $post[ 'post_id' ] . '#p' . $post[ 'post_id' ] : false,
		] );

	}

	/**
	 * viewtopic
	 */
	public function update_topic_post_row( $event ) {

		$topic_resolved_text = '';

		if ( 0 !== (int) $event[ 'topic_data' ][ 'topic_resolved_by_user_id' ] && $event[ 'row' ][ 'post_id' ] === $event[ 'topic_data' ][ 'topic_resolved_post_id' ] ) {

			$user = $this->functions->get_resolved_topic_user( $event[ 'topic_data' ][ 'topic_id' ] );

			if ( false !== $user ) {

				$topic_resolved_text = sprintf( $this->language->lang( 'RESOLVED_POST_MESSAGE' ), get_username_string( 'full', $user[ 'user_id' ], $user[ 'username' ], $user[ 'user_colour' ] ) );

			}

		}

		$event[ 'post_row' ] = array_merge( $event[ 'post_row' ], [
			'U_RESOLVE'				=> ( $this->functions->can_resolve_topic( $event[ 'topic_data' ][ 'topic_poster' ], $event[ 'topic_data' ][ 'forum_id' ] ) && (int) $event[ 'topic_data' ][ 'topic_first_post_id' ] !== (int) $event[ 'row' ][ 'post_id' ] && 1 === (int) $event[ 'row' ][ 'post_visibility' ] ) ? $this->functions->get_resolve_topic_route( $event[ 'row' ][ 'post_id' ] ) : false,
			'S_POST_RESOLUTION'		=> ( $event[ 'row' ][ 'post_id' ] === $event[ 'topic_data' ][ 'topic_resolved_post_id' ] ) ? true : false,
			'POST_RESOLVED_TOPIC'	=> $topic_resolved_text,
		] );

	}

}
