<?php
/**
 * Settings → ESPAM PLUS: company data shown in the footer and on the contact page, Viber/WhatsApp.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Editable fields: key => [label, type, hint].
 */
function espam_setting_fields(): array {
	return array(
		'company'    => array( 'Naziv firme', 'text', '' ),
		'address'    => array( 'Adresa', 'text', '' ),
		'city'       => array( 'Mjesto', 'text', 'Npr. 84300 Berane, Crna Gora' ),
		'pib'        => array( 'PIB', 'text', '' ),
		'mb'         => array( 'Matični broj', 'text', '' ),
		'phone'      => array( 'Telefon', 'text', 'Kako se prikazuje na sajtu, npr. +382 51 123 456' ),
		'email'      => array( 'Email', 'email', 'Na ovu adresu stižu upiti sa sajta.' ),
		'hours'      => array( 'Radno vrijeme', 'textarea', 'Svaki red posebno, npr. „Pon–Pet: 08–16h"' ),
		'chat_phone' => array( 'Viber / WhatsApp broj', 'text', 'Međunarodni format, samo cifre, npr. 38267123456. Prazno = dugmad se ne prikazuju.' ),
		'map_query'  => array( 'Lokacija za mapu', 'text', 'Adresa ili koordinate za Google mapu na stranici Kontakt.' ),
	);
}

add_action(
	'admin_menu',
	static function () {
		add_options_page( 'ESPAM PLUS', 'ESPAM PLUS', 'manage_options', 'espam', 'espam_settings_page' );
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'espam',
			'espam_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'espam_sanitize_options',
				'default'           => array(),
			)
		);
	}
);

// Company data is printed on every page (footer, contact widget, Kontakt), so cached pages must be refreshed.
add_action(
	'update_option_espam_options',
	static function () {
		do_action( 'litespeed_purge_all' );
	}
);

function espam_sanitize_options( $input ): array {
	$input = (array) $input;
	$out   = array();
	foreach ( espam_setting_fields() as $key => [ , $type ] ) {
		$raw         = (string) ( $input[ $key ] ?? '' );
		$out[ $key ] = match ( $type ) {
			'email'    => sanitize_email( $raw ),
			'textarea' => sanitize_textarea_field( $raw ),
			default    => sanitize_text_field( $raw ),
		};
	}
	$out['chat_phone'] = preg_replace( '/\D+/', '', $out['chat_phone'] );
	$out['chat_fab']   = empty( $input['chat_fab'] ) ? 0 : 1;
	return $out;
}

function espam_settings_page(): void {
	$opts = (array) get_option( 'espam_options', array() );
	?>
	<div class="wrap">
		<h1>ESPAM PLUS</h1>
		<?php if ( isset( $_GET['setup'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p>Postavljanje je završeno.</p></div>
		<?php endif; ?>
		<p>Podaci firme prikazuju se u podnožju i na stranici Kontakt. Prazna polja se ne prikazuju.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'espam' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( espam_setting_fields() as $key => [ $label, $type, $hint ] ) : ?>
					<?php $value = (string) ( $opts[ $key ] ?? espam_option( $key ) ); ?>
					<tr>
						<th scope="row"><label for="espam-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( 'textarea' === $type ) : ?>
								<textarea class="regular-text" rows="3" id="espam-<?php echo esc_attr( $key ); ?>" name="espam_options[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
							<?php else : ?>
								<input type="<?php echo esc_attr( $type ); ?>" class="regular-text" id="espam-<?php echo esc_attr( $key ); ?>" name="espam_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
							<?php endif; ?>
							<?php if ( $hint ) : ?>
								<p class="description"><?php echo esc_html( $hint ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row">Plutajuće dugme</th>
					<td><label><input type="checkbox" name="espam_options[chat_fab]" value="1" <?php checked( (int) espam_option( 'chat_fab' ), 1 ); ?>> Prikaži Viber/WhatsApp dugme u donjem desnom uglu</label></td>
				</tr>
			</table>
			<?php submit_button( 'Sačuvaj' ); ?>
		</form>

		<h2>Postavljanje</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'espam_setup' ); ?>
			<input type="hidden" name="action" value="espam_setup">
			<?php submit_button( 'Pokreni postavljanje (stranice, forma za upit, ikona sajta)', 'secondary', 'submit', false ); ?>
			<p class="description">Kreira samo ono što nedostaje — postojeći sadržaj se ne mijenja.</p>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_espam_setup',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemate dozvolu.' );
		}
		check_admin_referer( 'espam_setup' );
		espam_run_setup();
		flush_rewrite_rules();
		wp_safe_redirect( admin_url( 'options-general.php?page=espam&setup=1' ) );
		exit;
	}
);

// Remind the admin to fill in the data the contact page needs.
add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'settings_page_espam' === $screen->id ) {
			return;
		}
		$opts    = (array) get_option( 'espam_options', array() );
		$missing = array();
		foreach ( array( 'pib' => 'PIB', 'mb' => 'matični broj', 'phone' => 'telefon', 'email' => 'email', 'hours' => 'radno vrijeme' ) as $key => $label ) {
			if ( empty( $opts[ $key ] ) ) {
				$missing[] = $label;
			}
		}
		if ( $missing ) {
			printf(
				'<div class="notice notice-warning"><p><strong>ESPAM PLUS:</strong> upišite %s. <a href="%s">Podešavanja → ESPAM PLUS</a></p></div>',
				esc_html( implode( ', ', $missing ) ),
				esc_url( admin_url( 'options-general.php?page=espam' ) )
			);
		}
	}
);
