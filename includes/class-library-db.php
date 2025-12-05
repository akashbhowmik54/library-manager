<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Library_DB {

	private static $table_name;
	private static $version = '1.0';

	public static function init() {
		global $wpdb;
		self::$table_name = $wpdb->prefix . 'library_books';

		// Add DB version option if missing
		if ( get_option( 'library_db_version' ) !== self::$version ) {
			self::create_table();
			update_option( 'library_db_version', self::$version );
		}
	}

	/**
	 * Create custom database table using dbDelta()
	 */
	public static function create_table() {
		global $wpdb;

		self::$table_name = $wpdb->prefix . 'library_books';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . self::$table_name . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			description LONGTEXT,
			author VARCHAR(255),
			publication_year INT,
			status ENUM('available','borrowed','unavailable') DEFAULT 'available',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a new book
	 */
	public static function insert_book( $data ) {
		global $wpdb;

		return $wpdb->insert(
			self::$table_name,
			[
				'title'            => sanitize_text_field( $data['title'] ?? '' ),
				'description'      => wp_kses_post( $data['description'] ?? '' ),
				'author'           => sanitize_text_field( $data['author'] ?? '' ),
				'publication_year' => intval( $data['publication_year'] ?? 0 ),
				'status'           => sanitize_text_field( $data['status'] ?? 'available' ),
			],
			[
				'%s', '%s', '%s', '%d', '%s'
			]
		);
	}

	/**
	 * Get all books (supports filters)
	 */
	public static function get_books( $args = [] ) {
		global $wpdb;

		$where = "WHERE 1=1";
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where .= " AND status = %s";
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['author'] ) ) {
			$where .= " AND author = %s";
			$params[] = sanitize_text_field( $args['author'] );
		}

		if ( ! empty( $args['year'] ) ) {
			$where .= " AND publication_year = %d";
			$params[] = intval( $args['year'] );
		}

		$sql = "SELECT * FROM " . self::$table_name . " $where ORDER BY id DESC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Get a single book by ID
	 */
	public static function get_book( $id ) {
		global $wpdb;

		$sql = "SELECT * FROM " . self::$table_name . " WHERE id = %d LIMIT 1";
		return $wpdb->get_row( $wpdb->prepare( $sql, intval( $id ) ) );
	}

	/**
	 * Update a book
	 */
	public static function update_book( $id, $data ) {
		global $wpdb;

		return $wpdb->update(
			self::$table_name,
			[
				'title'            => sanitize_text_field( $data['title'] ?? '' ),
				'description'      => wp_kses_post( $data['description'] ?? '' ),
				'author'           => sanitize_text_field( $data['author'] ?? '' ),
				'publication_year' => intval( $data['publication_year'] ?? 0 ),
				'status'           => sanitize_text_field( $data['status'] ?? 'available' ),
			],
			[ 'id' => intval( $id ) ],
			[ '%s', '%s', '%s', '%d', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Delete a book
	 */
	public static function delete_book( $id ) {
		global $wpdb;

		return $wpdb->delete(
			self::$table_name,
			[ 'id' => intval( $id ) ],
			[ '%d' ]
		);
	}

}
