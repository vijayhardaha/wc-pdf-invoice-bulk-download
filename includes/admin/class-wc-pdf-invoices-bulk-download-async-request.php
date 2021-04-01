<?php
/**
 * WooCommerce PDF Invoices Bulk Download Async Request
 *
 * @class WC_PDF_Invoices_Bulk_Download_Async_Request
 * @package WC_PDF_Invoices_Bulk_Download
 * @subpackage WC_PDF_Invoices_Bulk_Download/Admin
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WC_PDF_Invoices_Bulk_Download_Async_Request class.
 */
class WC_PDF_Invoices_Bulk_Download_Async_Request extends WP_Async_Request {
	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'wc';

	/**
	 * Action
	 *
	 * (default value: 'async_request')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'pdf_invoices_bulk_download_request';

	/**
	 * Start Date
	 *
	 * @var string
	 */
	public $date_after = null;

	/**
	 * End date
	 *
	 * @var string
	 */
	public $date_before = null;

	/**
	 * Handle
	 */
	protected function handle() {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( isset( $_POST['download_filter'] ) ) {
			$filter = sanitize_text_field( wp_unslash( $_POST['download_filter'] ) );
			if ( 'month-group' === $filter ) {
				$timestamp         = strtotime( sprintf( '%s %s', sanitize_text_field( wp_unslash( $_POST['order_month'] ) ), sanitize_text_field( wp_unslash( $_POST['order_year'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				$this->date_after  = gmdate( 'Y-m-01', $timestamp );
				$this->date_before = gmdate( 'Y-m-t', $timestamp );
			} elseif ( 'range-group' === $filter ) {
				$this->date_after  = sanitize_text_field( wp_unslash( $_POST['start_date'] ) );
				$this->date_before = sanitize_text_field( wp_unslash( $_POST['end_date'] ) );
			}
		}

		if ( null !== $this->date_after && null !== $this->date_before ) {
			$status = isset( $_POST['order_status'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order_status'] ) ) : array();
			$result = $this->generate_zip( $status );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			} else {
				wp_send_json_success( $result );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		wp_send_json_error();
	}

	/**
	 * Generate zip
	 *
	 * @param array $order_statuses Order statuses array.
	 */
	public function generate_zip( $order_statuses ) {
		$args = array(
			'date_after'  => $this->date_after,
			'date_before' => $this->date_before,
			'status'      => $order_statuses,
			'type'        => 'shop_order',
			'limit'       => -1,
		);

		$orders = wc_get_orders( $args );

		if ( empty( $orders ) ) {
			return new WP_Error( 'error', sprintf( /* translators: 1: start date 2: end date */ esc_html__( 'Order from %1$s to %2$s not found.', 'wc-pdf-invoices-bulk-download' ), $this->date_after, $this->date_before ) );
		}

		return $this->run( $orders );
	}

	/**
	 * Create zup
	 *
	 * @param array  $files Files array.
	 * @param string $destination destination path.
	 * @param array  $args arguments.
	 * @param mixed  $callback callback function.
	 */
	public function create_zip( $files = array(), $destination = '', $args = array(), $callback = null ) {
		$defaults = array(
			'overwrite'                => false,
			'remove_pdf_after_process' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$upload_dir = wp_upload_dir();
		$dest_path  = trailingslashit( path_join( $upload_dir['basedir'], 'wc-invoice-archives/' ) );
		$dest_url   = trailingslashit( path_join( $upload_dir['baseurl'], 'wc-invoice-archives/' ) );

		if ( file_exists( $dest_path . $destination ) && ! $args['overwrite'] ) {
			return $dest_url . $destination;
		}

		$dest_path .= $destination;
		$dest_url  .= $destination;

		$valid_files = array();

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					$valid_files[] = $file;
				}
			}
		}

		if ( count( $valid_files ) ) {
			$zip = new \PHPZip\Zip\File\Zip();
			$zip->setZipFile( $dest_path );

			foreach ( $valid_files as $file ) {
				$zip->addFile( file_get_contents( $file ), basename( $file ), filectime( $file ) ); // @codingStandardsIgnoreLine

				if ( true === $args['remove_pdf_after_process'] ) {
					@unlink( $file ); // @codingStandardsIgnoreLine
				}
			}

			$zip->finalize();
		}

		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $files );
		}

		if ( file_exists( $dest_path ) ) {
			return $dest_url;
		}

		return false;
	}

	/**
	 * Run the process
	 *
	 * @param array $orders Orders array.
	 */
	public function run( $orders ) {
		if ( class_exists( 'BEWPI_Invoice' ) ) {
			$run = $this->woocommerce_pdf_invoices_plugin( $orders );
		} elseif ( function_exists( 'wcpdf_get_document' ) ) {
			$run = $this->wc_pdf_invoices_packing_slips( $orders );
		}

		return $run;
	}

	/**
	 * Create invoices zip from orders
	 *
	 * @param array $orders Orders array.
	 */
	public function wc_pdf_invoices_plugin( $orders ) {
		$files_to_zip = array();

		foreach ( $orders as $order ) {
			$order_id     = $order->get_id();
			$invoice      = new BEWPI_Invoice( $order_id );
			$invoice_path = $invoice->get_full_path();

			if ( ! file_exists( $invoice_path ) ) {
				$files_to_zip[] = $invoice->generate();
			} else {
				$files_to_zip[] = $invoice_path;
			}
		}

		$archive_name = $this->get_archive_name();

		return $this->create_zip(
			$files_to_zip,
			$archive_name,
			array( 'overwrite' => true )
		);
	}

	/**
	 * Create invoices packing slips zip from orders
	 *
	 * @param array $orders Orders array.
	 */
	public function wc_pdf_invoices_packing_slips( $orders ) {
		$files_to_zip = array();

		foreach ( $orders as $order ) {
			$document = wcpdf_get_document( 'invoice', $order, true );
			if ( ! $document ) { // Something went wrong, continue trying with other documents.
				continue;
			}
			$uploaded_pdf = wp_upload_bits( $document->get_filename(), null, $document->get_pdf(), 'temp' );

			if ( ! empty( $uploaded_pdf ) && false === $uploaded_pdf['error'] ) {
				$files_to_zip[] = $uploaded_pdf['file'];
			}
		}

		$archive_name = $this->get_archive_name();

		return $this->create_zip(
			$files_to_zip,
			$archive_name,
			array(
				'overwrite'                => true,
				'remove_pdf_after_process' => true,
			),
			function ( $files_to_zip ) {
				if ( ! empty( $files_to_zip ) ) {
					$file_zip = $files_to_zip[0];
					@rmdir( dirname( $file_zip ) ); // @codingStandardsIgnoreLine
				}
			}
		);
	}

	/**
	 * Return archive name
	 *
	 * @return string
	 */
	public function get_archive_name() {
		return sprintf( 'Invoices__%s__%s.zip', $this->date_after, $this->date_before );
	}
}
