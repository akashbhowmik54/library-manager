<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Library Admin: register admin menu and enqueue React app
 */
class Library_Admin {

	/**
	 * The admin page hook suffix (set when menu is added)
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Slug for the admin menu page
	 *
	 * @var string
	 */
	public static $page_slug = 'library-manager-dashboard';

	/**
	 * Initialize admin hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_build_notice' ) );
	}

	/**
	 * Register top-level admin menu
	 */
	public static function register_menu() {
		$capability = 'edit_posts'; // match REST permission requirement for mutating actions

		/**
		 * add_menu_page returns the $hook_suffix which we store to limit script loading
		 */
		self::$page_hook = add_menu_page(
			__( 'Library Manager', 'library-manager' ),
			__( 'Library Manager', 'library-manager' ),
			$capability,
			self::$page_slug,
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-book',
			25
		);
	}

	/**
	 * Render admin page container (React app will mount here)
	 */
	public static function render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Library Manager', 'library-manager' ); ?></h1>
			<div id="library-manager-app"></div>
			<noscript>
				<p><?php esc_html_e( 'This page requires JavaScript. Please enable JavaScript to use the Library Manager UI.', 'library-manager' ); ?></p>
			</noscript>
		</div>
		<?php
	}
	
	public static function enqueue_admin_assets( $hook ) {
		// Only enqueue on our plugin admin page
		if ( empty( self::$page_hook ) || $hook !== self::$page_hook ) {
			return;
		}

		$plugin_dir = dirname( dirname( __FILE__ ) ) . '/admin';
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) ) . 'admin/';

		// Expected production build files
		$js_bundle_path  = $plugin_dir . '/build/assets/index-TedKnzMW.js';
		$css_bundle_path = $plugin_dir . '/build/assets/index-CFnJL99V.css';

		$js_bundle_url  = $plugin_url . 'build/assets/index-TedKnzMW.js';
		$css_bundle_url = $plugin_url . 'build/assets/index-CFnJL99V.css';

		if ( file_exists( $js_bundle_path ) ) {
			$version = filemtime( $js_bundle_path );

			wp_register_script(
				'library-admin-app',
				$js_bundle_url,
				array( 'wp-element' ),
				$version,
				true
			);

			if ( file_exists( $css_bundle_path ) ) {
				$css_version = filemtime( $css_bundle_path );
				wp_enqueue_style(
					'library-admin-app-style',
					$css_bundle_url,
					array(),
					$css_version
				);
			}

			$rest_base = rest_url( 'library/v1' );
			$nonce     = wp_create_nonce( 'wp_rest' );

			wp_localize_script(
				'library-admin-app',
				'libraryApp',
				array(
					'rest_url'  => untrailingslashit( $rest_base ),
					'nonce'     => $nonce,
					'api_root'  => esc_url_raw( rest_url() ), 
					'page_slug' => self::$page_slug,
				)
			);

			wp_enqueue_script( 'library-admin-app' );
		} else {
			wp_register_script(
				'library-admin-app-placeholder',
				'',
				array(),
				'1.0',
				true
			);
			wp_enqueue_script( 'library-admin-app-placeholder' );
		}
	}

	/**
	 * If the React build is missing, show an admin notice so developer knows to build the app
	 */
	public static function maybe_show_build_notice() {

		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_' . self::$page_slug ) {
			return;
		}

		$build_dir = dirname( dirname( __FILE__ ) ) . '/admin/build';
		if ( ! file_exists( $build_dir ) || ! is_dir( $build_dir ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						esc_html__( 'Library Manager: React build not found.', 'library-manager' ),
						esc_html( dirname( LM_PLUGIN_DIR ) )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
