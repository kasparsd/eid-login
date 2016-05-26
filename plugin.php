<?php
/*
Plugin Name: eID Login
Plugin URI: https://github.com/kasparsd/eid-login
GitHub URI: https://github.com/kasparsd/eid-login
Description: Login with eID cards.
Version: 0.1
Author: Kaspars Dambis
Author URI: http://kaspars.net
*/


// Go!
eid_login::instance()->init();


class eid_login {


	protected function __construct() {

		return $this;

	}

	public function instance() {

		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new self();
		}

		return $instance;

	}

	public function init() {

		add_action( 'login_init', array( $this, 'login_ssl' ) );

		add_filter( 'user_contactmethods', array( $this, 'admin_user_fingerprint' ) );

		add_action( 'profile_update', array( $this, 'admin_save_user_fingerprint' ) );

		add_action( 'login_form', array( $this, 'login_form_link' ) );

		add_action( 'admin_init', array( $this, 'admin_eid_login_settings_register' ) );

	}

	function login_ssl() {

		// This relies on the server providing these variables
		$fingerprint = getenv( 'SSL_CLIENT_FINGERPRINT' );
		$verify = getenv( 'SSL_CLIENT_VERIFY' );

		// Client verification not attempted
		if ( empty( $verify ) ) {
			return;
		}

		// Client failed to present a certificate
		if ( 'NONE' === $verify ) {
			wp_die(
				__( 'Failed to read your eID client certificate.', 'eid-login' ),
				null,
				array(
					'response' => 401,
					'back_link' => true,
				)
			);
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		// Ensure that we have the COOKIE_DOMAIN set or else the
		// subdomain won't be able to set the login cookies for the top level domain
		if ( ! defined( 'COOKIE_DOMAIN' ) || empty( COOKIE_DOMAIN ) || '.' !== substr( COOKIE_DOMAIN, 0, 1 ) ) {
			wp_die( sprintf(
				'eID login requires <code>COOKIE_DOMAIN</code> to be set to <code>.%s</code>',
				parse_url( site_url(), PHP_URL_HOST )
			) );
		}

		if ( 'SUCCESS' !== $verify ) {
			return;
		}

		if ( empty( $fingerprint ) ) {
			wp_die(
				__( 'Empty eID client certificate fingerprint.', 'eid-login' ),
				null,
				array(
					'response' => 401,
					'back_link' => true,
				)
			);
		}

		// Ensure the same fingerprint format -- all lowercase, no columns, etc.
		$fingerprint = $this->sanitize_fingerprint( $fingerprint );

		// Search for users by the fingerprint
		$fingerprint_users = get_users( array(
			'meta_key' => 'cert_fingerprint',
			'meta_value' => $fingerprint,
		) );

		if ( ! is_wp_error( $fingerprint_users ) && isset( $fingerprint_users[0]->ID ) ) {
			wp_set_auth_cookie( $fingerprint_users[0]->ID );
			wp_safe_redirect( admin_url() );
			exit;
		}

		wp_die(
			__( 'Failed to find a user with that fingerprint.', 'eid-login' ),
			null,
			array(
				'response' => 401,
				'back_link' => true,
			)
		);

	}


	// @todo Create our own settings section
	public function admin_user_fingerprint( $fields ) {

		$fields['cert_fingerprint'] = __( 'eID Certificate Fingerprint', 'eid-login' );

		return $fields;

	}


	public function admin_save_user_fingerprint( $user_id ) {

		// Note that WP already takes care for checking for permissions
		if ( ! isset( $_POST['cert_fingerprint'] ) ) {
			return;
		}

		$fingerprint = $this->sanitize_fingerprint( $_POST['cert_fingerprint'] );

		update_user_meta( $user_id, 'cert_fingerprint', $fingerprint );

	}


	public function sanitize_fingerprint( $fingerprint ) {

		$fingerprint = sanitize_text_field( $fingerprint );
		$fingerprint = strtolower( $fingerprint );
		$fingerprint = preg_replace( "/[^a-z0-9]+/", '', $fingerprint );

		return $fingerprint;

	}


	public function login_form_link() {

		$url = get_option( 'eid_login_url' );

		// Ensure that a valid login URL is present
		if ( empty( $url ) || false === stripos( $url, 'https' ) ) {
			return;
		}

		printf(
			'<p>
				<a href="%s">%s</a>
				<br/><br/>
			</p>',
			esc_url( $url ),
			__( 'Login with eID', 'eid-login' )
		);

	}


	public function admin_eid_login_settings_register() {

		if ( ! current_user_can( 'manage_options' ) )
			return;

		register_setting( 'general', 'eid_login_url', 'esc_url' );

		add_settings_field(
			'eid_login_settings',
			__( 'eID Login', 'eid-login' ),
			array( $this, 'admin_eid_login_settings' ),
			'general',
			'default'
		);

	}


	public function admin_eid_login_settings() {

		$url = get_option( 'eid_login_url' );
		$url_placeholder = wp_login_url();

		?>
		<p>
			<input type="url" name="eid_login_url" value="<?php echo esc_attr( $url ) ?>" class="large-text" placeholder="<?php echo esc_attr( $url_placeholder ); ?>" />
		</p>
		<p class="description">
			<?php esc_html_e( 'URL of the server that is configured to perform the client certificate validation.', 'eid-login' ); ?>
		</p>
		<?php

	}

}
