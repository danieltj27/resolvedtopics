<?php

/**
 * @package Resolved Topics
 * @copyright (c) 2026 Daniel James
 * @license https://opensource.org/license/gpl-2-0
 */

namespace danieltj\resolvedtopics\migrations;

class v100 extends \phpbb\db\migration\migration {

	/**
	 * Check extension is installed.
	 */
	public function effectively_installed() {

		return (
			$this->db_tools->sql_column_exists( $this->table_prefix . 'topics', 'topic_resolved_post_id' ) &&
			$this->db_tools->sql_column_exists( $this->table_prefix . 'topics', 'topic_resolved_poster_id' ) &&
			$this->db_tools->sql_column_exists( $this->table_prefix . 'topics', 'topic_resolved_by_user_id' )
		);

	}

	/**
	 * Require 3.3.0 or later.
	 */
	static public function depends_on() {

		return [ '\phpbb\db\migration\data\v330\v330' ];

	}

	/**
	 * Install
	 */
	public function update_schema() {

		return [
			'add_columns' => [
				$this->table_prefix . 'topics' => [
					'topic_resolved_post_id'	=> [ 'UINT:8', 0 ],
					'topic_resolved_poster_id'	=> [ 'UINT:8', 0 ],
					'topic_resolved_by_user_id'	=> [ 'UINT:8', 0 ],
				]
			]
		];

	}

	/**
	 * Uninstall
	 */
	public function revert_schema() {

		return [
			'drop_columns' => [
				$this->table_prefix . 'topics' => [
					'topic_resolved_post_id',
					'topic_resolved_poster_id',
					'topic_resolved_by_user_id',
				]
			]
		];

	}

	/**
	 * Add additional data.
	 */
	public function update_data() {

		return [
			[ 'config.add', [ 'resolved_topics_notify_item_id', '0' ] ],

			[ 'permission.add', [ 'f_resolve_own_topics', false, 'f_bump' ] ],
			[ 'permission.add', [ 'm_resolve_all_topics', false, 'm_lock' ] ],
		];

	}

}
