<?php
/**
 * Plugin Name: WooCommerce PDF Invoices Bulk Download
 * Plugin URI: https://example.com
 * Description: This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://example.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: wc-pdf-invoices-bulk-download
 * Domain Path: /languages/
 *
 * @package WC_PDF_Invoices_Bulk_Download
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE' ) ) {
	define( 'WC_PDF_INVOICES_BULK_DOWNLOAD_PLUGIN_FILE', __FILE__ );
}

// Include the main WC_PDF_Invoices_Bulk_Download class.
if ( ! class_exists( 'WC_PDF_Invoices_Bulk_Download', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-pdf-invoices-bulk-download.php';
}

/**
 * Returns the main instance of WC_PDF_Invoices_Bulk_Download.
 *
 * @since  1.0.0
 * @return WC_PDF_Invoices_Bulk_Download
 */
function wc_pdf_invoices_bulk_download() {
	return WC_PDF_Invoices_Bulk_Download::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_pdf_invoices_bulk_download'] = wc_pdf_invoices_bulk_download();
