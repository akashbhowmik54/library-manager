<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Library REST API
 */
class Library_REST {

	/**
	 * Allowed status values (mirror DB enum)
	 */
	private static $allowed_status = array( 'available', 'borrowed', 'unavailable' );

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$ns = 'library/v1';

		register_rest_route( $ns, '/books', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_books' ),
				'permission_callback' => '__return_true', 
				'args'                => self::get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_book' ),
				'permission_callback' => array( __CLASS__, 'permission_check_edit_posts' ),
				'args'                => self::get_item_schema(),
			),
		) );

		register_rest_route( $ns, '/books/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_book' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'update_book' ),
				'permission_callback' => array( __CLASS__, 'permission_check_edit_posts' ),
				'args'                => self::get_item_schema(),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'delete_book' ),
				'permission_callback' => array( __CLASS__, 'permission_check_edit_posts' ),
				'args'                => array(
					'id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			),
		) );
	}

	public static function permission_check_edit_posts( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'library-manager' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	public static function get_collection_params() {
		return array(
			'status'   => array(
				'description'       => 'Filter by status (available|borrowed|unavailable).',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'author'   => array(
				'description'       => 'Filter by author name.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'year'     => array(
				'description'       => 'Filter by publication year (integer).',
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'page'     => array(
				'description'       => 'Page number for pagination (1-indexed).',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Items per page for pagination.',
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
			),
		);
	}

	public static function get_item_schema() {
		return array(
			'title' => array(
				'required'          => true,
				'description'       => 'Book title (required).',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'description' => array(
				'description'       => 'Long description (HTML allowed, sanitized).',
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_description' ),
			),
			'author' => array(
				'description'       => 'Author name.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'publication_year' => array(
				'description'       => 'Publication year (integer).',
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'status' => array(
				'description'       => "Book status. Allowed: " . implode( ',', self::$allowed_status ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	public static function sanitize_description( $value ) {
		return wp_kses_post( $value );
	}

	public static function get_books( $request ) {
		$params = array();

		$status   = $request->get_param( 'status' );
		$author   = $request->get_param( 'author' );
		$year     = $request->get_param( 'year' );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = max( 1, (int) $request->get_param( 'per_page' ) );

		if ( $status ) {
			$status = sanitize_text_field( $status );
			if ( ! in_array( $status, self::$allowed_status, true ) ) {
				return new WP_Error( 'invalid_status', 'Invalid status filter.', array( 'status' => 400 ) );
			}
			$params['status'] = $status;
		}

		if ( $author ) {
			$params['author'] = sanitize_text_field( $author );
		}

		if ( $year ) {
			$params['year'] = intval( $year );
		}

		$items = Library_DB::get_books( $params );

		if ( ! is_array( $items ) ) {
			return new WP_Error( 'db_error', 'Failed to retrieve books.', array( 'status' => 500 ) );
		}

		$total = count( $items );

		$offset = ( $page - 1 ) * $per_page;
		$paginated = array_slice( $items, $offset, $per_page );

		$data = array_map( array( __CLASS__, 'format_book_for_response' ), $paginated );

		$response = new WP_REST_Response( $data, 200 );

		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

		return $response;
	}

	public static function get_book( $request ) {
		$id = intval( $request->get_param( 'id' ) );

		$book = Library_DB::get_book( $id );

		if ( empty( $book ) ) {
			return new WP_Error( 'not_found', 'Book not found.', array( 'status' => 404 ) );
		}

		$data = self::format_book_for_response( $book );

		return new WP_REST_Response( $data, 200 );
	}

	public static function create_book( $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$validation = self::validate_item_payload( $body, true );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$inserted = Library_DB::insert_book( $validation );

		if ( false === $inserted ) {
			return new WP_Error( 'db_insert_failed', 'Failed to create book.', array( 'status' => 500 ) );
		}

		global $wpdb;
		$created_id = $wpdb->insert_id;

		$book = Library_DB::get_book( $created_id );
		$data = self::format_book_for_response( $book );

		return new WP_REST_Response( $data, 201 );
	}

	public static function update_book( $request ) {
		$id = intval( $request->get_param( 'id' ) );

		$existing = Library_DB::get_book( $id );
		if ( empty( $existing ) ) {
			return new WP_Error( 'not_found', 'Book not found.', array( 'status' => 404 ) );
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$validation = self::validate_item_payload( $body, false );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$updated = Library_DB::update_book( $id, $validation );

		if ( false === $updated ) {
			return new WP_Error( 'db_update_failed', 'Failed to update book.', array( 'status' => 500 ) );
		}

		$book = Library_DB::get_book( $id );
		$data = self::format_book_for_response( $book );

		return new WP_REST_Response( $data, 200 );
	}

	public static function delete_book( $request ) {
		$id = intval( $request->get_param( 'id' ) );

		$existing = Library_DB::get_book( $id );
		if ( empty( $existing ) ) {
			return new WP_Error( 'not_found', 'Book not found.', array( 'status' => 404 ) );
		}

		$deleted = Library_DB::delete_book( $id );

		if ( false === $deleted ) {
			return new WP_Error( 'db_delete_failed', 'Failed to delete book.', array( 'status' => 500 ) );
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Validate and sanitize payload for create/update
	 *
	 * @param array $data Raw input data
	 * @param bool $require_title If true, title is required
	 *
	 * @return array|WP_Error Sanitized data or WP_Error
	 */
	private static function validate_item_payload( $data, $require_title = true ) {
		$clean = array();

		if ( isset( $data['title'] ) ) {
			$clean['title'] = sanitize_text_field( $data['title'] );
		} elseif ( $require_title ) {
			return new WP_Error( 'missing_title', 'Title is required.', array( 'status' => 400 ) );
		}

		if ( isset( $data['description'] ) ) {
			$clean['description'] = wp_kses_post( $data['description'] );
		}

		if ( isset( $data['author'] ) ) {
			$clean['author'] = sanitize_text_field( $data['author'] );
		}

		if ( isset( $data['publication_year'] ) && $data['publication_year'] !== '' ) {
			if ( ! is_numeric( $data['publication_year'] ) ) {
				return new WP_Error( 'invalid_year', 'publication_year must be an integer.', array( 'status' => 400 ) );
			}
			$clean['publication_year'] = intval( $data['publication_year'] );
		}

		if ( isset( $data['status'] ) ) {
			$status = sanitize_text_field( $data['status'] );
			if ( ! in_array( $status, self::$allowed_status, true ) ) {
				return new WP_Error( 'invalid_status', 'Invalid status value.', array( 'status' => 400 ) );
			}
			$clean['status'] = $status;
		}

		return $clean;
	}

	/**
	 * Prepare book object/row for API response
	 *
	 * @param object|array $item
	 * @return array
	 */
	private static function format_book_for_response( $item ) {
		$book = array(
			'id'               => isset( $item->id ) ? intval( $item->id ) : ( isset( $item['id'] ) ? intval( $item['id'] ) : 0 ),
			'title'            => isset( $item->title ) ? wp_kses_post( $item->title ) : '',
			'description'      => isset( $item->description ) ? wp_kses_post( $item->description ) : '',
			'author'           => isset( $item->author ) ? sanitize_text_field( $item->author ) : '',
			'publication_year' => isset( $item->publication_year ) ? intval( $item->publication_year ) : null,
			'status'           => isset( $item->status ) ? sanitize_text_field( $item->status ) : 'available',
			'created_at'       => isset( $item->created_at ) ? sanitize_text_field( $item->created_at ) : '',
			'updated_at'       => isset( $item->updated_at ) ? sanitize_text_field( $item->updated_at ) : '',
		);

		return $book;
	}
}
