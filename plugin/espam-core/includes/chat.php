<?php
/**
 * Floating contact widget (bottom right, every page): a button that opens a small card with every
 * way to reach the company — Viber, WhatsApp, phone, email (each only when set in Settings → ESPAM PLUS)
 * and always the inquiry form. A short greeting bubble appears once per visit.
 * Styles live in the theme; behaviour in assets/fab.js.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		wp_register_script( 'espam-fab', ESPAM_URL . 'assets/fab.js', array(), ESPAM_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'espam-viber', ESPAM_URL . 'assets/viber.js', array(), ESPAM_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

/**
 * Contact channels for the widget: [key, label, sub-label, href, icon, new tab].
 */
function espam_fab_channels(): array {
	$out = array();
	foreach ( espam_chat_links() as $key => [ $label, $href ] ) {
		$out[] = array( $key, $label, 'viber' === $key ? 'Pišite nam na Viberu' : 'Pišite nam na WhatsAppu', $href, $key, 'whatsapp' === $key );
	}
	$phone = (string) espam_option( 'phone' );
	if ( $phone ) {
		$out[] = array( 'phone', 'Pozovite nas', $phone, 'tel:' . preg_replace( '/[^\d+]/', '', $phone ), 'phone-call', false );
	}
	$email = (string) espam_option( 'email' );
	if ( $email ) {
		$out[] = array( 'mail', 'Email', $email, 'mailto:' . $email, 'mail', false );
	}
	$out[] = array( 'form', 'Pošaljite upit', 'Lista proizvoda i količina', espam_inquiry_url(), 'file-invoice', false );
	return $out;
}

add_action(
	'wp_footer',
	static function () {
		if ( ! (int) espam_option( 'chat_fab' ) || is_page( 'kontakt' ) ) {
			return;
		}
		$items = '';
		foreach ( espam_fab_channels() as $i => [ $key, $label, $sub, $href, $icon, $blank ] ) {
			$items .= sprintf(
				'<li style="--i:%1$d"><a class="ep-fab__item ep-fab__item--%2$s" href="%3$s"%4$s><span class="ep-fab__item-icon">%5$s</span><span class="ep-fab__item-text"><strong>%6$s</strong><span>%7$s</span></span>%8$s</a></li>',
				(int) $i,
				esc_attr( $key ),
				esc_url( $href, array( 'https', 'http', 'viber', 'tel', 'mailto' ) ),
				( $blank ? ' target="_blank" rel="noopener"' : '' ) . ( 'viber' === $key ? espam_viber_attrs() : '' ),
				espam_icon( $icon, 22 ),
				esc_html( $label ),
				esc_html( $sub ),
				espam_icon( 'chevron-right', 18 )
			);
		}
		$hours = (string) espam_option( 'hours' );
		$foot  = $hours ? '<p class="ep-fab__foot">' . espam_icon( 'clock', 16 ) . '<span>' . esc_html( str_replace( "\n", ' · ', $hours ) ) . '</span></p>' : '';
		wp_enqueue_script( 'espam-fab' );
		printf(
			'<div class="ep-fab">'
			. '<div class="ep-fab__bubble" hidden><button type="button" class="ep-fab__bubble-close" aria-label="Zatvori poruku">%1$s</button><p><strong>Trebate ponudu?</strong> Pošaljite nam listu proizvoda — javljamo se sa cijenom i rokom isporuke.</p></div>'
			. '<div class="ep-fab__panel" id="ep-fab-panel" role="dialog" aria-label="Kontaktirajte ESPAM PLUS">'
			. '<div class="ep-fab__head"><span class="ep-fab__logo">%2$s</span><span><strong>ESPAM PLUS</strong><span>Veleprodaja, Berane</span></span></div>'
			. '<p class="ep-fab__intro">Recite nam šta vam treba — odgovaramo sa ponudom.</p>'
			. '<ul class="ep-fab__list">%3$s</ul>%4$s</div>'
			. '<button type="button" class="ep-fab__toggle" aria-expanded="false" aria-controls="ep-fab-panel"><span class="ep-fab__toggle-icons">%5$s%6$s</span><span class="ep-fab__label">Kontakt</span></button>'
			. '</div>' . "\n",
			espam_icon( 'x', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput
			espam_logo_svg( 'logo-mark.svg' ), // phpcs:ignore WordPress.Security.EscapeOutput
			$items, // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
			$foot, // phpcs:ignore WordPress.Security.EscapeOutput
			str_replace( 'class="ep-icon"', 'class="ep-icon ep-fab__open"', espam_icon( 'message-circle', 24 ) ), // phpcs:ignore WordPress.Security.EscapeOutput
			str_replace( 'class="ep-icon"', 'class="ep-icon ep-fab__close"', espam_icon( 'x', 22 ) ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	},
	5
);
