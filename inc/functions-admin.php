<?php
/**
 * Admin functionality
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'qrlite_add_plugin_action_links' );
function qrlite_add_plugin_action_links($links){
    $links['go_qrmenudocs'] = '<a href="https://docs.modeltheme.com/qrmenu/" target="_blank" style="color: #00a32a;">'.__('Docs', 'meeek').'</a>';
    $links['go_qrmenusaas'] = '<a href="https://modeltheme.com/go/qrmenu-saas/" target="_blank" style="color: #F7345E; font-weight: 700;">'.__('Upgrade to QRMenu SaaS', 'meeek').'</a>';

    return $links;
}

if ( get_option( 'qrlite_dismiss_notice_key' ) !== 'yes' ) {
	 add_action( 'admin_notices', 'qrlite_admin_notice');
	 add_action( 'wp_ajax_qrlite_dismiss_welcome_notice', 'qrlite_remove_notice');
}


function qrlite_admin_notice() {
	if ( apply_filters( 'qrlite_disable_starter_sites_admin_notice', false ) === true ) {
		return;
	}
	if ( defined( 'TI_ONBOARDING_DISABLED' ) && TI_ONBOARDING_DISABLED === true ) {
		return;
	}

	$current_screen = get_current_screen();
	if ( $current_screen->id !== 'dashboard' && $current_screen->id !== 'themes' ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( is_network_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// to check above the gutenberg v5.5.0 (is_gutenberg_page is deprecated with )
	if ( method_exists( $current_screen, 'is_block_editor' ) ) {
		if ( $current_screen->is_block_editor() ) {
			return;
		}
	}

	/**
	 * Backwards compatibility.
	 */
	global $current_user;
	$user_id          = $current_user->ID;
	$dismissed_notice = get_user_meta( $user_id, 'qrlite_dismiss_notice_key', true );

	if ( $dismissed_notice === 'dismissed' ) {
		update_option( 'qrlite_dismiss_notice_key', 'yes' );
	}

	if ( get_option( 'qrlite_dismiss_notice_key', 'no' ) === 'yes' ) {
		return;
	}

	// Let's dismiss the notice if the user sees it for more than 1 week.
	$activated_time = get_option( 'qrlite_install' );

	if ( ! empty( $activated_time ) ) {
		if ( time() - intval( $activated_time ) > WEEK_IN_SECONDS ) {
			update_option( 'qrlite_dismiss_notice_key', 'yes' );

			return;
		}
	}

	$style = '
		.ti-about-notice{
			position: relative;
		}

		.ti-about-notice .notice-dismiss{
			position: absolute;
			z-index: 10;
		    top: 10px;
		    right: 10px;
		    padding: 10px 15px 10px 21px;
		    font-size: 13px;
		    line-height: 1.23076923;
		    text-decoration: none;
		}

		.ti-about-notice .notice-dismiss:before{
		    position: absolute;
		    top: 8px;
		    left: 0;
		    transition: all .1s ease-in-out;
		    background: none;
		}

		.ti-about-notice .notice-dismiss:hover{
			color: #00a0d2;
		}
	';

	echo '<style>' . wp_kses_post( $style ) . '</style>';
	qrlite_dismiss_notice_script();
	echo '<div class="qrlite-welcome-notice updated notice ti-about-notice">';
	echo '<div class="notice-dismiss"></div>';
	qrlite_welcome_notice_content();
	echo '</div>';
}

/**
 * Render welcome notice content
 */
 function qrlite_welcome_notice_content() {
	$name       = wp_get_theme()->__get( 'Name' );
	$template   = wp_get_theme()->get( 'Template' );
	$slug       = wp_get_theme()->__get( 'stylesheet' );
	$theme_page = ! empty( $template ) ? $template . '-welcome' : $slug . '-welcome';

	$notice_template = '
		<div class="qrlite-notice-wrapper">
		%1$s
			<div class="qrlite-notice-column-container">
				<div class="qrlite-notice-column qrlite-notice-image">%2$s</div>
				<div class="qrlite-notice-column qrlite-notice-starter-sites">%3$s</div>
			</div>
		</div>
		<style>%4$s</style>';

	/* translators: 1 - notice title, 2 - notice message */
	$notice_header = sprintf(
		'<h2>%1$s</h2><br><p class="about-description">%2$s</p></hr>',
		esc_html__( 'Monetize your website with QRMenu SaaS!', 'qrlite' ),
		esc_html__( 'The QRMenu – Restaurant QR Menu Generator (SaaS) plugin is a specialized online menu Software as a Service (SaaS) solution designed for businesses in the hospitality and foodservice industry, specifically tailored for use with WordPress websites. This platform offers a user-friendly and customizable way for restaurants, cafes, bars, and other food establishments to create and manage digital menus on their websites.', 'qrlite' )
	);


	$link_cc = 'https://modeltheme.com/go/qrmenu-saas/';
	$link_docs = 'https://docs.modeltheme.com/qrmenu/#license';
	$link_testdrive = 'https://qrmenu.modeltheme.com/my-account';
	$link_preview_image = QRLITE_PLUGIN_URL.'assets/images/qrmenu-saas.jpg';

	$upgrade_btn = sprintf(
		'<a href="%1$s" target="_blank" class="button button-primary button-hero install-now" >%2$s</a>',
		esc_url( $link_cc ),
		esc_html__( 'Upgrade to QRMenu SaaS', 'qrlite' )
	);

	$notice_picture    = sprintf(
		'<picture>
				<source srcset="about:blank" media="(max-width: 1024px)">
				<a href="%2$s" target="_blank"><img src="%1$s" alt="qrlite SaaS" /></a>
			</picture>',
		esc_url( $link_preview_image ),
		esc_url( $link_cc )
	);
	$notice_sites_list = sprintf(
		'<div>
			<h3>%1$s</h3>
			<ul>
				<li>%2$s</li>
				<li>%3$s</li>
				<li>%4$s</li>
				<li>%5$s</li>
			</ul>
			<p>%8$s</p>
			<p>
				<a target="_blank" rel="external noopener noreferrer" href="%7$s">
					<i class="dashicons dashicons-external"></i>%6$s
				</a> 
				<a target="_blank" rel="external noopener noreferrer" href="%10$s">
					<i class="dashicons dashicons-admin-links"></i>%9$s
				</a>
			</p>
		</div>',
		esc_html__( 'Key Features', 'qrlite' ),
		esc_html__('- Monetization: Restaurant Paid access to create & share menus (Extended License).','qrlite'),
		esc_html__('- Prezentation: Build your Restaurant Menu (with the built-in builder).','qrlite'),
		esc_html__('- Landing Page for a online menu used by Restaurants & HoReCa business.','qrlite'),
		esc_html__('- Video Tutorials, Dedicated help team and constant updates.','qrlite'),
		esc_html__( 'Regular vs Extended License', 'qrlite' ),
		esc_url( $link_docs ),
		$upgrade_btn,
		esc_html__( 'Test Drive', 'qrlite' ),
		$link_testdrive
	);
	$style = '
	.qrlite-notice-wrapper h2{
		margin: 0;
		font-size: 21px;
		font-weight: 400;
		line-height: 1.2;
	}
	.qrlite-notice-column.qrlite-notice-starter-sites i, .qrlite-notice-column.qrlite-notice-starter-sites a {
	    text-decoration: none;
	}
	.qrlite-notice-wrapper p.about-description{
		color: #72777c;
		font-size: 16px;
		margin: 0;
		padding:0px;
	}
	.qrlite-notice-wrapper{
		padding: 23px 10px 0;
		max-width: 1500px;
	}
	.qrlite-notice-wrapper hr {
		margin: 20px -23px 0;
		border-top: 1px solid #f3f4f5;
		border-bottom: none;
	}
	.qrlite-notice-column-container h3{
		margin: 17px 0 0;
		font-size: 16px;
		line-height: 1.4;
	}
	.qrlite-notice-column-container p {
		color: #72777c;
	}
	.qrlite-notice-text p.ti-return-dashboard {
		margin-top: 30px;
	}
	.qrlite-notice-column-container .qrlite-notice-column{
		 padding-right: 40px;
	}
	.qrlite-notice-column-container img{
		margin-top: 23px;
		width: calc(100% - 40px);
		border: 1px solid #f3f4f5;
	}
	.qrlite-notice-column-container {
		display: -ms-grid;
		display: grid;
		-ms-grid-columns: 45% 45%;
		grid-template-columns: 45% 45%;
		margin-bottom: 13px;
	}
	.qrlite-notice-column-container a.button.button-hero.button-secondary,
	.qrlite-notice-column-container a.button.button-hero.button-primary{
		margin:0px;
	}
	.qrlite-notice-column-container .qrlite-notice-column:not(.qrlite-notice-image) {
		display: -ms-grid;
		display: grid;
		-ms-grid-rows: auto 50px;
		grid-template-rows: auto 50px;
	}
	@media screen and (max-width: 1280px) {
		.qrlite-notice-wrapper .qrlite-notice-column-container {
			-ms-grid-columns: 50% 50%;
			grid-template-columns: 50% 50%;
		}
		.qrlite-notice-column-container a.button.button-hero.button-secondary,
		.qrlite-notice-column-container a.button.button-hero.button-primary{
			padding:6px 18px;
		}
		.qrlite-notice-wrapper .qrlite-notice-image {
			display: none;
		}
	}
	@media screen and (max-width: 870px) {

		.qrlite-notice-wrapper .qrlite-notice-column-container {
			-ms-grid-columns: 100%;
			grid-template-columns: 100%;
		}
		.qrlite-notice-column-container a.button.button-hero.button-primary{
			padding:12px 36px;
		}
	}
	@-webkit-keyframes spin {
		from {
			transform: rotate(0deg);
		}
		to {
			transform: rotate(360deg);
		}
	}
	#qrlite-ss-install button.is-loading {
		color: #828282 !important;
	}
	#qrlite-ss-install button.is-loading .dashicon {
		color: #646D82;
		animation-name: spin;
		animation-duration: 2000ms;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
	}
	';

	echo sprintf(
		$notice_template, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$notice_header, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$notice_picture, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$notice_sites_list, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$style // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}




/**
 * Dismiss notice JS
 */
 function qrlite_dismiss_notice_script() {
	?>
	<script type="text/javascript">
		function qrliteHandleNoticeActions($) {
			var actions = $('.qrlite-welcome-notice').find('.notice-dismiss, .ti-return-dashboard, .options-page-btn')
			$.each(actions, function (index, actionButton) {
				$(actionButton).on('click', function (e) {
					e.preventDefault()
					var redirect = $(this).attr('href')
					$.post(
						'<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						{
							nonce: '<?php echo esc_attr( wp_create_nonce( 'remove_notice_confirmation' ) ); ?>',
							action: 'qrlite_dismiss_welcome_notice',
							success: function () {
								if (typeof redirect !== 'undefined' && window.location.href !== redirect) {
									window.location = redirect
									return false
								}
								$('.qrlite-welcome-notice').fadeOut()
							}
						}
					)
				})
			})
		}

		jQuery(document).ready(function () {
			qrliteHandleNoticeActions(jQuery)
		})
	</script>
	<?php
}


/**
 * Remove notice;
 */
 function qrlite_remove_notice() {
	if ( ! isset( $_POST['nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'remove_notice_confirmation' ) ) {
		return;
	}
	update_option( 'qrlite_dismiss_notice_key', 'yes' );
	wp_die();
}

