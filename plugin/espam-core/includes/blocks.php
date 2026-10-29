<?php
/**
 * Dynamic (server-rendered) blocks used by the ESPAM PLUS theme.
 * Editor side: assets/editor.js registers them with a live server-side preview.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block definitions: name => [title, description, attributes, render callback].
 */
function espam_block_definitions(): array {
	return array(
		'logo'       => array( 'ESPAM · Logo', 'Logo firme.', array(
			'variant' => array( 'type' => 'string', 'default' => 'horizontal', 'enum' => array( 'horizontal', 'mark' ) ),
			'color'   => array( 'type' => 'string', 'default' => 'dark', 'enum' => array( 'dark', 'light' ) ),
		), 'espam_render_logo' ),
		'hero-mark'  => array( 'ESPAM · Animirani znak', 'Veliki znak iz logoa koji se sklapa pri učitavanju.', array(), 'espam_render_hero_mark' ),
		'nav'        => array( 'ESPAM · Meni', 'Glavni meni (sa dugmetom za mobilni) ili meni u podnožju.', array(
			'variant' => array( 'type' => 'string', 'default' => 'main', 'enum' => array( 'main', 'footer' ) ),
		), 'espam_render_nav' ),
		'marquee'    => array( 'ESPAM · Traka', 'Pokretna traka sa porukama.', array(
			'items' => array( 'type' => 'string', 'default' => '' ),
		), 'espam_render_marquee' ),
		'categories' => array( 'ESPAM · Kategorije', 'Kategorije asortimana (mreža ili detaljan prikaz).', array(
			'variant' => array( 'type' => 'string', 'default' => 'grid', 'enum' => array( 'grid', 'full' ) ),
		), 'espam_render_categories' ),
		'company'    => array( 'ESPAM · Podaci firme', 'Naziv, adresa, PIB, matični broj, telefon, email, radno vrijeme.', array(
			'variant' => array( 'type' => 'string', 'default' => 'contact', 'enum' => array( 'contact', 'footer' ) ),
		), 'espam_render_company' ),
		'map'        => array( 'ESPAM · Mapa', 'Google mapa sa lokacijom firme.', array(), 'espam_render_map' ),
		'form'       => array( 'ESPAM · Forma za upit', 'Forma „Zatražite ponudu" (Contact Form 7).', array(), 'espam_render_form' ),
		'features'      => array( 'ESPAM · Lista sa ikonama', 'Prednosti, koraci saradnje ili tipovi kupaca, sa ikonama.', array(
			'set' => array( 'type' => 'string', 'default' => 'values', 'enum' => array( 'values', 'advantages', 'segments', 'steps', 'steps-detail', 'about', 'principles', 'offer', 'request-tips', 'b2b-modes' ) ),
		), 'espam_render_features' ),
		'network'       => array( 'ESPAM · Mreža nabavke', 'Animirana ilustracija: kupci → ESPAM PLUS → dobavljači.', array(), 'espam_render_network' ),
		'faq'           => array( 'ESPAM · Česta pitanja', 'Pitanja i odgovori (otvaraju se na klik).', array(), 'espam_render_faq' ),
		'breadcrumbs'   => array( 'ESPAM · Putanja', 'Početna › naziv stranice.', array(), 'espam_render_breadcrumbs' ),
		'photo'         => array( 'ESPAM · Fotografija', 'Fotografija iz teme (hero, logistics, b2b, berane, cat-…).', array(
			'name' => array( 'type' => 'string', 'default' => 'hero' ),
		), 'espam_render_photo' ),
		'photo-credits' => array( 'ESPAM · Zasluge za fotografije', 'Autori i licence fotografija na sajtu.', array(), 'espam_render_photo_credits' ),
		'chat-links' => array( 'ESPAM · Viber i WhatsApp', 'Dugmad za kontakt preko Vibera i WhatsAppa.', array(), 'espam_render_chat_links' ),
		'contact-channels' => array( 'ESPAM · Kanali kontakta', 'Velike pločice: telefon, Viber, WhatsApp, email (svaka samo ako je podešena).', array(), 'espam_render_contact_channels' ),
	);
}

add_action(
	'init',
	static function () {
		$asset = include ESPAM_DIR . 'assets/editor.asset.php';
		wp_register_script( 'espam-editor', ESPAM_URL . 'assets/editor.js', $asset['dependencies'], $asset['version'], true );
		$meta = array();
		foreach ( espam_block_definitions() as $name => [ $title, $description, $attributes, $render ] ) {
			register_block_type(
				"espam/{$name}",
				array(
					'api_version'           => 3,
					'title'                 => $title,
					'description'           => $description,
					'category'              => 'theme',
					'icon'                  => 'layout',
					'attributes'            => $attributes,
					'supports'              => array( 'html' => false, 'className' => true ),
					'render_callback'       => $render,
					'editor_script_handles' => array( 'espam-editor' ),
				)
			);
			$meta[ "espam/{$name}" ] = array( 'attributes' => (object) $attributes );
		}
		wp_add_inline_script( 'espam-editor', 'window.espamBlocks = ' . wp_json_encode( $meta ) . ';', 'before' );
	}
);

function espam_wrapper( string $class, string $inner, string $tag = 'div', array $extra = array() ): string {
	$attrs = get_block_wrapper_attributes( array_merge( array( 'class' => $class ), $extra ) );
	return "<{$tag} {$attrs}>{$inner}</{$tag}>";
}

function espam_logo_svg( string $file ): string {
	$svg = (string) file_get_contents( ESPAM_DIR . 'assets/logo/' . $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$svg = preg_replace( '/<title>.*?<\/title>/', '', $svg );
	return str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" ', trim( $svg ) );
}

/* ---------- blocks ---------- */

function espam_render_logo( array $attrs ): string {
	$mark = 'mark' === ( $attrs['variant'] ?? '' );
	$file = $mark ? 'logo-mark.svg' : ( 'light' === ( $attrs['color'] ?? '' ) ? 'logo-horizontal-white.svg' : 'logo-horizontal.svg' );
	$link = sprintf( '<a href="%s" rel="home"><span class="screen-reader-text">%s</span>%s</a>', esc_url( home_url( '/' ) ), esc_html( (string) espam_option( 'company' ) ), espam_logo_svg( $file ) );
	return espam_wrapper( 'ep-logo ep-logo--' . ( $mark ? 'mark' : 'horizontal' ), $link );
}

function espam_render_hero_mark(): string {
	$svg = espam_logo_svg( 'logo-mark.svg' );
	return espam_wrapper( 'ep-hero-mark', '<div class="ep-hero-mark__in">' . $svg . '</div>', 'div', array( 'aria-hidden' => 'true' ) );
}

function espam_render_nav( array $attrs ): string {
	$footer  = 'footer' === ( $attrs['variant'] ?? '' );
	$current = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	$items   = '';
	foreach ( espam_menu() as $slug => $label ) {
		$items .= sprintf( '<li><a href="%s"%s>%s</a></li>', esc_url( espam_page_url( $slug ) ), $current === $slug ? ' aria-current="page"' : '', esc_html( $label ) );
	}
	if ( $footer ) {
		$items .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( espam_page_url( 'politika-privatnosti' ) ), 'Politika privatnosti' );
		return espam_wrapper( 'ep-nav ep-nav--footer', "<ul>{$items}</ul>", 'nav', array( 'aria-label' => 'Podnožje' ) );
	}
	$cta    = sprintf( '<a class="ep-nav__cta" href="%s">%s<span>Zatražite ponudu</span></a>', esc_url( espam_inquiry_url() ), espam_icon( 'send', 18 ) );
	$toggle = '<button type="button" class="ep-nav__toggle" aria-expanded="false" aria-controls="ep-menu"><span class="screen-reader-text">Meni</span>' . espam_icon( 'menu-2', 26 ) . espam_icon( 'x', 26 ) . '</button>';
	return espam_wrapper( 'ep-nav ep-nav--main', $toggle . "<div class=\"ep-nav__panel\" id=\"ep-menu\"><ul>{$items}</ul>{$cta}</div>", 'nav', array( 'aria-label' => 'Glavni meni' ) );
}

function espam_render_marquee( array $attrs ): string {
	$items = array_filter( array_map( 'trim', explode( '|', (string) ( $attrs['items'] ?? '' ) ) ) );
	$list  = '';
	if ( $items ) {
		foreach ( $items as $item ) {
			$list .= '<li>' . esc_html( $item ) . '</li>';
		}
	} else {
		// No custom text: the product categories with their icons.
		foreach ( espam_categories() as [ $name, $icon ] ) {
			$list .= '<li>' . espam_icon( $icon, 22 ) . esc_html( $name ) . '</li>';
		}
	}
	// Two copies of the track for a seamless loop; the second is hidden from assistive tech.
	$inner = "<ul class=\"ep-marquee__track\">{$list}{$list}</ul><ul class=\"ep-marquee__track\" aria-hidden=\"true\">{$list}{$list}</ul>";
	return espam_wrapper( 'ep-marquee', $inner );
}

function espam_render_categories( array $attrs ): string {
	$full = 'full' === ( $attrs['variant'] ?? '' );
	$out  = '';
	foreach ( espam_categories() as $slug => [ $name, $icon, $text, $examples ] ) {
		if ( $full ) {
			$list = '';
			foreach ( $examples as $ex ) {
				$list .= '<li>' . esc_html( $ex ) . '</li>';
			}
			$out .= sprintf(
				'<section class="ep-cat" id="%1$s"><div class="ep-cat__media">%8$s<span class="ep-cat__icon">%2$s</span></div><div class="ep-cat__body"><h2 class="ep-cat__name">%3$s</h2><p class="ep-cat__text">%4$s Proizvode nabavljamo od pouzdanih dobavljača prema zahtjevima i potrebama naših kupaca. Za aktuelnu ponudu i dostupnost pošaljite nam upit.</p><ul class="ep-cat__list">%5$s</ul></div><a class="ep-cat__cta" href="%6$s">Upit za ovu kategoriju %7$s</a></section>',
				esc_attr( $slug ),
				espam_icon( $icon, 44 ),
				esc_html( $name ),
				esc_html( $text ),
				$list,
				esc_url( espam_inquiry_url( $name ) ),
				espam_icon( 'arrow-up-right', 20 ),
				espam_image( "cat-{$slug}", 'ep-cat__img', '(max-width: 960px) 100vw, 420px' )
			);
		} else {
			$out .= sprintf(
				'<li><a class="ep-tile" href="%1$s"><span class="ep-tile__media">%6$s<span class="ep-tile__icon">%2$s</span></span><span class="ep-tile__name">%3$s</span><span class="ep-tile__text">%4$s</span><span class="ep-tile__go">Pogledaj %5$s</span></a></li>',
				esc_url( espam_page_url( 'asortiman' ) . '#' . $slug ),
				espam_icon( $icon, 36 ),
				esc_html( $name ),
				esc_html( $text ),
				espam_icon( 'arrow-right', 18 ),
				espam_image( "cat-{$slug}", 'ep-tile__img', '(max-width: 640px) 100vw, (max-width: 1180px) 50vw, 25vw' )
			);
		}
	}
	return $full ? espam_wrapper( 'ep-cats ep-cats--full', $out ) : espam_wrapper( 'ep-cats ep-cats--grid', "<ul>{$out}</ul>" );
}

/**
 * Company data rows: [icon, label, value html]. Empty values are skipped.
 */
function espam_company_rows(): array {
	$rows  = array();
	$rows[] = array( 'map-pin', 'Sjedište', esc_html( espam_option( 'address' ) ) . '<br>' . esc_html( espam_option( 'city' ) ) );
	$phone = (string) espam_option( 'phone' );
	if ( $phone ) {
		$rows[] = array( 'phone', 'Telefon', sprintf( '<a href="tel:%s">%s</a>', esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ), esc_html( $phone ) ) );
	}
	$email = (string) espam_option( 'email' );
	if ( $email ) {
		$rows[] = array( 'mail', 'Email', sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_html( antispambot( $email ) ) ) );
	}
	$hours = (string) espam_option( 'hours' );
	if ( $hours ) {
		$rows[] = array( 'clock', 'Radno vrijeme', nl2br( esc_html( $hours ) ) );
	}
	$ids = array();
	if ( espam_option( 'pib' ) ) {
		$ids[] = 'PIB: ' . esc_html( espam_option( 'pib' ) );
	}
	if ( espam_option( 'mb' ) ) {
		$ids[] = 'Matični broj: ' . esc_html( espam_option( 'mb' ) );
	}
	if ( $ids ) {
		$rows[] = array( 'id', 'Podaci firme', implode( '<br>', $ids ) );
	}
	return $rows;
}

function espam_render_company( array $attrs ): string {
	$rows = '';
	foreach ( espam_company_rows() as [ $icon, $label, $value ] ) {
		$rows .= sprintf( '<div class="ep-company__row"><span class="ep-company__icon">%s</span><dt>%s</dt><dd>%s</dd></div>', espam_icon( $icon, 22 ), esc_html( $label ), $value );
	}
	$name = '<p class="ep-company__name">' . esc_html( espam_option( 'company' ) ) . '</p>';
	return espam_wrapper( 'ep-company ep-company--' . ( 'footer' === ( $attrs['variant'] ?? '' ) ? 'footer' : 'contact' ), $name . "<dl>{$rows}</dl>" );
}

function espam_render_map(): string {
	$q    = rawurlencode( (string) espam_option( 'map_query' ) );
	$src  = "https://www.google.com/maps?q={$q}&z=16&output=embed";
	$big  = "https://www.google.com/maps/search/?api=1&query={$q}";
	$card = sprintf(
		'<div class="ep-map__card"><span class="ep-map__card-icon">%1$s</span><p class="ep-map__card-label">Sjedište firme</p><p class="ep-map__card-name">%2$s</p><p class="ep-map__card-addr">%3$s<br>%4$s</p><a class="ep-map__open" href="%5$s" target="_blank" rel="noopener">%6$s Otvori u Google mapama</a></div>',
		espam_icon( 'map-pin', 24 ),
		esc_html( espam_option( 'company' ) ),
		esc_html( espam_option( 'address' ) ),
		esc_html( espam_option( 'city' ) ),
		esc_url( $big ),
		espam_icon( 'arrow-up-right', 18 )
	);
	return espam_wrapper(
		'ep-map',
		sprintf(
			'<iframe title="Lokacija: %1$s" src="%2$s" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>%3$s',
			esc_attr( espam_option( 'address' ) ),
			esc_url( $src ),
			$card
		),
		'div',
		array( 'style' => '--ep-map-bg:url(' . esc_url( get_theme_file_uri( 'assets/img/berane-mapa-800.webp' ) ) . ')' )
	);
}

function espam_render_form(): string {
	$id = espam_form_id();
	if ( ! $id ) {
		return current_user_can( 'manage_options' ) ? espam_wrapper( 'ep-notice', 'Forma nije dostupna — aktivirajte „Contact Form 7" i pokrenite postavljanje (Podešavanja → ESPAM PLUS).', 'p' ) : '';
	}
	return espam_wrapper( 'ep-form', do_shortcode( sprintf( '[contact-form-7 id="%d" html_id="upit-forma"]', $id ) ) );
}

function espam_render_features( array $attrs ): string {
	$sets = espam_feature_sets();
	$set  = isset( $sets[ $attrs['set'] ?? '' ] ) ? $attrs['set'] : 'values';
	// Each set keeps the class names the theme already styles.
	// [list class, item class, prefix for __icon / __title / __text]
	$map = array(
		'values'       => array( 'ep-values', 'ep-value', 'ep-value' ),
		'advantages'   => array( 'ep-adv', 'ep-adv__item', 'ep-adv' ),
		'segments'     => array( 'ep-segments', 'ep-segment', 'ep-segment' ),
		'steps'        => array( 'ep-steps', 'ep-step', 'ep-step' ),
		'steps-detail' => array( 'ep-steps ep-steps--detail', 'ep-step', 'ep-step' ),
	);
	[ $list, $item, $base ] = $map[ $set ] ?? array( 'ep-cards ep-cards--' . $set, 'ep-card', 'ep-card' );
	$steps = str_starts_with( $set, 'steps' );
	$out   = '';
	foreach ( $sets[ $set ] as $i => [ $icon, $title, $text ] ) {
		$n    = $steps ? sprintf( '<span class="ep-step__n">%02d</span>', $i + 1 ) : '';
		$out .= sprintf(
			'<div class="%1$s">%2$s<span class="%3$s__icon">%4$s</span><h3 class="%3$s__title">%5$s</h3><p class="%3$s__text">%6$s</p></div>',
			esc_attr( $item ),
			$n,
			esc_attr( $base ),
			espam_icon( $icon, 24 ),
			esc_html( $title ),
			esc_html( $text )
		);
	}
	return espam_wrapper( $list, $out );
}

/**
 * Supply network illustration. One coordinate system (viewBox 640×520) drives the SVG lines and the
 * absolutely positioned HTML nodes (as percentages), so text stays real, crisp and translatable.
 */
function espam_render_network(): string {
	$W   = 640;
	$H   = 520;
	$pct = static fn( float $v, float $of ): string => round( $v / $of * 100, 3 ) . '%';
	$cx  = 320;
	$cy  = 260;

	$customers = array_map( static fn( $s ) => array( $s[0], $s[1] ), espam_feature_sets()['segments'] );
	$cats      = array_values( espam_categories() );
	$lines     = '';
	$dots      = '';
	$nodes     = '';

	foreach ( $customers as $i => [ $icon, $label ] ) {
		$y      = 95 + $i * 110;
		$id     = "ep-net-c{$i}";
		$lines .= sprintf( '<path id="%s" d="M176 %d C 236 %d, 220 %d, %d %d"/>', $id, $y, $y, $cy, $cx - 66, $cy );
		$dots  .= sprintf( '<circle r="4.5" class="ep-net__dot"><animateMotion dur="%ss" begin="%ss" repeatCount="indefinite" rotate="auto"><mpath href="#%s"/></animateMotion></circle>', 2.6 + $i * 0.3, $i * 0.7, $id );
		$nodes .= sprintf(
			'<div class="ep-net__chip" style="top:%s">%s<span>%s</span></div>',
			esc_attr( $pct( $y, $H ) ),
			espam_icon( $icon, 20 ),
			esc_html( $label )
		);
	}
	foreach ( $cats as $i => [ $name, $icon ] ) {
		$y      = 70 + $i * 57;
		$id     = "ep-net-s{$i}";
		$lines .= sprintf( '<path id="%s" d="M%d %d C 440 %d, 470 %.1f, 546 %.1f"/>', $id, $cx + 66, $cy, $cy, $y, $y );
		$dots  .= sprintf( '<circle r="3.5" class="ep-net__dot ep-net__dot--out"><animateMotion dur="%ss" begin="%ss" repeatCount="indefinite" keyPoints="1;0" keyTimes="0;1" calcMode="linear"><mpath href="#%s"/></animateMotion></circle>', 2.4 + ( $i % 4 ) * 0.35, 0.4 + $i * 0.45, $id );
		$nodes .= sprintf(
			'<div class="ep-net__sup" style="top:%s;left:%s" tabindex="0">%s<span class="ep-net__tip">%s</span></div>',
			esc_attr( $pct( $y, $H ) ),
			esc_attr( $pct( 546, $W ) ),
			espam_icon( $icon, 20 ),
			esc_html( $name )
		);
	}

	$svg    = sprintf( '<svg class="ep-net__lines" viewBox="0 0 %d %d" aria-hidden="true" focusable="false"><g class="ep-net__paths">%s</g>%s</svg>', $W, $H, $lines, $dots );
	$center = '<div class="ep-net__hub"><span class="ep-net__ring"></span><span class="ep-net__ring ep-net__ring--2"></span>' . espam_logo_svg( 'logo-mark.svg' ) . '</div>';
	$heads  = '<p class="ep-net__head ep-net__head--in">Vaš upit</p><p class="ep-net__head ep-net__head--out">Mreža dobavljača</p>';
	$label  = '<p class="screen-reader-text">Ilustracija: vaš upit stiže do ESPAM PLUS, a mi ga prosljeđujemo dobavljačima iz osam kategorija i vraćamo vam najbolju ponudu.</p>';
	return espam_wrapper( 'ep-net', $label . $heads . $svg . $nodes . $center );
}

function espam_render_faq(): string {
	$items = '';
	foreach ( espam_faqs() as $i => [ $q, $a ] ) {
		$items .= sprintf(
			'<details class="ep-faq__item"%s><summary><span>%s</span><span class="ep-faq__toggle" aria-hidden="true"></span></summary><div class="ep-faq__answer"><p>%s</p></div></details>',
			0 === $i ? ' open' : '',
			esc_html( $q ),
			esc_html( $a )
		);
	}
	return espam_wrapper( 'ep-faq', $items );
}

function espam_render_breadcrumbs(): string {
	$title = is_404() ? 'Stranica nije pronađena' : get_the_title( get_queried_object_id() );
	$inner = sprintf(
		'<ol><li><a href="%1$s">%2$s<span>Početna</span></a></li><li aria-hidden="true">%3$s</li><li><span aria-current="page">%4$s</span></li></ol>',
		esc_url( home_url( '/' ) ),
		espam_icon( 'home', 16 ),
		espam_icon( 'chevron-right', 14 ),
		esc_html( $title )
	);
	return espam_wrapper( 'ep-crumbs', $inner, 'nav', array( 'aria-label' => 'Putanja' ) );
}

function espam_render_photo( array $attrs ): string {
	$name = sanitize_key( (string) ( $attrs['name'] ?? '' ) );
	$img  = espam_image( $name, '', '(max-width: 960px) 100vw, 50vw', 'hero' === $name );
	return $img ? espam_wrapper( 'ep-photo', $img, 'figure' ) : '';
}

function espam_render_photo_credits(): string {
	$items = '';
	foreach ( espam_image_data() as $data ) {
		$items .= sprintf( '<li>%s — %s, <a href="%s" rel="nofollow noopener" target="_blank">%s</a></li>', esc_html( $data['alt'] ), esc_html( $data['author'] ), esc_url( $data['source'] ), esc_html( $data['license'] ) );
	}
	return $items ? espam_wrapper( 'ep-credits', "<ul>{$items}</ul>" ) : '';
}

function espam_render_chat_links(): string {
	$items = '';
	foreach ( espam_chat_links() as $key => [ $label, $href ] ) {
		$items .= sprintf( '<a class="ep-chat ep-chat--%s" href="%s" rel="noopener"%s>%s<span>%s</span></a>', esc_attr( $key ), esc_url( $href, array( 'https', 'viber' ) ), 'viber' === $key ? espam_viber_attrs() : '', espam_icon( $key, 22 ), esc_html( $label ) );
	}
	return $items ? espam_wrapper( 'ep-chat-links', $items ) : '';
}

// Block editor assets.
add_action(
	'enqueue_block_editor_assets',
	static function () {
		wp_enqueue_script( 'espam-editor' );
	}
);

/**
 * Contact channels as large tiles (Kontakt page): phone, Viber, WhatsApp, email — each only when set.
 */
function espam_render_contact_channels(): string {
	$items    = '';
	$channels = array_filter( espam_fab_channels(), static fn( $c ) => 'form' !== $c[0] );
	// Phone first on the contact page; Viber/WhatsApp show the number they open.
	usort( $channels, static fn( $a, $b ) => array_search( $a[0], array( 'phone', 'viber', 'whatsapp', 'mail' ), true ) <=> array_search( $b[0], array( 'phone', 'viber', 'whatsapp', 'mail' ), true ) );
	$number = (string) espam_option( 'phone' );
	foreach ( $channels as [ $key, $label, $sub, $href, $icon, $blank ] ) {
		$label  = 'phone' === $key ? 'Pozovite' : $label;
		$sub    = in_array( $key, array( 'viber', 'whatsapp' ), true ) && $number ? $number : $sub;
		$items .= sprintf(
			'<a class="ep-channel ep-channel--%1$s" href="%2$s"%3$s><span class="ep-channel__icon">%4$s</span><span class="ep-channel__text"><strong>%5$s</strong><span>%6$s</span></span><span class="ep-channel__go">%7$s</span></a>',
			esc_attr( $key ),
			esc_url( $href, array( 'https', 'http', 'viber', 'tel', 'mailto' ) ),
			( $blank ? ' target="_blank" rel="noopener"' : '' ) . ( 'viber' === $key ? espam_viber_attrs() : '' ),
			espam_icon( $icon, 26 ),
			esc_html( $label ),
			esc_html( 'mail' === $key ? antispambot( $sub ) : $sub ),
			espam_icon( 'arrow-up-right', 20 )
		);
	}
	return $items ? espam_wrapper( 'ep-channels', $items ) : '';
}
