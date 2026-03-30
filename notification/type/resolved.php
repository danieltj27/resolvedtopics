<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2026 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\notification\type;

class resolved extends \phpbb\notification\type\base {

	/**
	 * @var user_loader
	 */
	protected $user_loader;

	/**
	 * @var functions
	 */
	protected $functions;

	/**
	 * Notification options data.
	 * 
	 * @var array $notification_option An array of notification data.
	 */
	static public $notification_option = [
		'group'		=> 'NOTIFICATION_GROUP_POSTING',
		'lang'		=> 'RESOLVED_TOPIC_NOTIFICATION_RESOLVED_SAMPLE'
	];

	/**
	 * Set the user loader object.
	 * 
	 * @param  \phpbb\user_loader $user_loader The user_loader object.
	 * @return void
	 */
	public function set_user_loader( \phpbb\user_loader $user_loader ) {

		$this->user_loader = $user_loader;

	}

	/**
	 * Set the extension functions object.
	 * 
	 * @param  \danieltj\resolvedtopics\includes\functions $functions The functions object.
	 * @return void
	 */
	public function set_functions( \danieltj\resolvedtopics\includes\functions $functions ) {

		$this->functions = $functions;

	}

	/**
	 * Returns the type of notification.
	 * 
	 * @return string The type of notification this is.
	 */
	public function get_type() {

		return 'danieltj.resolvedtopics.notification.type.resolved';

	}

	/**
	 * Returns a boolean value checking if the user can access this notification.
	 * 
	 * @return boolean Returns true if permission is granted or false if not.
	 */
	public function is_available() {

		return true;

	}

	/**
	 * Return the notification item ID.
	 * 
	 * @param  array   $data The item data passed to the notification handler.
	 * @return integer       The notification item ID.
	 */
	public static function get_item_id( $data ) {

		return $data[ 'item_id' ];

	}

	/**
	 * Return the ID of the parent.
	 * 
	 * @param  array   $data The item data passed to the notification handler.
	 * @return integer       The ID of the parent.
	 */
	public static function get_item_parent_id( $data ) {

		return 0;

	}

	/**
	 * Returns a collection of user IDs that want this notification.
	 * 
	 * @param  array $data    The array of data for this notification.
	 * @param  array $options The array of options for filtering users.
	 * @return array          The array of users.
	 */
	public function find_users_for_notification( $data, $options = [] ) {

		/**
		 * @todo fix users sending resolved topic notifications to themselves
		 */

		$options = array_merge( [
			'ignore_users' => [ ANONYMOUS, (int) $data[ 'user_id' ] ]
		], $options );

		$user_methods = $this->check_user_notification_options( [ $data[ 'poster_id' ] ], $options );

		return $user_methods;

	}

	/**
	 * Return the array of users required for this notification.
	 * 
	 * @return array The array of user IDs.
	 */
	public function users_to_query() {

		return [ $this->get_data( 'poster_id' ) ];

	}

	/**
	 * Return the avatar of the user.
	 * 
	 * @return string  The HTML formatted avatar.
	 */
	public function get_avatar() {

		return $this->user_loader->get_avatar( $this->get_data( 'user_id' ), true, true );

	}

	/**
	 * Return the title of the notification.
	 * 
	 * @return string The notification title.
	 */
	public function get_title() {

		return $this->language->lang( 'RESOLVED_TOPIC_NOTIFICATION_RESOLVED_TITLE', $this->user_loader->get_username( $this->get_data( 'user_id' ), 'no_profile' ) );

	}

	/**
	 * Return the reference of the notification.
	 * 
	 * @return string The notification reference.
	 */
	public function get_reference() {

		return '<em>' . $this->get_data( 'topic_title' ) . '</em>';

	}

	/**
	 * Return the URL of the notification.
	 * 
	 * @return string The notification URL.
	 */
	public function get_url() {

		return trim( generate_board_url(), '/' ) . '/viewtopic.php?p=' . $this->get_data( 'post_id' ) . '#p' . $this->get_data( 'post_id' );

	}

	/**
	 * Return the email template.
	 * 
	 * @return string The name of the email template file.
	 */
	public function get_email_template() {

		return '@danieltj_resolvedtopics/resolved';

	}

	/**
	 * Return the template variables for the email.
	 * 
	 * @return array The array of variables required for the template.
	 */
	public function get_email_template_variables() {

		return [
			'RESOLVED_BY_USER'	=> htmlspecialchars_decode( $this->user_loader->get_username( $this->get_data( 'user_id' ), 'username', false, false, true ) ),
			'TOPIC_TITLE'		=> $this->get_data( 'topic_title' ),
		];

	}

	/**
	 * Prepare notification data for database insertion.
	 * 
	 * @param  array $data            The notification data.
	 * @param  array $pre_create_data The array data from `pre_create_insert_array()`.
	 * @return void
	 */
	public function create_insert_array( $data, $pre_create_data = [] ) {

		$this->set_data( 'item_id', $data[ 'item_id' ] );
		$this->set_data( 'post_id', $data[ 'post_id' ] );
		$this->set_data( 'poster_id', $data[ 'poster_id' ] );
		$this->set_data( 'user_id', $data[ 'user_id' ] );
		$this->set_data( 'topic_title', $data[ 'topic_title' ] );

		parent::create_insert_array( $data, $pre_create_data );

	}

	/**
	 * Function for getting the data for insertion in an SQL query.
	 *
	 * @return array Array of data ready to be inserted into the database.
	 */
	public function get_insert_array() {

		return parent::get_insert_array();

	}

}
