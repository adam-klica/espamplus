<?php
/**
 * Shared helpers: company data, product categories, site pages, icons.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Company data and contact settings (Settings → ESPAM PLUS), with defaults.
 */
function espam_option( string $key ) {
	$defaults = array(
		'company'    => 'ESPAM PLUS DOO',
		'address'    => 'VIII crnogorske brigade broj 5',
		'city'       => '84300 Berane, Crna Gora',
		'pib'        => '',
		'mb'         => '',
		'phone'      => '+382 68 074 287',
		'email'      => '',
		'hours'      => '',
		'chat_phone' => '38268074287',
		'chat_fab'   => 1,
		'map_query'  => 'VIII crnogorske brigade 5, Berane, Crna Gora',
	);
	$opts = (array) get_option( 'espam_options', array() );
	return isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : ( $defaults[ $key ] ?? '' );
}

/**
 * Product categories in display order: slug => [name, icon, short text, examples].
 * Deliberately no products or prices — the company sources goods to order.
 */
function espam_categories(): array {
	return array(
		'it-oprema'          => array( 'IT oprema', 'device-laptop', 'Računari i oprema za kancelarije, škole i firme.', array( 'Laptopovi i desktop računari', 'Monitori', 'Mrežna oprema', 'Tastature, miševi i periferija', 'Komponente i skladištenje podataka' ) ),
		'tv-displeji'        => array( 'TV, displeji, projektori', 'device-tv', 'Ekrani za domove, hotele, prodajne i poslovne prostore.', array( 'Televizori', 'Profesionalni i reklamni displeji', 'Projektori i platna', 'Nosači i oprema za montažu' ) ),
		'bijela-tehnika'     => array( 'Bijela tehnika', 'fridge', 'Kućni i profesionalni aparati za opremanje objekata.', array( 'Frižideri i zamrzivači', 'Mašine za veš i sudove', 'Šporeti i ugradni aparati', 'Klima uređaji', 'Mali kućni aparati' ) ),
		'kancelarijski'      => array( 'Kancelarijski materijal', 'paperclip', 'Potrošni materijal za svakodnevni rad kancelarije.', array( 'Papir i fascikle', 'Pribor za pisanje', 'Arhivska oprema', 'Potrošni materijal za kancelarije' ) ),
		'stampaci'           => array( 'Štampači', 'printer', 'Uređaji za štampu i skeniranje, uz potrošni materijal.', array( 'Laserski i inkjet štampači', 'Multifunkcijski uređaji', 'Skeneri', 'Toneri i kertridži' ) ),
		'mobilni'            => array( 'Mobilni telefoni i oprema', 'device-mobile', 'Telefoni, tableti i prateća oprema.', array( 'Mobilni telefoni', 'Tableti', 'Punjači, kablovi i baterije', 'Maske, zaštitna stakla i slušalice' ) ),
		'alati-basta'        => array( 'Alati i baštenska oprema', 'tool', 'Ručni i električni alati, oprema za dvorište i baštu.', array( 'Ručni alati', 'Električni i akumulatorski alati', 'Baštenska oprema i mašine', 'Pribor i potrošni materijal' ) ),
		'ostalo'             => array( 'Ostalo', 'packages', 'Ne vidite kategoriju? Pošaljite upit — tražimo i robu van ove liste.', array( 'Roba po posebnom zahtjevu', 'Oprema za opremanje objekata', 'Sezonska roba' ) ),
	);
}

/**
 * Icon lists used across pages: set => [ [icon, title, text], ... ].
 */
function espam_feature_sets(): array {
	$steps = array(
		array( 'send', 'Pošaljete nam upit', 'Navedete proizvode i količine koje su vam potrebne — preko forme, emaila, telefona ili Vibera.' ),
		array( 'search', 'Provjeravamo dostupnost', 'Kontaktiramo odgovarajuće dobavljače i pronalazimo najbolju opciju za vaš zahtjev.' ),
		array( 'file-invoice', 'Dobijate ponudu', 'Cijena, dostupnost i rok isporuke — jasno, na jednom mjestu.' ),
		array( 'truck', 'Realizujemo porudžbinu', 'Organizujemo nabavku i isporuku, a vi dobijate robu bez dodatnih briga.' ),
	);
	return array(
		'values'       => array(
			array( 'stack-2', 'Širok asortiman', 'IT, tehnika, kancelarija, alati — i sve van liste.' ),
			array( 'adjustments-horizontal', 'Fleksibilna nabavka', 'Količine i rokovi prema vašim potrebama.' ),
			array( 'shield-check', 'Pouzdani dobavljači', 'Domaći i međunarodni partneri koje poznajemo.' ),
			array( 'briefcase', 'B2B saradnja', 'Prodavnice, marketi, ugostitelji i firme.' ),
		),
		'advantages'   => array(
			array( 'rosette-discount-check', 'Bez ograničenja na jedan brend', 'Ne prodajemo jednu paletu, nego tražimo proizvod koji odgovara vašem zahtjevu i budžetu.' ),
			array( 'search', 'Najbolja opcija, ne prva', 'Upoređujemo ponude više dobavljača prije nego što vam pošaljemo cijenu.' ),
			array( 'phone-call', 'Jedan partner za sve kategorije', 'IT oprema, bijela tehnika, kancelarija, alati — jedan upit, jedan kontakt.' ),
			array( 'truck', 'Brza realizacija', 'Ponuda sa cijenom, dostupnošću i rokom isporuke, pa organizovana nabavka i isporuka.' ),
		),
		'segments'     => array(
			array( 'building-store', 'Prodavnice', 'Dopuna asortimana bez obaveze da držite zalihe jednog proizvođača.' ),
			array( 'shopping-cart', 'Marketi', 'Roba široke potrošnje i sezonski artikli u veleprodajnim količinama.' ),
			array( 'tools-kitchen-2', 'Ugostitelji', 'Bijela tehnika, displeji i oprema za opremanje objekata.' ),
			array( 'briefcase', 'Firme i kancelarije', 'IT oprema, štampači i kancelarijski materijal na jednom mjestu.' ),
		),
		'steps'        => $steps,
		'steps-detail' => $steps,
		// Card sets (rendered as .ep-cards).
		'about'        => array(
			array( 'building-store', 'Ko smo', 'Veleprodajna firma sa sjedištem u Beranama. Umjesto jedne palete proizvoda, radimo sa mrežom domaćih i međunarodnih dobavljača.' ),
			array( 'packages', 'Čime se bavimo', 'Nabavljamo i isporučujemo IT opremu, displeje, bijelu tehniku, kancelarijski materijal, štampače, telefone, alate i baštensku opremu.' ),
			array( 'map-pin', 'Gdje radimo', 'Sa poslovnim kupcima širom Crne Gore — prodavnicama, marketima, ugostiteljima i firmama.' ),
		),
		'principles'   => array(
			array( 'file-invoice', 'Jasna ponuda', 'Cijena, dostupnost i rok isporuke na jednom mjestu — bez sitnih slova i naknadnih iznenađenja.' ),
			array( 'phone-call', 'Jedan sagovornik', 'Od prvog upita do isporuke razgovarate sa istom osobom koja zna vašu porudžbinu.' ),
			array( 'adjustments-horizontal', 'Prilagođavamo se vama', 'Količine, rokovi i dinamika nabavke prema vašim potrebama, a ne prema našem lageru.' ),
			array( 'shield-check', 'Provjereni dobavljači', 'Radimo sa domaćim i međunarodnim partnerima koje poznajemo i čije uslove možemo garantovati u ponudi.' ),
		),
		'offer'        => array(
			array( 'receipt', 'Cijena', 'Za tačno navedene proizvode i količine, uz uslove plaćanja.' ),
			array( 'circle-check', 'Dostupnost', 'Šta je odmah dostupno, a za šta predlažemo zamjenski model.' ),
			array( 'calendar-time', 'Rok isporuke', 'Kada roba stiže i kako se organizuje isporuka na vašu adresu.' ),
			array( 'messages', 'Prijedlog', 'Ako postoji bolja ili povoljnija opcija za vašu namjenu, napišemo je.' ),
		),
		'request-tips' => array(
			array( 'list-details', 'Šta tražite', 'Naziv, model ili samo namjenu proizvoda — pomoći ćemo u izboru.' ),
			array( 'stack-2', 'Koliko vam treba', 'Količina utiče na cijenu i izbor dobavljača.' ),
			array( 'calendar-time', 'Do kada', 'Željeni rok i mjesto isporuke.' ),
		),
		'b2b-modes'    => array(
			array( 'repeat', 'Redovna nabavka', 'Za kupce koji robu nabavljaju stalno: dogovaramo dinamiku, količine i način naručivanja, pa svaka sljedeća porudžbina ide brže.' ),
			array( 'bolt', 'Jednokratna porudžbina', 'Opremanje objekta, kancelarije ili sezonska nabavka: pošaljete listu, dobijete ponudu za tu porudžbinu.' ),
		),
	);
}

/**
 * Frequently asked questions: [question, answer]. Only what the company's own brief says — no invented promises.
 */
function espam_faqs(): array {
	return array(
		array( 'Da li imate robu na stanju?', 'Ne radimo sa sopstvenom paletom proizvoda. Robu nabavljamo od provjerenih domaćih i međunarodnih dobavljača prema vašem upitu — zato nismo ograničeni na jedan brend ili proizvođača i možemo ponuditi ono što vam zaista treba.' ),
		array( 'Mogu li tražiti proizvod koji nije na listi kategorija?', 'Da. Kategorije na sajtu su najčešći zahtjevi naših kupaca, ali u upitu možete navesti bilo koju robu. Provjerićemo dostupnost kod dobavljača i javiti vam se sa ponudom ili prijedlogom zamjene.' ),
		array( 'Šta treba da navedem u upitu?', 'Naziv ili opis proizvoda (model, specifikaciju ili namjenu), količinu, željeni rok i mjesto isporuke. Što je upit precizniji, to je ponuda brža i tačnija. Ako niste sigurni koji model vam treba, opišite namjenu — pomoći ćemo u izboru.' ),
		array( 'Kako se formira cijena?', 'Cijena zavisi od proizvoda, količine, dobavljača i roka isporuke. Zato je ne objavljujemo na sajtu, već je dobijate u ponudi za vaš konkretan upit — zajedno sa dostupnošću i rokom isporuke.' ),
		array( 'Da li organizujete isporuku?', 'Da. Kada prihvatite ponudu, preuzimamo komunikaciju sa dobavljačem, organizujemo nabavku i isporuku na vašu adresu. Rok isporuke je naveden u ponudi.' ),
		array( 'Sa kim sarađujete?', 'Najčešće sa prodavnicama, marketima, ugostiteljima i firmama kojima je potrebna roba u većim količinama — redovno ili povremeno. Saradnju prilagođavamo obimu i dinamici vaše nabavke.' ),
	);
}

/**
 * Site pages: slug => title. Each has its own block template (page-{slug}.html) in the theme.
 */
function espam_pages(): array {
	return array(
		'pocetna'              => 'Početna',
		'o-nama'               => 'O nama',
		'asortiman'            => 'Asortiman',
		'saradnja'             => 'Kako funkcioniše saradnja',
		'b2b'                  => 'Za poslovne kupce',
		'kontakt'              => 'Kontakt',
		'politika-privatnosti' => 'Politika privatnosti',
	);
}

/**
 * Main menu: slug => label.
 */
function espam_menu(): array {
	return array(
		'o-nama'    => 'O nama',
		'asortiman' => 'Asortiman',
		'saradnja'  => 'Saradnja',
		'b2b'       => 'Za firme',
		'kontakt'   => 'Kontakt',
	);
}

function espam_page_url( string $slug ): string {
	$page = get_page_by_path( $slug );
	return $page ? (string) get_permalink( $page ) : home_url( "/{$slug}/" );
}

/**
 * Link to the inquiry form with the category preselected.
 */
function espam_inquiry_url( string $category = '' ): string {
	$url = espam_page_url( 'kontakt' );
	if ( $category ) {
		$url = add_query_arg( 'kategorija', rawurlencode( $category ), $url );
	}
	return $url . '#upit';
}

/**
 * Inline SVG icon. Tabler (outline, MIT) or Simple Icons (brand, CC0) — see includes/icons.php.
 */
function espam_icon( string $name, int $size = 24 ): string {
	$icons = espam_icon_data();
	if ( empty( $icons[ $name ] ) ) {
		return '';
	}
	[ $type, $body ] = $icons[ $name ];
	if ( 'brand' === $type ) {
		return sprintf( '<svg class="ep-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="%2$s"/></svg>', $size, esc_attr( $body ) );
	}
	return sprintf( '<svg class="ep-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>', $size, $body );
}

/**
 * Viber / WhatsApp links for the configured number (digits only, international format).
 */
function espam_chat_links(): array {
	$phone = preg_replace( '/\D+/', '', (string) espam_option( 'chat_phone' ) );
	if ( ! $phone ) {
		return array();
	}
	return array(
		// viber://contact works in the Viber phone apps; viber://chat gives "requested page is unavailable" on iOS (tested 28. 10. 2026).
		'viber'    => array( 'Viber', 'viber://contact?number=%2B' . $phone ),
		'whatsapp' => array( 'WhatsApp', 'https://wa.me/' . $phone ),
	);
}

/**
 * Data attributes for a viber:// link and enqueues assets/viber.js, which shows the number and a WhatsApp link
 * when the Viber app doesn't open (desktop without Viber — the browser silently ignores viber://).
 */
function espam_viber_attrs(): string {
	$phone = preg_replace( '/\D+/', '', (string) espam_option( 'chat_phone' ) );
	$shown = (string) espam_option( 'phone' );
	if ( preg_replace( '/\D+/', '', $shown ) !== $phone ) {
		$shown = '+' . $phone;
	}
	wp_enqueue_script( 'espam-viber' );
	return sprintf( ' data-phone="%s" data-whatsapp="%s"', esc_attr( $shown ), esc_url( 'https://wa.me/' . $phone ) );
}

/**
 * Responsive <img> for a site photo from the theme (assets/img/<name>.webp and <name>-800.webp).
 * Returns '' if the photo is not in the set.
 */
function espam_image( string $name, string $class = '', string $sizes = '(max-width: 800px) 100vw, 50vw', bool $eager = false ): string {
	$data = espam_image_data()[ $name ] ?? null;
	if ( ! $data ) {
		return '';
	}
	$base = get_theme_file_uri( "assets/img/{$name}" );
	return sprintf(
		'<img class="%1$s" src="%2$s.webp" srcset="%2$s-800.webp 800w, %2$s.webp %3$dw" sizes="%4$s" width="%3$d" height="%5$d" alt="%6$s" decoding="async"%7$s>',
		esc_attr( trim( "ep-img {$class}" ) ),
		esc_url( $base ),
		(int) $data['w'],
		esc_attr( $sizes ),
		(int) $data['h'],
		esc_attr( $data['alt'] ),
		$eager ? ' fetchpriority="high"' : ' loading="lazy"'
	);
}
