<?php
/**
 * Inquiry form attachments ("prilog"): server-side checks on top of Contact Form 7's own extension and size rules.
 *
 * CF7 already stores uploads in uploads/wpcf7_uploads (web access denied by .htaccess, files chmod 0400, random
 * sub-directory) and deletes them once the mail is sent. Here every file must also really be what its extension says,
 * carry no scripts, macros, embedded objects or remote references, and one visitor can't flood the inbox.
 */

defined( 'ABSPATH' ) || exit;

const ESPAM_UPLOAD_FIELD     = 'prilog';
const ESPAM_UPLOAD_FILETYPES = 'pdf|docx|xlsx|txt|jpg|jpeg|png';

/**
 * Allowed extension → MIME types libmagic may report for a genuine file of that type.
 * Office files are also accepted as plain zip: their contents are inspected below anyway.
 */
function espam_upload_mimes(): array {
	return array(
		'pdf'  => array( 'application/pdf' ),
		'docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream' ),
		'xlsx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream' ),
		'txt'  => array( 'text/plain', 'text/csv' ),
		'jpg'  => array( 'image/jpeg' ),
		'jpeg' => array( 'image/jpeg' ),
		'png'  => array( 'image/png' ),
	);
}

/**
 * Why a stored upload must be rejected ('' when it is fine). The reason only goes to the error log.
 */
function espam_upload_problem( string $path ): string {
	$name = wp_basename( $path );
	$ext  = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
	$mime = espam_upload_mimes();

	if ( ! isset( $mime[ $ext ] ) ) {
		return "extension .{$ext} not allowed";
	}
	// "shell.php.pdf" (CF7 renames it to "shell.php_.pdf") and similar double extensions.
	if ( preg_match( '/\.(php\d?|phtml|phar|phps|pht|cgi|pl|py|sh|asp|aspx|jsp|exe|com|bat|cmd|ps1|vbs|js|jar|html?|svg|htaccess)(\.|_|$)/i', $name ) ) {
		return 'executable extension inside the file name';
	}
	$size = (int) @filesize( $path );
	if ( $size <= 0 ) {
		return 'empty or unreadable file';
	}
	if ( ! class_exists( 'finfo' ) ) {
		return 'fileinfo extension missing';
	}
	$type = (string) ( new finfo( FILEINFO_MIME_TYPE ) )->file( $path );
	if ( ! in_array( $type, $mime[ $ext ], true ) ) {
		return "content is {$type}, not .{$ext}";
	}

	$data = (string) file_get_contents( $path );
	// PHP code or a script hidden in an image/text/PDF is the classic upload-to-RCE polyglot. Only markers long enough
	// not to occur by chance in compressed binary data (`<?=` or `<%` would, in a few MB of JPEG or PDF).
	if ( preg_match( '/<\?php|<script\b/i', $data ) ) {
		return 'contains script code';
	}

	switch ( $ext ) {
		case 'jpg':
		case 'jpeg':
		case 'png':
			$info = @getimagesize( $path );
			$want = 'png' === $ext ? IMAGETYPE_PNG : IMAGETYPE_JPEG;
			if ( ! $info || $want !== $info[2] ) {
				return 'not a valid image';
			}
			if ( $info[0] > 12000 || $info[1] > 12000 ) {
				return 'image dimensions too large';
			}
			return '';

		case 'pdf':
			if ( false === strpos( substr( $data, 0, 1024 ), '%PDF-' ) ) {
				return 'missing PDF header';
			}
			// Active content: scripts, launching programs, files hidden inside the PDF, Flash/media.
			if ( preg_match( '/\/(JavaScript|Launch|EmbeddedFiles?|RichMedia)\b/', $data ) ) {
				return 'PDF with active or embedded content';
			}
			return '';

		case 'txt':
			if ( false !== strpos( $data, "\0" ) ) {
				return 'binary data in a text file';
			}
			return '';

		default: // docx, xlsx
			return espam_office_problem( $path, 'docx' === $ext ? 'word/document.xml' : 'xl/workbook.xml' );
	}
}

/**
 * Word/Excel (OOXML) files are zip archives: no macros, ActiveX, embedded objects or remote templates/objects
 * (the "Follina" style attacks), and no zip bombs.
 */
function espam_office_problem( string $path, string $main_part ): string {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return 'zip extension missing';
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path, ZipArchive::RDONLY ) ) {
		return 'not a valid Office file';
	}
	$problem = '';
	$total   = 0;
	$count   = $zip->numFiles;
	if ( $count < 1 || $count > 2000 ) {
		$problem = 'unexpected number of parts';
	}
	for ( $i = 0; '' === $problem && $i < $count; $i++ ) {
		$stat = $zip->statIndex( $i );
		$part = $stat ? (string) $stat['name'] : '';
		$total += $stat ? (int) $stat['size'] : 0;
		if ( '' === $part || str_contains( $part, '..' ) || str_starts_with( $part, '/' ) || str_contains( $part, '\\' ) ) {
			$problem = 'invalid part name';
		} elseif ( $total > 100 * MB_IN_BYTES ) {
			$problem = 'uncompressed size too large';
		} elseif ( preg_match( '/vbaProject\.bin|vbaData\.xml|(^|\/)(activeX|embeddings)\/|oleObject[^\/]*$|\.(exe|dll|js|vbs|ps1|bat|cmd|lnk|hta|scr|msi)$/i', $part ) ) {
			$problem = "forbidden part {$part}";
		} elseif ( str_ends_with( $part, '.rels' ) || '[Content_Types].xml' === $part ) {
			$xml = (string) $zip->getFromIndex( $i, 2 * MB_IN_BYTES );
			if ( preg_match( '/macroEnabled|vbaProject|\/(oleObject|attachedTemplate|subDocument|frame|package|control)"/i', $xml ) ) {
				$problem = "macro or external object in {$part}";
			}
		}
	}
	if ( '' === $problem && ( false === $zip->locateName( '[Content_Types].xml' ) || false === $zip->locateName( $main_part ) ) ) {
		$problem = 'not a valid Office file';
	}
	$zip->close();
	return $problem;
}

function espam_is_inquiry_form(): bool {
	$form = class_exists( 'WPCF7_ContactForm' ) ? WPCF7_ContactForm::get_current() : null;
	return $form && (int) $form->id() === espam_form_id();
}

// Runs after CF7 has moved the upload into its protected temp dir; a rejected file is deleted right away.
add_filter(
	'wpcf7_validate_file',
	static function ( $result, $tag, $args = array() ) {
		if ( ESPAM_UPLOAD_FIELD !== $tag->name || ! espam_is_inquiry_form() || empty( $args['uploaded_files'] ) || is_wp_error( $args['uploaded_files'] ) ) {
			return $result;
		}
		foreach ( (array) $args['uploaded_files'] as $path ) {
			$problem = espam_upload_problem( (string) $path );
			if ( '' !== $problem ) {
				error_log( sprintf( 'espam upload rejected (%s): %s', $problem, wp_basename( (string) $path ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				wp_delete_file( (string) $path );
				$result->invalidate( $tag, 'Prilog nije prihvaćen. Pošaljite običan PDF, Word (.docx), Excel (.xlsx), TXT ili sliku (JPG, PNG).' );
				break;
			}
		}
		return $result;
	},
	20,
	3
);

// The name the company sees in the mail: no control or bidi characters ("racun‮fdp.exe" tricks), no odd symbols.
add_filter(
	'wpcf7_upload_file_name',
	static function ( $filename, $original, $options = array() ) {
		if ( empty( $options['tag'] ) || ESPAM_UPLOAD_FIELD !== $options['tag']->name ) {
			return $filename;
		}
		$clean = sanitize_file_name( (string) preg_replace( '/\p{C}+/u', '', (string) $filename ) );
		return '' !== $clean && '' !== (string) pathinfo( $clean, PATHINFO_FILENAME ) ? $clean : 'prilog.' . pathinfo( (string) $filename, PATHINFO_EXTENSION );
	},
	20,
	3
);

/**
 * Inquiry flood guard: max 5 inquiries per visitor in 10 minutes and 40 per hour for the whole site.
 * The site sits behind Hostinger's CDN, so the visitor is identified by REMOTE_ADDR plus the last
 * X-Forwarded-For hop (the one the edge appends; a client can only prepend its own values).
 */
add_filter(
	'wpcf7_spam',
	static function ( $spam, $submission ) {
		if ( $spam || ! $submission || (int) $submission->get_contact_form()->id() !== espam_form_id() ) {
			return $spam;
		}
		$forwarded = array_map( 'trim', explode( ',', (string) ( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$visitor   = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . (string) end( $forwarded ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$limits    = array(
			'espam_rl_' . md5( $visitor ) => array( 5, 10 * MINUTE_IN_SECONDS ),
			'espam_rl_all'                => array( 40, HOUR_IN_SECONDS ),
		);
		foreach ( $limits as $key => [ $max, $window ] ) {
			$hits = (int) get_transient( $key );
			if ( $hits >= $max ) {
				$submission->add_spam_log( array( 'agent' => 'espam', 'reason' => 'Too many inquiries' ) );
				return true;
			}
			set_transient( $key, $hits + 1, $window );
		}
		return false;
	},
	20,
	2
);
