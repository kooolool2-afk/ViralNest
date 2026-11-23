<?php
/**
 * Media library: safe image uploads, responsive WebP sizes, alt text in both languages.
 *
 * Uploads are decoded and re-encoded with GD, which strips metadata and anything hidden in the
 * file. Only JPEG, PNG and WebP images are accepted. Files live in public/uploads/YYYY/MM/,
 * and public/uploads/.htaccess stops scripts from running there.
 */

declare(strict_types=1);

const VN_IMAGE_WIDTHS = array( 480, 960, 1600 );
const VN_UPLOAD_MAX   = 10 * 1024 * 1024;

function vn_media( int $id ): ?array {
	static $cache = array();
	if ( $id <= 0 ) {
		return null;
	}
	if ( ! array_key_exists( $id, $cache ) || ! empty( $GLOBALS['vn_media_dirty'] ) ) {
		unset( $GLOBALS['vn_media_dirty'] );
		$row = db_row( 'SELECT * FROM vn_media WHERE id = ?', array( $id ) );
		if ( $row ) {
			$row['sizes'] = json_decode( (string) $row['sizes'], true ) ?: array();
		}
		$cache[ $id ] = $row;
	}
	return $cache[ $id ];
}

function vn_media_url( ?array $m, int $width = 0 ): string {
	if ( ! $m ) {
		return '';
	}
	if ( $width && $m['sizes'] ) {
		$best = null;
		foreach ( $m['sizes'] as $w => $p ) {
			if ( (int) $w >= $width && ( null === $best || (int) $w < $best ) ) {
				$best = (int) $w;
			}
		}
		if ( null !== $best ) {
			return '/uploads/' . $m['sizes'][ $best ];
		}
	}
	return '/uploads/' . $m['path'];
}

/** Alt text for a language, falling back to English then to $fallback. */
function vn_image_alt( int $id, ?string $lang = null, string $fallback = '' ): string {
	$m = vn_media( $id );
	if ( ! $m ) {
		return $fallback;
	}
	$alt = 'ar' === ( $lang ?? vn_lang() ) ? $m['alt_ar'] : '';
	return $alt ?: ( $m['alt_en'] ?: $fallback );
}

/** Responsive <img> with srcset of the generated WebP sizes. */
function vn_image( int $id, string $size = 'card', string $class = '', bool $eager = false, string $sizes = '' ): string {
	$m = vn_media( $id );
	if ( ! $m ) {
		return '';
	}
	$srcset = array();
	foreach ( $m['sizes'] as $w => $p ) {
		$srcset[] = '/uploads/' . $p . ' ' . (int) $w . 'w';
	}
	$srcset[] = '/uploads/' . $m['path'] . ' ' . (int) $m['width'] . 'w';
	$sizes    = $sizes ?: ( 'full' === $size ? '(min-width: 1280px) 1200px, 100vw' : '(min-width: 1024px) 600px, (min-width: 720px) 50vw, 100vw' );
	$src      = vn_media_url( $m, 'full' === $size ? 1600 : 960 );
	return '<img src="' . eu( $src ) . '" srcset="' . ea( implode( ', ', array_unique( $srcset ) ) ) . '" sizes="' . ea( $sizes ) . '"'
		. ' width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '" alt="' . ea( vn_image_alt( $id ) ) . '"'
		. ( $class ? ' class="' . ea( $class ) . '"' : '' ) . ' loading="' . ( $eager ? 'eager' : 'lazy' ) . '" decoding="async"'
		. ( $eager ? ' fetchpriority="high"' : '' ) . '>';
}

/**
 * Store an uploaded image.
 *
 * @param array $file One entry of $_FILES.
 * @return array{ok:bool, id?:int, error?:string}
 */
function vn_media_upload( array $file, int $user_id = 0 ): array {
	if ( ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
		return array( 'ok' => false, 'error' => 'The upload did not arrive. Try again, or use a smaller file.' );
	}
	return vn_media_import( (string) $file['tmp_name'], (string) ( $file['name'] ?? 'image' ), $user_id );
}

/** Import an image file from disk (uploads and starter brand assets). */
function vn_media_import( string $tmp, string $name, int $user_id = 0, bool $brand = false ): array {
	if ( ! is_file( $tmp ) || filesize( $tmp ) > VN_UPLOAD_MAX ) {
		return array( 'ok' => false, 'error' => 'Images must be 10 MB or smaller.' );
	}
	$info  = @getimagesize( $tmp );
	$finfo = function_exists( 'finfo_open' ) ? finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $tmp ) : ( $info['mime'] ?? '' );
	$ok    = array( 'image/jpeg', 'image/png', 'image/webp' );
	if ( ! $info || ! in_array( $info['mime'], $ok, true ) || ! in_array( $finfo, $ok, true ) ) {
		return array( 'ok' => false, 'error' => 'Only JPG, PNG and WebP images are accepted.' );
	}
	if ( $info[0] * $info[1] > 40000000 ) {
		return array( 'ok' => false, 'error' => 'This image is too large in pixels (40 megapixels maximum).' );
	}
	$src = match ( $info['mime'] ) {
		'image/jpeg' => @imagecreatefromjpeg( $tmp ),
		'image/png'  => @imagecreatefrompng( $tmp ),
		default      => @imagecreatefromwebp( $tmp ),
	};
	if ( ! $src ) {
		return array( 'ok' => false, 'error' => 'This image could not be read.' );
	}
	if ( 'image/jpeg' === $info['mime'] && function_exists( 'exif_read_data' ) ) {
		$exif = @exif_read_data( $tmp );
		$rot  = array( 3 => 180, 6 => -90, 8 => 90 )[ $exif['Orientation'] ?? 0 ] ?? 0;
		if ( $rot ) {
			$src = imagerotate( $src, $rot, 0 );
		}
	}
	imagepalettetotruecolor( $src );
	imagealphablending( $src, false );
	imagesavealpha( $src, true );

	$dir  = gmdate( 'Y/m' );
	$abs  = VN_PUBLIC . '/uploads/' . $dir;
	if ( ! is_dir( $abs ) && ! mkdir( $abs, 0755, true ) && ! is_dir( $abs ) ) {
		return array( 'ok' => false, 'error' => 'The uploads folder is not writable.' );
	}
	$base = vn_slugify( pathinfo( $name, PATHINFO_FILENAME ) ) ?: 'image';
	$base = substr( $base, 0, 60 ) . '-' . bin2hex( random_bytes( 3 ) );

	$w0 = imagesx( $src );
	$h0 = imagesy( $src );
	// Full size capped at 2000 px wide.
	$full = vn_media_resize( $src, min( $w0, 2000 ) );
	imagewebp( $full, "$abs/$base.webp", 82 );
	$fw = imagesx( $full );
	$fh = imagesy( $full );
	$sizes = array();
	foreach ( VN_IMAGE_WIDTHS as $w ) {
		if ( $w < $fw ) {
			$im = vn_media_resize( $src, $w );
			imagewebp( $im, "$abs/$base-$w.webp", 80 );
			imagedestroy( $im );
			$sizes[ $w ] = "$dir/$base-$w.webp";
		}
	}
	if ( $full !== $src ) {
		imagedestroy( $full );
	}
	imagedestroy( $src );

	$id = db_insert(
		'vn_media',
		array(
			'path'        => "$dir/$base.webp",
			'mime'        => 'image/webp',
			'width'       => $fw,
			'height'      => $fh,
			'bytes'       => (int) filesize( "$abs/$base.webp" ),
			'sizes'       => json_encode( $sizes ),
			'alt_en'      => '',
			'alt_ar'      => '',
			'brand_asset' => $brand ? 1 : 0,
			'created_by'  => $user_id,
			'created_at'  => db_now(),
		)
	);
	return array( 'ok' => true, 'id' => $id );
}

function vn_media_resize( GdImage $src, int $width ): GdImage {
	$w0 = imagesx( $src );
	if ( $width >= $w0 ) {
		return $src;
	}
	$h  = (int) round( imagesy( $src ) * $width / $w0 );
	$im = imagecreatetruecolor( $width, $h );
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	imagecopyresampled( $im, $src, 0, 0, 0, 0, $width, $h, $w0, imagesy( $src ) );
	return $im;
}

/** Delete a media item and its files; references in projects/settings are cleared. */
function vn_media_delete( int $id ): void {
	$m = vn_media( $id );
	if ( ! $m ) {
		return;
	}
	foreach ( array_merge( array( $m['path'] ), array_values( $m['sizes'] ) ) as $p ) {
		$file = VN_PUBLIC . '/uploads/' . $p;
		if ( is_file( $file ) && str_starts_with( realpath( $file ) ?: '', realpath( VN_PUBLIC . '/uploads' ) ?: '//' ) ) {
			unlink( $file );
		}
	}
	db_q( 'DELETE FROM vn_media WHERE id = ?', array( $id ) );
	db_q( 'UPDATE vn_projects SET cover_id = 0 WHERE cover_id = ?', array( $id ) );
	foreach ( db_all( 'SELECT id, gallery FROM vn_projects WHERE FIND_IN_SET(?, gallery)', array( $id ) ) as $p ) {
		$g = array_diff( array_map( 'intval', explode( ',', $p['gallery'] ) ), array( $id ) );
		db_update( 'vn_projects', array( 'gallery' => implode( ',', $g ) ), array( 'id' => $p['id'] ) );
	}
	$s = vn_settings();
	foreach ( array( 'logo_id', 'favicon_id', 'og_image_id' ) as $k ) {
		if ( (int) $s[ $k ] === $id ) {
			vn_save_settings( array( $k => 0 ) );
		}
	}
	$GLOBALS['vn_media_dirty'] = true;
	vn_catalog_flush();
}
