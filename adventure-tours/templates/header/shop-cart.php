<?php
/**
 * Shopping cart icon rendring templated.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.4.0
 */

$woo_is_active = adventure_tours_check( 'woocommerce_active' );

// user login/logout/my account/signup icons
$user_links = array();

// possible values are: 'wp_login_signup', 'wp_login', 'woo_login_signup', 'woo_login', 'woo_account_only'.
$login_signup_mode = adventure_tours_get_option( 'show_header_login_signup_links_mode' );

if ( $login_signup_mode ) {
	$is_account_only_mode = 'woo_account_only' == $login_signup_mode;

	$link_icon_classes = array(
		'login' => 'fa fa-sign-in',
		'signup' => 'fa fa-user-plus',
		'logout' => 'fa fa-sign-out',
		'account' => 'fa fa-user',
	);

	$user_link_titles = array(
		'login' => esc_html__( 'Login', 'adventure-tours' ),
		'signup' => esc_html__( 'Signup', 'adventure-tours' ),
		'logout' => esc_html__( 'Logout', 'adventure-tours' ),
		'account' => esc_html__( 'My Account', 'adventure-tours' ),
	);

	$is_logged_in = is_user_logged_in();
	$account_page_link = $woo_is_active && ( $is_logged_in || 0 === strpos( $login_signup_mode, 'woo_' ) ) ? wc_get_page_permalink( 'myaccount' ) : null;
	if ( $account_page_link && $account_page_link == home_url() ) {
		$account_page_link = null;
	} else {
		if ( $is_logged_in || $is_account_only_mode ) {
			$account_page = get_post( wc_get_page_id( 'myaccount' ) );
			if ( $account_page && $account_page->post_title ) {
				$user_link_titles['account'] = get_the_title( $account_page );
			}
		}
	}

	if ( $is_account_only_mode ) {
		if ( $account_page_link ) {
			$user_links['account'] = $account_page_link;
		}
	} else {
		if ( $is_logged_in ) {
			if ( $account_page_link ) {
				$user_links['account'] = $account_page_link;
			}
			$user_links['logout'] = wp_logout_url();
		} else {
			switch( $login_signup_mode ) {
				case 'wp_login_signup':
					$user_links['signup'] = wp_registration_url();

				case 'wp_login':
					$user_links['login'] = wp_login_url();
					break;

				case 'woo_login_signup':
					if ( $account_page_link ) {
						$user_links['signup'] = $account_page_link;
					}

				case 'woo_login':
					if ( $account_page_link ) {
						$user_links['login'] = $account_page_link;
					}
					break;
			}
		}
	}
}

$render_shopping_cart_link = $woo_is_active && adventure_tours_get_option( 'show_header_shop_cart' );
if ( ! $render_shopping_cart_link && empty( $user_links ) ) {
	return;
}
?>
<div class="header__info__item header__info__item--delimiter header__info__item--shoping-cart">
<?php
	if ( $user_links ) {
		foreach( $user_links as $_link_code => $_url_address ) {
			printf( '<a href="%s" class="header__info__item__account-icon" title="%s"><i class="%s"></i></a>',
				esc_url( $_url_address ),
				isset( $user_link_titles[ $_link_code ] ) ? esc_attr( $user_link_titles[ $_link_code ] ) : '',
				isset( $link_icon_classes[ $_link_code ] ) ? $link_icon_classes[ $_link_code ] : ''
			);
		}
	}

	if ( $render_shopping_cart_link ) {
		$cart_qty = WC()->cart->get_cart_contents_count();
		printf( '<a href="%s"><i class="fa fa-shopping-cart"></i>%s</a>',
			wc_get_cart_url(),
			$cart_qty > 0 ? '(' . $cart_qty . ')' : ''
		);
	}
?>
</div>

