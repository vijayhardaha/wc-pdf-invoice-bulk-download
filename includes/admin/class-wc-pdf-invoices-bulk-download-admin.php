<?php
/**
 * WooCommerce PDF Invoices Bulk Download Admin
 *
 * @class WC_PDF_Invoices_Bulk_Download_Admin
 * @package WC_PDF_Invoices_Bulk_Download
 * @subpackage WC_PDF_Invoices_Bulk_Download/Admin
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_PDF_Invoices_Bulk_Download_Admin' ) ) {
	return new WC_PDF_Invoices_Bulk_Download_Admin();
}

/**
 * WC_PDF_Invoices_Bulk_Download_Admin class.
 */
class WC_PDF_Invoices_Bulk_Download_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Create directory.
		$this->create_target_dir();

		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'hide_all_notices' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_submenu_page( 'woocommerce', __( 'Invoice Bulk Download', 'wc-pdf-invoices-bulk-download' ), __( 'Invoice Bulk Download', 'wc-pdf-invoices-bulk-download' ), 'manage_woocommerce', 'wc-invoice-bulk-download', array( $this, 'admin_menu_page' ) );
	}

	/**
	 * Recursive directory creation based on full path.
	 *
	 * @return boolean
	 */
	public function create_target_dir() {
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( path_join( $upload_dir['basedir'], 'wc-invoice-archives/' ) );

		return wp_mkdir_p( $path );
	}

	/**
	 * Hides all admin notice for new admin page
	 */
	public function hide_all_notices() {
		if ( $this->is_valid_screen() ) {
			remove_all_actions( 'admin_notices' );
		}
	}

	/**
	 * Valid screen ids for plugin scripts & styles
	 *
	 * @return  array
	 */
	public function is_valid_screen() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$valid_screen_ids = apply_filters(
			'wc_pdf_invoices_bulk_download_valid_admin_screen_ids',
			array(
				'wc-invoice-bulk-download',
			)
		);

		if ( empty( $valid_screen_ids ) ) {
			return false;
		}

		foreach ( $valid_screen_ids as $admin_screen_id ) {
			$matcher = '/' . $admin_screen_id . '/';
			if ( preg_match( $matcher, $screen_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_register_style( 'wc-pdf-invoices-bulk-download-admin-styles', wc_pdf_invoices_bulk_download()->plugin_url() . '/assets/css/admin' . $suffix . '.css', array(), WC_PDF_INVOICES_BULK_DOWNLOAD_VERSION );

		// Admin styles for wc_pdf_invoices_bulk_download pages only.
		if ( $this->is_valid_screen() ) {
			wp_enqueue_style( 'wc-pdf-invoices-bulk-download-admin-styles' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Register scripts.
		wp_register_script( 'wc-pdf-invoices-bulk-download-admin', wc_pdf_invoices_bulk_download()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_PDF_INVOICES_BULK_DOWNLOAD_VERSION, true );

		// Admin scripts for wc_pdf_invoices_bulk_download pages only.
		if ( $this->is_valid_screen() ) {
			wp_enqueue_script( 'wc-pdf-invoices-bulk-download-admin' );
			$params = array(
				'ajaxurl'  => esc_url( admin_url( 'admin-ajax.php' ) ),
				'messages' => array(
					'general_error' => __( 'Something wrong. Please, try later.', 'wc-pdf-invoices-bulk-download' ),
					'server_error'  => __( 'Please, increase your System Status Limits (PHP Time Limit, PHP Memory Limit, PHP Max Input Vars) or contact with your hosting.', 'wc-pdf-invoices-bulk-download' ),
					'processing'    => __( 'Archive is preparing. Please, wait...', 'wc-pdf-invoices-bulk-download' ),
					'success'       => __( 'Archive successfully created.', 'wc-pdf-invoices-bulk-download' ),
				),
			);
			wp_localize_script( 'wc-pdf-invoices-bulk-download-admin', 'wc_pdf_invoices_bulk_download_admin_params', $params );
		}
	}

	/**
	 * Display admin page
	 */
	public function admin_menu_page() {
		$download_filters = array(
			'month-group' => __( 'Month & Year', 'wc-pdf-invoices-bulk-download' ),
			'range-group' => __( 'Custom Date Range', 'wc-pdf-invoices-bulk-download' ),
		);

		$months = array(
			'January'   => 'January',
			'February'  => 'February',
			'March'     => 'March',
			'April'     => 'April',
			'May'       => 'May',
			'June'      => 'June',
			'July'      => 'July',
			'August'    => 'August',
			'September' => 'September',
			'October'   => 'October',
			'November'  => 'November',
			'December'  => 'December',
		);

		$years = $this->years_options();

		?>
		<div class="wrap wc-pdf-invoices-bulk-download-container" id="wc-pdf-invoices-bulk-download-container">
			<div class="wrapper">
				<div class="page-title">
					<h2>
						<span class="dashicons dashicons-pdf"></span>
						<span class="link-shadow"><?php esc_html_e( 'Invoice Bulk Download', 'wc-pdf-invoices-bulk-download' ); ?></span>
					</h2>
				</div>

				<div class="page-content">
					<form method="post" action="" class="wc-pdf-invoices-bulk-download-form">
						<input type="hidden" name="action" value="wc_pdf_invoices_bulk_download_request" />
						<?php wp_nonce_field( 'wc_pdf_invoices_bulk_download_request', 'nonce' ); ?>

						<div id="setting-row-download-filter" class="setting-row clear">
							<div class="setting-label">
								<label for="setting-download-filter"><?php esc_html_e( 'Download Filter', 'wc-pdf-invoices-bulk-download' ); ?></label>
							</div>
							<div class="setting-field">
								<select id="setting-download-filter" name="download_filter">
									<?php
									foreach ( $download_filters as $key => $value ) {
										printf( '<option value="%1$s">%2$s</option>', esc_attr( $key ), esc_html( $value ) );
									}
									?>
								</select>
								<p class="desc"><?php esc_html_e( 'Choose the report orders period.', 'wc-pdf-invoices-bulk-download' ); ?></p>
							</div>
						</div>

						<div id="setting-row-month-year-range" class="setting-row clear">
							<div class="setting-label">
								<label for="setting-month-year-range"><?php esc_html_e( 'Month & Year', 'wc-pdf-invoices-bulk-download' ); ?></label>
							</div>
							<div class="setting-field">
								<div class="input-group">
									<span for="setting-order-month" class="label"><?php esc_html_e( 'Order Month', 'wc-pdf-invoices-bulk-download' ); ?></span>
									<select id="setting-order-month" name="order_month">
									<?php
									foreach ( $months as $value ) {
										printf( '<option value="%1$s">%2$s</option>', esc_attr( $value ), esc_html( $value ) );
									}
									?>
									</select>
								</div>
								<div class="input-group">
									<span for="setting-order-year" class="label"><?php esc_html_e( 'Order Year', 'wc-pdf-invoices-bulk-download' ); ?></span>
									<select id="setting-order-year" name="order_year">
									<?php
									foreach ( $years as $value ) {
										printf( '<option value="%1$s">%2$s</option>', esc_attr( $value ), esc_html( $value ) );
									}
									?>
									</select>
								</div>
								<p class="desc"><?php esc_html_e( 'Choose the month & year for orders period.', 'wc-pdf-invoices-bulk-download' ); ?></p>
							</div>
						</div>

						<div id="setting-row-custom-date-range" class="setting-row clear">
							<div class="setting-label">
								<label for="setting-custom-date-range"><?php esc_html_e( 'Custom Date Range', 'wc-pdf-invoices-bulk-download' ); ?></label>
							</div>
							<div class="setting-field">
								<div class="input-group">
									<span for="setting-start-date" class="label"><?php esc_html_e( 'Start Date', 'wc-pdf-invoices-bulk-download' ); ?></span>
									<input id="setting-start-date" type="text" class="datepicker" name="start_date" placeholder="YYYY-MM-DD" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( 'today midnight' ) - MONTH_IN_SECONDS ) ); ?>" readonly />
								</div>
								<div class="input-group">
									<span for="setting-end-date" class="label"><?php esc_html_e( 'End Date', 'wc-pdf-invoices-bulk-download' ); ?></span>
									<input id="setting-end-date" type="text" class="datepicker" name="end_date" placeholder="YYYY-MM-DD"value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( 'today midnight' ) + DAY_IN_SECONDS - 1 ) ); ?>" readonly />
								</div>
								<p class="desc"><?php esc_html_e( 'Choose the custom date range for orders period.', 'wc-pdf-invoices-bulk-download' ); ?></p>
							</div>
						</div>

						<div id="setting-row-order-status" class="setting-row clear">
							<div class="setting-label">
								<label for="setting-order-status"><?php esc_html_e( 'Order Status', 'wc-pdf-invoices-bulk-download' ); ?></label>
							</div>
							<div class="setting-field">
								<?php
								foreach ( wc_get_order_statuses() as $key => $value ) {
									printf(
										'<div class="checkbox-group inline"><input id="setting-order-status-%1$s" name="order_status[]" type="checkbox" value="%1$s" checked /><label for="setting-order-status-%1$s">%2$s</label></div>',
										esc_attr( $key ),
										esc_attr( $value )
									);
								}
								?>
								<p class="desc"><?php esc_html_e( 'Choose the order statuses to be included.', 'wc-pdf-invoices-bulk-download' ); ?></p>
							</div>
						</div>

						<p class="setting-submit">
							<button class="btn download-invoices" type="submit"><?php esc_html_e( 'Download Invoices', 'wc-pdf-invoices-bulk-download' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns years array for settings field
	 *
	 * @return array
	 */
	private function years_options() {
		$now     = gmdate( 'Y' );
		$then    = $now - 10;
		$years   = range( $now, $then );
		$options = array();
		foreach ( $years as $year ) {
			$options[ $year ] = $year;
		}

		return $options;
	}
}

return new WC_PDF_Invoices_Bulk_Download_Admin();
