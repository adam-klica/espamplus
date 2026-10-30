<?php
/**
 * Plugin Name:       ESPAM PLUS — Core
 * Description:       Funkcionalnost sajta ESPAM PLUS: podaci firme, kategorije asortimana, blokovi (logo, kategorije, kontakt, mapa, forma za upit), Viber/WhatsApp dugme i početno postavljanje stranica. Potreban za temu „ESPAM PLUS".
 * Version:     1.5.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            ESPAM PLUS
 * License:           GPL-2.0-or-later
 * Text Domain:       espam
 */

defined( 'ABSPATH' ) || exit;

define( 'ESPAM_VERSION', '1.5.0' );
define( 'ESPAM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ESPAM_URL', plugin_dir_url( __FILE__ ) );

require_once ESPAM_DIR . 'includes/helpers.php';
require_once ESPAM_DIR . 'includes/icons.php';
require_once ESPAM_DIR . 'includes/images.php';
require_once ESPAM_DIR . 'includes/settings.php';
require_once ESPAM_DIR . 'includes/blocks.php';
require_once ESPAM_DIR . 'includes/chat.php';
require_once ESPAM_DIR . 'includes/uploads.php';
require_once ESPAM_DIR . 'includes/setup.php';
require_once ESPAM_DIR . 'includes/hardening.php';

register_activation_hook( __FILE__, 'espam_run_setup' );
