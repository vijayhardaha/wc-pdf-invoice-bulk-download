<?php
/**
 * WooCommerce PDF Invoices Bulk Download setup
 *
 * @package WC_PDF_Invoices_Bulk_Download
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main WC_PDF_Invoices_Bulk_Download Class.
 *
 * @class WC_PDF_Invoices_Bulk_Download
 */
final class WC_PDF_Invoices_Bulk_Download {
	/**
	 * Single Request
	 *
	 * @var Object.
	 */
	protected $process_single;

	/**
	 * The single instance of the class.
	 *
	 * @var WC_PDF_Invoices_Bulk_Download
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Main WC_PDF_Invoices_Bulk_Download Instance.
	 *
	 * Ensures only one instance of WC_PDF_Invoices_Bulk_Download is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return WC_PDF_Invoices_Bulk_Download - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WC_PDF_Invoices_Bulk_Download Constructor.
	 */
	public function __construct() {
		// Check if WooCommerce is active.
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! function_exists( 'WC' ) ) {
			return;
		}

		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * When WP has loaded all plugins, trigger the `wc_pdf_invoices_bulk_download_loaded` hook.
	 *
	 * This ensures `wc_pdf_invoices_bulk_download_loaded` is called only after all other plugins
	 * are loaded.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'wc_pdf_invoices_bulk_download_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_notices', array( $this, 'build_dependencies_notice' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Output a admin notice when build dependencies not met.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function build_dependencies_notice() {
		$old_php = version_compare( phpversion(), WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_PHP_VERSION, '<' );
		$old_wp  = version_compare( get_bloginfo( 'version' ), WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_WP_VERSION, '<' );

		// Both PHP and WordPress up to date version => no notice.
		if ( ! $old_php && ! $old_wp ) {
			return;
		}

		if ( $old_php && $old_wp ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version 3: Minimum WordPress version */
				__( 'Update required: WooCommerce PDF Invoices Bulk Download require PHP version %1$s or newer (%2$s or higher recommended) and WordPress version %3$s or newer to work properly. Please update to required version to have best experience.', 'wc-pdf-invoices-bulk-download' ),
				WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_PHP_VERSION,
				WC_PDF_INVOICES_BULK_DOWNLOAD_BEST_PHP_VERSION,
				WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_WP_VERSION
			);
		} elseif ( $old_php ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version */
				__( 'Update required: WooCommerce PDF Invoices Bulk Download require PHP version %1$s or newer (%2$s or higher recommended) to work properly. Please update to required version to have best experience.', 'wc-pdf-invoices-bulk-download' ),
				WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_PHP_VERSION,
				WC_PDF_INVOICES_BULK_DOWNLOAD_BEST_PHP_VERSION
			);
		} elseif ( $old_wp ) {
			$msg = sprintf(
				/* translators: %s: Minimum WordPress version */
				__( 'Update required: WooCommerce PDF Invoices Bulk Download require WordPress version %s or newer to work properly. Please update to required version to have best experience.', 'wc-pdf-invoices-bulk-download' ),
				WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_WP_VERSION
			);
		}

		echo '<div class="error"><p>' . $msg . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.0.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				/* translators: 1: error message 2: file name and path 3: line number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'wc-pdf-invoices-bulk-download' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable
			}
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_ABSPATH', dirname( WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE ) . '/' );
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_BASENAME', plugin_basename( WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE ) );
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_VERSION', '1.0.0' );
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_PHP_VERSION', '5.3' );
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_BEST_PHP_VERSION', '5.6' );
		$this->define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_MIN_WP_VERSION', '4.0' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( $this->is_request( 'admin' ) || $this->is_request( 'ajax' ) ) {
			require WC_PDF_INVOICES_BULK_DOWNLOAD_ABSPATH . 'vendor/autoload.php';
			include_once WC_PDF_INVOICES_BULK_DOWNLOAD_ABSPATH . 'includes/admin/class-wc-pdf-invoices-bulk-download-admin.php';
			include_once WC_PDF_INVOICES_BULK_DOWNLOAD_ABSPATH . 'includes/admin/class-wc-pdf-invoices-bulk-download-async-request.php';
		}
	}

	/**
	 * Init WC_PDF_Invoices_Bulk_Download when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_wc_pdf_invoices_bulk_download_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		if ( $this->is_request( 'admin' ) || $this->is_request( 'ajax' ) ) {
			$this->process_single = new WC_PDF_Invoices_Bulk_Download_Async_Request();
			$this->process_single->dispatch();
		}

		// Init action.
		do_action( 'wc_pdf_invoices_bulk_download_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wc-pdf-invoices-bulk-download/wc-pdf-invoices-bulk-download-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc-pdf-invoices-bulk-download-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'wc-pdf-invoices-bulk-download' );

		unload_textdomain( 'wc-pdf-invoices-bulk-download' );
		load_textdomain( 'wc-pdf-invoices-bulk-download', WP_LANG_DIR . '/wc-pdf-invoices-bulk-download/wc-pdf-invoices-bulk-download-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc-pdf-invoices-bulk-download', false, plugin_basename( dirname( WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE ) );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
}
