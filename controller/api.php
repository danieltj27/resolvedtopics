<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2025 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\controller;

use phpbb\language\language;
use phpbb\log\log;
use phpbb\notification\manager as notifications;
use phpbb\request\request;
use phpbb\user;
use danieltj\resolvedtopics\includes\functions;

use Symfony\Component\DependencyInjection\ContainerInterface as container;
use Symfony\Component\HttpFoundation\JsonResponse as response;

final class api {

	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @var log
	 */
	protected $log;

	/**
	 * @var notifications
	 */
	protected $notifications;

	/**
	 * @var request
	 */
	protected $request;

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
	public function __construct( language $language, log $log, notifications $notifications, request $request, user $user, functions $functions ) {

		$this->language = $language;
		$this->log = $log;
		$this->notifications = $notifications;
		$this->request = $request;
		$this->user = $user;
		$this->functions = $functions;

	}

	/**
	 * Toggle the resolve status of a topic.
	 * 
	 * @since 1.0.0
	 * 
	 * @param integer $id The ID of a post within a topic.
	 * 
	 * @return string  Returns a JSON object.
	 */
	public function resolve( $id ) {

		$post_id = (int) $id;

		$user_id = (int) $this->user->data[ 'user_id' ];

		if ( ! check_link_hash( $this->request->variable( 'csrf', '' ), $this->functions->get_ext_csrf_token_name() ) ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_CSRF_FAILURE', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return new response( [
				'http'			=> 401,
				'title'			=> $this->language->lang( 'INFORMATION' ),
				'text'			=> $this->language->lang( 'RESOLVED_TOPICS_API_ACTION_ERROR' ),
				'post_id'		=> $post_id
			] );

		}

		if ( ! $this->request->is_ajax() ) {

			$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_INVALID_METHOD', time(), [
				'reportee_id' => $user_id,
				'post_id' => $post_id,
			] );

			return new response( [
				'http'			=> 405,
				'title'			=> $this->language->lang( 'INFORMATION' ),
				'text'			=> $this->language->lang( 'RESOLVED_TOPICS_API_ACTION_ERROR' ),
				'post_id'		=> $post_id
			] );

		}

		if ( confirm_box( true ) ) {

			$response = $this->functions->update_topic_resolution( $post_id );

			if ( false === $response ) {

				$this->log->add( 'user', $user_id, $this->user->data[ 'user_ip' ], 'RESOLVED_TOPICS_ERROR_RESOLVE_FAILURE', time(), [
					'reportee_id' => $user_id,
					'post_id' => $post_id,
				] );

				return new response( [
					'http'			=> 500,
					'title'			=> $this->language->lang( 'INFORMATION' ),
					'text'			=> $this->language->lang( 'RESOLVED_TOPICS_API_ACTION_ERROR' ),
					'post_id'		=> $post_id
				] );

			}

			return new response( [
				'http'			=> 200,
				'title'			=> $this->language->lang( 'INFORMATION' ),
				'text'			=> $this->language->lang( 'RESOLVED_TOPICS_API_ACTION_SUCCESS' ),
				'post_id'		=> $post_id
			] );

		} else {

			confirm_box( false, $this->language->lang( 'RESOLVED_TOPICS_API_CONFIRM_RESOLVE' ), build_hidden_fields( [ 'post_id' => $post_id ] ) );

		}

	}

}
