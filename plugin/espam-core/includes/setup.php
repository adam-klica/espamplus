<?php
/**
 * One-click setup (plugin activation + Settings → ESPAM PLUS → "Pokreni postavljanje").
 * Idempotent: creates only what is missing, never overwrites existing content.
 */

defined( 'ABSPATH' ) || exit;

function espam_run_setup(): array {
	$log = array();

	// Pages. Their layout comes from the theme templates page-{slug}.html, so content stays empty.
	foreach ( espam_pages() as $slug => $title ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		$content = 'politika-privatnosti' === $slug ? espam_privacy_content() : '';
		wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ) );
		$log[] = "Stranica: {$title}";
	}
	$front = get_page_by_path( 'pocetna' );
	if ( $front && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front->ID );
		$log[] = 'Početna stranica postavljena.';
	}
	$privacy = get_page_by_path( 'politika-privatnosti' );
	if ( $privacy && ! get_option( 'wp_page_for_privacy_policy' ) ) {
		update_option( 'wp_page_for_privacy_policy', $privacy->ID );
	}
	$sample = get_page_by_path( 'sample-page' ) ?: get_page_by_path( 'primjer-stranice' );
	if ( $sample && $sample->post_modified === $sample->post_date ) {
		wp_trash_post( $sample->ID );
	}
	$hello = get_post( 1 );
	if ( $hello && 'post' === $hello->post_type && $hello->post_modified === $hello->post_date ) {
		wp_trash_post( 1 );
	}

	// Inquiry form.
	if ( espam_form_id() ) {
		$log[] = 'Forma za upit postoji.';
	} elseif ( class_exists( 'WPCF7_ContactForm' ) ) {
		espam_create_form();
		$log[] = 'Forma za upit kreirana.';
	}

	// Brand images → site icon + default share image (Yoast).
	$icon_id = espam_import_brand_image( 'site-icon-512.png', 'ESPAM PLUS — ikona' );
	if ( $icon_id && ! get_option( 'site_icon' ) ) {
		update_option( 'site_icon', $icon_id );
		$log[] = 'Ikona sajta postavljena.';
	}
	$og_id = espam_import_brand_image( 'og-default-1200x630.jpg', 'ESPAM PLUS — slika za dijeljenje' );
	if ( $og_id && defined( 'WPSEO_VERSION' ) ) {
		$social = (array) get_option( 'wpseo_social', array() );
		if ( empty( $social['og_default_image'] ) ) {
			$social['og_default_image']    = wp_get_attachment_url( $og_id );
			$social['og_default_image_id'] = $og_id;
			update_option( 'wpseo_social', $social );
		}
		$titles = (array) get_option( 'wpseo_titles', array() );
		if ( empty( $titles['company_name'] ) ) {
			$titles['company_or_person'] = 'company';
			$titles['company_name']      = (string) espam_option( 'company' );
			$titles['company_logo']      = wp_get_attachment_url( $icon_id );
			$titles['company_logo_id']   = $icon_id;
			update_option( 'wpseo_titles', $titles );
		}
	}

	// LiteSpeed Cache (Hostinger): safe defaults, only where the value is still LiteSpeed's factory default.
	if ( class_exists( '\LiteSpeed\Conf' ) && method_exists( '\LiteSpeed\Conf', 'cls' ) ) {
		$want    = array(
			'cache-ttl_pub'           => array( 604800, 86400 ),
			'cache-browser'           => array( 0, 1 ),
			'purge-upgrade'           => array( 0, 1 ),
			'optm-css_min'            => array( 0, 1 ),
			'optm-js_min'             => array( 0, 1 ),
			'optm-html_min'           => array( 0, 1 ),
			'optm-emoji_rm'           => array( 0, 1 ),
			'media-add_missing_sizes' => array( 0, 1 ),
		);
		$changes = array();
		foreach ( $want as $key => [ $default, $value ] ) {
			$current = get_option( "litespeed.conf.{$key}" );
			if ( false !== $current && (int) $current === $default ) {
				$changes[ $key ] = $value;
			}
		}
		if ( $changes ) {
			\LiteSpeed\Conf::cls()->update_confs( $changes );
			$log[] = 'LiteSpeed Cache podešen.';
		}
	}

	update_option( 'espam_setup_log', array( 'time' => time(), 'log' => $log ) );
	return $log;
}

function espam_form_id(): int {
	$id = (int) get_option( 'espam_form_id' );
	return $id && 'wpcf7_contact_form' === get_post_type( $id ) ? $id : 0;
}

/**
 * "Zatražite ponudu" form. The category select is preselected from ?kategorija= (links on the Asortiman page).
 */
function espam_create_form(): void {
	$form = WPCF7_ContactForm::get_template( array( 'title' => 'Zatražite ponudu' ) );
	$form->set_properties(
		array(
			'form'     => espam_form_body(),
			'mail'     => array(
				'active'             => true,
				'subject'            => 'Novi upit sa sajta: [kategorija]',
				'sender'             => '[_site_title] <wordpress@[_site_domain]>',
				'recipient'          => '[_site_admin_email]',
				'body'               => espam_inquiry_mail_body(),
				'additional_headers' => 'Reply-To: [email]',
				'attachments'        => '[prilog]',
				'use_html'           => true,
				'exclude_blank'      => true,
			),
			'messages' => array_merge(
				wpcf7_messages() ? array_map( static fn( $m ) => $m['default'], wpcf7_messages() ) : array(),
				array(
					'mail_sent_ok'     => 'Hvala! Vaš zahtjev za ponudu je uspješno poslat. Potvrdu smo poslali na vaš email — javićemo vam se sa ponudom.',
					'mail_sent_ng'     => 'Upit nije poslat. Pokušajte ponovo ili nas pozovite telefonom.',
					'validation_error' => 'Provjerite označena polja i pokušajte ponovo.',
					'invalid_required' => 'Ovo polje je obavezno.',
					'accept_terms'     => 'Potrebna je vaša saglasnost.',
					'invalid_email'    => 'Upišite ispravnu email adresu.',
				),
				espam_upload_messages()
			),
		)
	);
	$form->save();
	update_option( 'espam_form_id', (int) $form->id() );
}

function espam_form_body(): string {
	$cats  = implode( ' ', array_map( static fn( $c ) => '"' . $c[0] . '"', espam_categories() ) );
	$body  = '<div class="ep-form__grid">' . "\n";
	$body .= '<p><label>Ime i prezime *[text* ime autocomplete:name]</label></p>' . "\n";
	$body .= '<p><label>Firma[text firma autocomplete:organization]</label></p>' . "\n";
	$body .= '<p><label>Email *[email* email autocomplete:email]</label></p>' . "\n";
	$body .= '<p><label>Telefon[tel telefon autocomplete:tel]</label></p>' . "\n";
	$body .= "</div>\n";
	$body .= "<p><label>Kategorija[select kategorija first_as_label default:get \"Izaberite kategoriju\" {$cats}]</label></p>\n";
	$body .= '<p><label>Proizvodi i količine *[textarea* poruka placeholder "Npr. 10 laptopova 15,6 inča, 16 GB RAM; 5 multifunkcijskih štampača; rok isporuke…"]</label></p>' . "\n";
	$body .= '<p class="ep-form__file"><label>Prilog — nije obavezan (specifikacija, spisak proizvoda…)[file ' . ESPAM_UPLOAD_FIELD . ' limit:10mb filetypes:' . ESPAM_UPLOAD_FILETYPES . ']</label><span class="ep-form__hint">PDF, Word (.docx), Excel (.xlsx), TXT ili slika (JPG, PNG), do 10 MB.</span></p>' . "\n";
	$body .= '<p class="ep-form__consent">[acceptance saglasnost] Saglasan/na sam da ESPAM PLUS koristi moje podatke kako bi mi poslao ponudu. [/acceptance]</p>' . "\n";
	$body .= '<p>[submit "Pošaljite upit"]</p>';
	return $body;
}

function espam_upload_messages(): array {
	return array(
		'upload_failed'            => 'Prilog nije otpremljen. Pokušajte ponovo.',
		'upload_file_type_invalid' => 'Ova vrsta fajla nije dozvoljena. Pošaljite PDF, Word (.docx), Excel (.xlsx), TXT ili sliku (JPG, PNG).',
		'upload_file_too_large'    => 'Prilog je veći od 10 MB.',
		'upload_failed_php_error'  => 'Prilog nije otpremljen. Pokušajte ponovo.',
		'spam'                     => 'Previše upita u kratkom roku. Pokušajte ponovo kasnije ili nas pozovite telefonom.',
	);
}

// The live form was created before the attachment field existed: always render the current fields and messages.
add_filter(
	'wpcf7_contact_form_property_form',
	static fn( $body, $form ) => $form && (int) $form->id() === espam_form_id() ? espam_form_body() : $body,
	10,
	2
);
add_filter(
	'wpcf7_contact_form_property_messages',
	static fn( $messages, $form ) => $form && (int) $form->id() === espam_form_id() ? array_merge( (array) $messages, espam_upload_messages() ) : $messages,
	10,
	2
);

/**
 * Branded HTML body for the inquiry email. CF7 mail-tags; in HTML mode CF7 escapes the submitted values.
 * One table row per line so "exclude_blank" drops rows of empty optional fields (firma, telefon, kategorija).
 */
function espam_inquiry_mail_body(): string {
	$logo  = esc_url( ESPAM_URL . 'assets/logo/site-icon-512.png' );
	$font  = "font-family:'Plus Jakarta Sans',Segoe UI,Helvetica,Arial,sans-serif;";
	$label = 'padding:10px 0;width:120px;color:#5B6B80;font-size:13px;vertical-align:top;border-bottom:1px solid #E3EAF3;';
	$value = 'padding:10px 0;color:#13243A;font-size:15px;font-weight:600;border-bottom:1px solid #E3EAF3;';
	$row   = static fn( string $l, string $v ) => "<tr><td style=\"{$label}\">{$l}</td><td style=\"{$value}\">{$v}</td></tr>\n";

	return '<!doctype html>
<html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Novi upit</title></head>
<body style="margin:0;padding:0;background:#EEF3F9;' . $font . '">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF3F9;padding:24px 12px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(11,58,102,.08);">
<tr><td style="background:#0B3A66;padding:22px 28px;">
<table role="presentation" cellpadding="0" cellspacing="0"><tr>
<td style="vertical-align:middle;padding-right:12px;"><img src="' . $logo . '" width="40" height="40" alt="ESPAM PLUS" style="display:block;border-radius:10px;border:0;"></td>
<td style="vertical-align:middle;color:#FFFFFF;font-size:18px;font-weight:800;letter-spacing:.04em;' . $font . '">ESPAM PLUS<br><span style="font-size:12px;font-weight:500;letter-spacing:0;color:#B9D3EE;">Veleprodaja · Berane</span></td>
</tr></table></td></tr>
<tr><td style="height:4px;background:#0A65B8;line-height:4px;font-size:0;">&nbsp;</td></tr>
<tr><td style="padding:28px 28px 8px;">
<p style="margin:0 0 6px;color:#0A65B8;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Novi upit sa sajta</p>
<h1 style="margin:0 0 6px;color:#13243A;font-size:22px;line-height:1.3;font-weight:800;">[ime] traži ponudu</h1>
<p style="margin:0;color:#5B6B80;font-size:13px;">Primljeno [_date] u [_time]</p>
</td></tr>
<tr><td style="padding:16px 28px 4px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $font . '">
' . $row( 'Ime i prezime', '[ime]' )
		. $row( 'Firma', '[firma]' )
		. $row( 'Email', '<a href="mailto:[email]" style="color:#0A65B8;text-decoration:none;">[email]</a>' )
		. $row( 'Telefon', '<a href="tel:[telefon]" style="color:#0A65B8;text-decoration:none;">[telefon]</a>' )
		. $row( 'Kategorija', '[kategorija]' )
		. $row( 'Prilog', '[prilog] (u prilogu emaila)' ) . '</table>
</td></tr>
<tr><td style="padding:20px 28px 4px;">
<p style="margin:0 0 8px;color:#5B6B80;font-size:13px;">Proizvodi i količine</p>
<div style="background:#F7F9FC;border:1px solid #E3EAF3;border-left:4px solid #0A65B8;border-radius:10px;padding:14px 16px;color:#13243A;font-size:15px;line-height:1.6;white-space:pre-line;">[poruka]</div>
</td></tr>
<tr><td style="padding:24px 28px 28px;">
<a href="mailto:[email]?subject=Ponuda%20-%20ESPAM%20PLUS" style="display:inline-block;background:#0A65B8;color:#FFFFFF;font-size:15px;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:999px;">Odgovorite kupcu</a>
<p style="margin:12px 0 0;color:#5B6B80;font-size:12px;">Ili samo kliknite „Odgovori" — odgovor ide direktno kupcu.</p>
</td></tr>
<tr><td style="background:#F7F9FC;padding:16px 28px;color:#7A8899;font-size:12px;line-height:1.6;border-top:1px solid #E3EAF3;">
' . esc_html( espam_option( 'company' ) . ' · ' . espam_option( 'address' ) . ', ' . espam_option( 'city' ) ) . '<br>
Poslato sa forme „Zatražite ponudu" na <a href="[_site_url]" style="color:#0A65B8;text-decoration:none;">[_site_url]</a>
</td></tr>
</table>
</td></tr></table>
</body></html>';
}

// The inquiry form always sends the branded HTML email (also for forms created before this template existed).
add_filter(
	'wpcf7_contact_form_property_mail',
	static function ( $mail, $form ) {
		if ( is_array( $mail ) && $form && (int) $form->id() === espam_form_id() ) {
			$mail['use_html']      = true;
			$mail['exclude_blank'] = true;
			$mail['body']          = espam_inquiry_mail_body();
			$mail['attachments']   = '[prilog]';
		}
		return $mail;
	},
	10,
	2
);

// Inquiries go to the email from Settings → ESPAM PLUS (falls back to the site admin email).
add_filter(
	'wpcf7_mail_components',
	static function ( array $components, $form, $mail ) {
		$email = sanitize_email( (string) espam_option( 'email' ) );
		if ( $email && $form && (int) $form->id() === espam_form_id() && 'mail' === $mail->name() ) {
			$components['recipient'] = $email;
		}
		return $components;
	},
	10,
	3
);

/**
 * Branded HTML confirmation sent to the customer after an inquiry (CF7 "Mail (2)").
 * Deliberately doesn't echo the free-text message: the form has no captcha, so a bot could otherwise use it
 * to send its own text to any address it types in.
 */
function espam_confirmation_mail_body(): string {
	$logo  = esc_url( ESPAM_URL . 'assets/logo/site-icon-512.png' );
	$font  = "font-family:'Plus Jakarta Sans',Segoe UI,Helvetica,Arial,sans-serif;";
	$phone = (string) espam_option( 'phone' );
	$email = sanitize_email( (string) espam_option( 'email' ) );
	$reach = array();
	if ( $phone ) {
		$reach[] = 'telefon <a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ) . '" style="color:#0A65B8;text-decoration:none;">' . esc_html( $phone ) . '</a>';
	}
	if ( $email ) {
		$reach[] = 'email <a href="mailto:' . esc_attr( $email ) . '" style="color:#0A65B8;text-decoration:none;">' . esc_html( $email ) . '</a>';
	}
	$contact = $reach ? '<p style="margin:16px 0 0;color:#5B6B80;font-size:14px;line-height:1.6;">Ako želite nešto da dopunite, odgovorite na ovaj email ili nas kontaktirajte: ' . implode( ' · ', $reach ) . '.</p>' : '';

	return '<!doctype html>
<html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Upit je primljen</title></head>
<body style="margin:0;padding:0;background:#EEF3F9;' . $font . '">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF3F9;padding:24px 12px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(11,58,102,.08);">
<tr><td style="background:#0B3A66;padding:22px 28px;">
<table role="presentation" cellpadding="0" cellspacing="0"><tr>
<td style="vertical-align:middle;padding-right:12px;"><img src="' . $logo . '" width="40" height="40" alt="ESPAM PLUS" style="display:block;border-radius:10px;border:0;"></td>
<td style="vertical-align:middle;color:#FFFFFF;font-size:18px;font-weight:800;letter-spacing:.04em;' . $font . '">ESPAM PLUS<br><span style="font-size:12px;font-weight:500;letter-spacing:0;color:#B9D3EE;">Veleprodaja · Berane</span></td>
</tr></table></td></tr>
<tr><td style="height:4px;background:#0A65B8;line-height:4px;font-size:0;">&nbsp;</td></tr>
<tr><td style="padding:28px 28px 8px;">
<p style="margin:0 0 6px;color:#0A65B8;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Zahtjev za ponudu je primljen</p>
<h1 style="margin:0 0 12px;color:#13243A;font-size:22px;line-height:1.3;font-weight:800;">Hvala, [ime]!</h1>
<p style="margin:0;color:#13243A;font-size:15px;line-height:1.6;">Uspješno ste poslali zahtjev za ponudu. Provjeravamo dostupnost kod dobavljača i javljamo vam se sa cijenom i rokom isporuke.</p>
</td></tr>
<tr><td style="padding:16px 28px 4px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $font . '">
<tr><td style="padding:10px 0;width:120px;color:#5B6B80;font-size:13px;border-bottom:1px solid #E3EAF3;">Kategorija</td><td style="padding:10px 0;color:#13243A;font-size:15px;font-weight:600;border-bottom:1px solid #E3EAF3;">[kategorija]</td></tr>
<tr><td style="padding:10px 0;width:120px;color:#5B6B80;font-size:13px;border-bottom:1px solid #E3EAF3;">Poslato</td><td style="padding:10px 0;color:#13243A;font-size:15px;font-weight:600;border-bottom:1px solid #E3EAF3;">[_date] u [_time]</td></tr>
</table>
</td></tr>
<tr><td style="padding:12px 28px 28px;">' . $contact . '</td></tr>
<tr><td style="background:#F7F9FC;padding:16px 28px;color:#7A8899;font-size:12px;line-height:1.6;border-top:1px solid #E3EAF3;">
' . esc_html( espam_option( 'company' ) . ' · ' . espam_option( 'address' ) . ', ' . espam_option( 'city' ) ) . '<br>
Ovu poruku ste dobili jer je vaša adresa upisana u formu „Zatražite ponudu" na <a href="[_site_url]" style="color:#0A65B8;text-decoration:none;">[_site_url]</a>.
</td></tr>
</table>
</td></tr></table>
</body></html>';
}

// After an inquiry the customer gets a confirmation email ("Mail (2)"); replies go to the company email.
add_filter(
	'wpcf7_contact_form_property_mail_2',
	static function ( $mail, $form ) {
		if ( ! $form || (int) $form->id() !== espam_form_id() ) {
			return $mail;
		}
		$email = sanitize_email( (string) espam_option( 'email' ) );
		return array_merge(
			is_array( $mail ) ? $mail : array(),
			array(
				'active'             => true,
				'subject'            => 'Primili smo vaš zahtjev za ponudu — ESPAM PLUS',
				'sender'             => '[_site_title] <wordpress@[_site_domain]>',
				'recipient'          => '[email]',
				'body'               => espam_confirmation_mail_body(),
				'additional_headers' => $email ? 'Reply-To: ' . $email : '',
				'attachments'        => '',
				'use_html'           => true,
				'exclude_blank'      => true,
			)
		);
	},
	10,
	2
);

/**
 * Copies a bundled brand image into the media library; re-imports when the bundled file changes.
 */
function espam_import_brand_image( string $file, string $title ): int {
	$ids    = (array) get_option( 'espam_brand_media', array() );
	$src    = ESPAM_DIR . 'assets/logo/' . $file;
	$hash   = is_readable( $src ) ? md5_file( $src ) : '';
	$old_id = ! empty( $ids[ $file ] ) && get_post( (int) $ids[ $file ] ) ? (int) $ids[ $file ] : 0;
	if ( $old_id ) {
		$stored = wp_get_original_image_path( $old_id ) ?: get_attached_file( $old_id );
		if ( ! $hash || ( $stored && is_readable( $stored ) && md5_file( $stored ) === $hash ) ) {
			return $old_id;
		}
	}
	if ( ! $hash ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$tmp = wp_tempnam( $file );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => 'espam-' . $file, 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	if ( $old_id && (int) get_option( 'site_icon' ) === $old_id ) {
		update_option( 'site_icon', $id );
	}
	update_post_meta( $id, '_wp_attachment_image_alt', 'ESPAM PLUS' );
	$ids[ $file ] = $id;
	update_option( 'espam_brand_media', $ids );
	return (int) $id;
}

function espam_privacy_content(): string {
	$p   = static fn( string $t ): string => "<!-- wp:paragraph -->\n<p>{$t}</p>\n<!-- /wp:paragraph -->\n\n";
	$h   = static fn( string $t ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$t}</h2>\n<!-- /wp:heading -->\n\n";
	$out = $p( 'Ova stranica objašnjava koje podatke prikupljamo preko sajta i kako ih koristimo. Prije objave sajta provjerite tekst sa pravnim savjetnikom.' );
	$out .= $h( 'Koje podatke prikupljamo' );
	$out .= $p( 'Kada pošaljete upit preko forme, primamo podatke koje upišete: ime i prezime, naziv firme, email, telefon, kategoriju i opis traženih proizvoda.' );
	$out .= $h( 'Zašto ih koristimo' );
	$out .= $p( 'Podatke koristimo isključivo da bismo odgovorili na vaš upit i pripremili ponudu. Ne prodajemo ih i ne ustupamo trećim licima, osim dobavljačima u mjeri potrebnoj za pripremu ponude, bez vaših kontakt podataka.' );
	$out .= $h( 'Koliko ih čuvamo' );
	$out .= $p( 'Upite čuvamo onoliko dugo koliko je potrebno za saradnju i ispunjavanje zakonskih obaveza. Možete zatražiti uvid, ispravku ili brisanje svojih podataka slanjem poruke na naš email.' );
	$out .= $h( 'Mapa i kolačići' );
	$out .= $p( 'Stranica Kontakt prikazuje Google mapu, koju učitava Google i pri tome može postaviti svoje kolačiće. Sajt ne koristi kolačiće za praćenje i reklame.' );
	return $out;
}

// Contact Form 7 loads its CSS/JS on every page by default; the form only lives on Kontakt.
add_filter( 'wpcf7_load_js', static fn() => is_page( 'kontakt' ) );
add_filter( 'wpcf7_load_css', static fn() => is_page( 'kontakt' ) );
