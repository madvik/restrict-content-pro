<?php

class RCP_EDD {

	private $user;
	private $member;

	public function __construct() {
		$this->user = wp_get_current_user();
		$this->member = new RCP_Member( $this->user->ID );

		add_filter( 'edd_can_purchase_download', array( $this, 'can_purchase' ), 10, 2 );
		add_filter( 'edd_purchase_download_form', array( $this, 'download_form' ), 10, 2 );
		add_filter( 'edd_file_download_has_access', array( $this, 'file_download_has_access' ), 10, 3 );
	}

	/**
	 * Restricts the ability to purchase a product if the user doesn't have access to it.
	 *
	 * @access public
	 * @since 2.7
	 */
	public function can_purchase( $can_purchase, $download ) {

		if ( ! $can_purchase || ! $this->member->can_access( $download->ID ) ) {
			$can_purchase = false;
		}

		return $can_purchase;
	}

	/**
	 * Overrides the purchase form if the user doesn't have access to the product.
	 *
	 * @access public
	 * @since 2.7
	 */
	public function download_form( $purchase_form, $args ) {

		if ( ! $this->member->can_access( $args['download_id'] ) ) {
			return '';
		}

		return $purchase_form;
	}

	/**
	 * Prevents downloading files if the member doesn't have access.
	 *
	 * @access public
	 * @since 2.7
	 */
	public function file_download_has_access( $has_access, $payment_id, $args ) {

		if ( ! $this->member->can_access( $args['download'] ) ) {
			$has_access = false;
		}

		return $has_access;
	}
}


function rcp_edd_init() {

	if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		return;
	}
	new RCP_EDD;
}
add_action( 'init', 'rcp_edd_init' );