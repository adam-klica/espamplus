<?php
// Run on the live site by deploy.sh (wp eval-file -). Hostinger's .htaccess has ExpiresDefault "1 weeks", which also
// applies to HTML pages, so browsers kept old pages for up to 7 days after a deploy. This marked block makes HTML
// revalidate on every visit (ExpiresByType wins over ExpiresDefault). Idempotent; static files keep their long expiry.
require_once ABSPATH . 'wp-admin/includes/misc.php';
$file = ABSPATH . '.htaccess';
if ( ! file_exists( $file . '.bak-espam' ) ) {
	copy( $file, $file . '.bak-espam' );
}
$ok = insert_with_markers(
	$file,
	'ESPAM',
	array(
		'<IfModule mod_expires.c>',
		'ExpiresActive On',
		'ExpiresByType text/html "access plus 0 seconds"',
		'</IfModule>',
	)
);
echo $ok ? "htaccess: HTML revalidates\n" : "htaccess: FAILED\n";
