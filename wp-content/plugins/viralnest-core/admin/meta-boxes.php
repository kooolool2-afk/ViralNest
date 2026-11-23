<?php
/**
 * Editor meta boxes for services and portfolio projects (English = core fields,
 * Arabic + structured fields = post meta), and an Arabic alt-text field for images.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'vn_service', 'vn_project' ) as $type ) {
			add_meta_box( 'vn_arabic', 'Arabic version (العربية)', 'vn_box_arabic', $type, 'normal', 'high' );
			add_meta_box( 'vn_seo', 'Search and social sharing', 'vn_box_seo', $type, 'normal', 'low' );
		}
		add_meta_box( 'vn_service_settings', 'Service settings, pricing and quote form', 'vn_box_service', 'vn_service', 'normal', 'high' );
		add_meta_box( 'vn_project_details', 'Project details', 'vn_box_project', 'vn_project', 'normal', 'high' );
		add_meta_box( 'vn_help', 'How this works', 'vn_box_help', array( 'vn_service', 'vn_project' ), 'side', 'high' );
	}
);

/* English field labels on the core editor. */
add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return in_array( $post->post_type, array( 'vn_service', 'vn_project' ), true ) ? 'English title' : $text;
	},
	10,
	2
);
add_action(
	'edit_form_after_title',
	function ( $post ) {
		if ( in_array( $post->post_type, array( 'vn_service', 'vn_project' ), true ) ) {
			echo '<p class="vn-lang-label"><strong>English</strong> — the title, main text and excerpt below are the English version. The Arabic version is in its own box.</p>';
		}
	}
);

function vn_box_help( $post ) {
	if ( 'vn_service' === $post->post_type ) {
		echo '<p>A <strong>top-level service</strong> gets its own page and appears in the services list and the quote form.</p><p>Set a <strong>Parent</strong> (Page Attributes) to make it a <strong>sub-service</strong>: it is listed on its parent page and selectable in the quote form.</p><p>Use <strong>Order</strong> to sort. New services saved by content editors go to <em>Pending Review</em> and appear only after an owner publishes them.</p>';
	} else {
		echo '<p>Use the <strong>Featured image</strong> as the card cover. Publish only facts the client approved.</p><p><strong>Outcomes</strong> stay hidden until you tick "Verified" and record the evidence.</p>';
	}
}

function vn_box_arabic( $post ) {
	wp_nonce_field( 'vn_meta_' . $post->ID, 'vn_meta_nonce' );
	$type       = get_post_type_object( $post->post_type );
	$can_pub    = current_user_can( $type->cap->publish_posts );
	$status     = get_post_meta( $post->ID, '_vn_ar_status', true ) ?: 'draft';
	echo '<div dir="rtl" class="vn-ar">';
	echo '<p><label for="vn_ar_title"><strong>العنوان</strong> (Arabic title)</label><input type="text" class="widefat" id="vn_ar_title" name="vn_ar[title]" value="' . esc_attr( get_post_meta( $post->ID, '_vn_ar_title', true ) ) . '"></p>';
	echo '<p><label for="vn_ar_excerpt"><strong>ملخص</strong> (Arabic summary)</label><textarea class="widefat" rows="2" id="vn_ar_excerpt" name="vn_ar[excerpt]">' . esc_textarea( get_post_meta( $post->ID, '_vn_ar_excerpt', true ) ) . '</textarea></p>';
	echo '<p><strong>المحتوى</strong> (Arabic main text)</p>';
	wp_editor( (string) get_post_meta( $post->ID, '_vn_ar_content', true ), 'vn_ar_content', array( 'textarea_name' => 'vn_ar[content]', 'textarea_rows' => 8, 'media_buttons' => false, 'tinymce' => array( 'directionality' => 'rtl' ) ) );
	echo '</div>';
	echo '<p><label for="vn_ar_status"><strong>Arabic publication</strong></label> <select id="vn_ar_status" name="vn_ar[status]"><option value="draft" ' . selected( $status, 'draft', false ) . '>Draft (hidden on /ar/)</option><option value="publish" ' . selected( $status, 'publish', false ) . ( $can_pub ? '' : ' disabled' ) . '>Published on /ar/</option></select> ';
	echo '<span class="description">The Arabic version is public only when the post is published, this is set to Published, and the Arabic title is filled in. Otherwise /ar/ visitors see a "not available in Arabic yet" page linking to English.</span></p>';
}

function vn_box_seo( $post ) {
	foreach ( array( 'en' => 'English', 'ar' => 'Arabic' ) as $l => $label ) {
		$dir = 'ar' === $l ? 'rtl' : 'ltr';
		echo '<p><label for="vn_seo_title_' . esc_attr( $l ) . '">' . esc_html( $label ) . ' page title (optional)</label><input type="text" dir="' . esc_attr( $dir ) . '" class="widefat" id="vn_seo_title_' . esc_attr( $l ) . '" name="vn_meta[seo_title_' . esc_attr( $l ) . ']" value="' . esc_attr( get_post_meta( $post->ID, '_vn_seo_title_' . $l, true ) ) . '" maxlength="70"></p>';
		echo '<p><label for="vn_seo_desc_' . esc_attr( $l ) . '">' . esc_html( $label ) . ' meta description (optional)</label><textarea dir="' . esc_attr( $dir ) . '" class="widefat" rows="2" id="vn_seo_desc_' . esc_attr( $l ) . '" name="vn_meta[seo_desc_' . esc_attr( $l ) . ']" maxlength="170">' . esc_textarea( get_post_meta( $post->ID, '_vn_seo_desc_' . $l, true ) ) . '</textarea></p>';
	}
	echo '<p><strong>Social sharing image</strong> (optional; defaults to the featured image, then the site default)</p>';
	vn_media_field( 'vn_meta[og_image]', (int) get_post_meta( $post->ID, '_vn_og_image', true ), 'Social sharing image' );
}

function vn_box_service( $post ) {
	$m = fn( $k ) => get_post_meta( $post->ID, '_vn_' . $k, true );
	echo '<table class="form-table"><tr><th>Icon</th><td><select name="vn_meta[icon]">';
	foreach ( vn_icon_choices() as $icon ) {
		printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $icon ), selected( $m( 'icon' ), $icon, false ) );
	}
	echo '</select> <span class="description">Shown on top-level service cards.</span></td></tr>';
	echo '<tr><th>Quote form</th><td><input type="hidden" name="vn_meta[quote_enabled]" value="0"><label><input type="checkbox" name="vn_meta[quote_enabled]" value="1" ' . checked( '0' !== $m( 'quote_enabled' ), true, false ) . '> Selectable in the Request a Quote form (when published)</label></td></tr>';
	echo '<tr><th>Public price</th><td><input type="hidden" name="vn_meta[price_visible]" value=""><label><input type="checkbox" name="vn_meta[price_visible]" value="1" ' . checked( (bool) $m( 'price_visible' ), true, false ) . '> Show a price on the website</label><p class="description">Off by default: the page explains that proposals depend on scope. Turn on only after an owner approves the amount.</p>';
	echo '<p><select name="vn_meta[price_mode]" aria-label="Price type"><option value="starting" ' . selected( $m( 'price_mode' ), 'starting', false ) . '>Starting from</option><option value="fixed" ' . selected( $m( 'price_mode' ), 'fixed', false ) . '>Fixed price</option></select> ';
	echo '<select name="vn_meta[price_currency]" aria-label="Currency">';
	foreach ( array_filter( array_map( 'trim', explode( ',', vn_settings( 'currencies' ) ) ) ) as $cur ) {
		printf( '<option %2$s>%1$s</option>', esc_html( $cur ), selected( $m( 'price_currency' ), $cur, false ) );
	}
	echo '</select> <input type="number" min="0" step="0.01" name="vn_meta[price_amount]" value="' . esc_attr( $m( 'price_amount' ) ) . '" aria-label="Amount" placeholder="Amount"></p>';
	echo '<p><label>Price note (EN) <input type="text" class="regular-text" name="vn_meta[en_price_note]" value="' . esc_attr( $m( 'en_price_note' ) ) . '" placeholder="e.g. per month"></label> <label dir="rtl">ملاحظة السعر (AR) <input type="text" class="regular-text" dir="rtl" name="vn_meta[ar_price_note]" value="' . esc_attr( $m( 'ar_price_note' ) ) . '"></label></p></td></tr>';
	echo '<tr><th>Ad spend</th><td><input type="hidden" name="vn_meta[ad_spend_note]" value=""><label><input type="checkbox" name="vn_meta[ad_spend_note]" value="1" ' . checked( (bool) $m( 'ad_spend_note' ), true, false ) . '> Show "Ad spend is paid separately from management fees"</label></td></tr>';
	echo '</table>';
}

function vn_box_project( $post ) {
	$m        = fn( $k ) => get_post_meta( $post->ID, '_vn_' . $k, true );
	$selected = array_map( 'intval', (array) $m( 'services' ) );
	echo '<table class="form-table">';
	echo '<tr><th><label for="vn_client">Client or project name</label></th><td><input type="text" class="regular-text" id="vn_client" name="vn_meta[client_name]" value="' . esc_attr( $m( 'client_name' ) ) . '"><br><input type="hidden" name="vn_meta[client_approved]" value=""><label><input type="checkbox" name="vn_meta[client_approved]" value="1" ' . checked( (bool) $m( 'client_approved' ), true, false ) . '> The client approved showing this name publicly</label></td></tr>';
	echo '<tr><th>Industry</th><td><input type="text" class="regular-text" name="vn_meta[en_industry]" value="' . esc_attr( $m( 'en_industry' ) ) . '" placeholder="English, e.g. Food & beverage" aria-label="Industry (English)"> <input type="text" class="regular-text" dir="rtl" name="vn_meta[ar_industry]" value="' . esc_attr( $m( 'ar_industry' ) ) . '" placeholder="بالعربي" aria-label="Industry (Arabic)"><p class="description">Used for the portfolio industry filter.</p></td></tr>';
	echo '<tr><th>Scope</th><td><textarea class="widefat" rows="2" name="vn_meta[en_scope]" aria-label="Scope (English)" placeholder="English">' . esc_textarea( $m( 'en_scope' ) ) . '</textarea><textarea class="widefat" rows="2" dir="rtl" name="vn_meta[ar_scope]" aria-label="Scope (Arabic)" placeholder="بالعربي">' . esc_textarea( $m( 'ar_scope' ) ) . '</textarea></td></tr>';
	echo '<tr><th>Services delivered</th><td><div class="vn-checklist">';
	foreach ( vn_service_tree( 'en' ) as $node ) {
		printf( '<label><input type="checkbox" name="vn_meta[services][]" value="%d" %s> <strong>%s</strong></label>', (int) $node['post']->ID, checked( in_array( $node['post']->ID, $selected, true ), true, false ), esc_html( $node['post']->post_title ) );
		foreach ( $node['children'] as $c ) {
			printf( '<label class="vn-indent"><input type="checkbox" name="vn_meta[services][]" value="%d" %s> %s</label>', (int) $c->ID, checked( in_array( $c->ID, $selected, true ), true, false ), esc_html( $c->post_title ) );
		}
	}
	echo '</div><p class="description">Drives the portfolio service filter and preselects the quote form from this project.</p></td></tr>';
	echo '<tr><th>Featured</th><td><input type="hidden" name="vn_meta[featured]" value=""><label><input type="checkbox" name="vn_meta[featured]" value="1" ' . checked( (bool) $m( 'featured' ), true, false ) . '> Show in "Selected work" on the homepage</label></td></tr>';
	echo '<tr><th>Gallery</th><td><div data-vn-gallery><input type="hidden" name="vn_meta[gallery]" value="' . esc_attr( $m( 'gallery' ) ) . '"><div class="vn-gallery">';
	foreach ( vn_project_gallery( $post->ID ) as $id ) {
		echo wp_get_attachment_image( $id, 'thumbnail' );
	}
	echo '</div><button type="button" class="button" data-vn-gallery-pick>Choose gallery images</button> <span class="description">Order follows your selection. Add alt text (EN, and AR in the "Arabic alt text" field) to each image.</span></div></td></tr>';
	echo '<tr><th>Outcomes</th><td><p class="description"><strong>Internal until verified.</strong> Record results only with evidence. They stay hidden on the website until "Verified" is ticked.</p>';
	echo '<textarea class="widefat" rows="3" name="vn_meta[en_outcomes]" aria-label="Outcomes (English)" placeholder="English, one result per line">' . esc_textarea( $m( 'en_outcomes' ) ) . '</textarea>';
	echo '<textarea class="widefat" rows="3" dir="rtl" name="vn_meta[ar_outcomes]" aria-label="Outcomes (Arabic)" placeholder="بالعربي، نتيجة في كل سطر">' . esc_textarea( $m( 'ar_outcomes' ) ) . '</textarea>';
	echo '<p><label for="vn_evidence">Evidence (internal, never shown)</label><input type="text" class="widefat" id="vn_evidence" name="vn_meta[outcomes_evidence]" value="' . esc_attr( $m( 'outcomes_evidence' ) ) . '" placeholder="e.g. Meta Ads Manager export 2026-03, client approval email"></p>';
	echo '<input type="hidden" name="vn_meta[outcomes_verified]" value=""><label><input type="checkbox" name="vn_meta[outcomes_verified]" value="1" ' . checked( (bool) $m( 'outcomes_verified' ), true, false ) . '> Verified and approved by the client for publication</label></td></tr>';
	echo '</table>';
}

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! in_array( $post->post_type, array( 'vn_service', 'vn_project' ), true ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['vn_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vn_meta_nonce'] ) ), 'vn_meta_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$ar = wp_unslash( $_POST['vn_ar'] ?? array() );
		update_post_meta( $post_id, '_vn_ar_title', sanitize_text_field( $ar['title'] ?? '' ) );
		update_post_meta( $post_id, '_vn_ar_excerpt', sanitize_textarea_field( $ar['excerpt'] ?? '' ) );
		update_post_meta( $post_id, '_vn_ar_content', wp_kses_post( $ar['content'] ?? '' ) );
		$type    = get_post_type_object( $post->post_type );
		$wanted  = 'publish' === ( $ar['status'] ?? '' ) ? 'publish' : 'draft';
		$current = get_post_meta( $post_id, '_vn_ar_status', true ) ?: 'draft';
		if ( ! current_user_can( $type->cap->publish_posts ) ) {
			$wanted = $current; // Only owners change Arabic publication; editors' changes wait for review.
		}
		update_post_meta( $post_id, '_vn_ar_status', $wanted );

		$meta = wp_unslash( $_POST['vn_meta'] ?? array() );
		$text = array( 'seo_title_en', 'seo_title_ar', 'en_price_note', 'ar_price_note', 'client_name', 'en_industry', 'ar_industry', 'outcomes_evidence', 'price_mode', 'price_currency', 'icon' );
		$area = array( 'seo_desc_en', 'seo_desc_ar', 'en_scope', 'ar_scope', 'en_outcomes', 'ar_outcomes' );
		$flag = array( 'price_visible', 'ad_spend_note', 'client_approved', 'featured', 'outcomes_verified' );
		foreach ( $text as $k ) {
			if ( isset( $meta[ $k ] ) ) {
				update_post_meta( $post_id, '_vn_' . $k, sanitize_text_field( $meta[ $k ] ) );
			}
		}
		foreach ( $area as $k ) {
			if ( isset( $meta[ $k ] ) ) {
				update_post_meta( $post_id, '_vn_' . $k, sanitize_textarea_field( $meta[ $k ] ) );
			}
		}
		foreach ( $flag as $k ) {
			if ( array_key_exists( $k, $meta ) ) {
				update_post_meta( $post_id, '_vn_' . $k, empty( $meta[ $k ] ) ? '' : '1' );
			}
		}
		if ( isset( $meta['quote_enabled'] ) ) {
			update_post_meta( $post_id, '_vn_quote_enabled', empty( $meta['quote_enabled'] ) ? '0' : '1' );
		}
		if ( isset( $meta['price_amount'] ) ) {
			$amt = trim( (string) $meta['price_amount'] );
			update_post_meta( $post_id, '_vn_price_amount', is_numeric( $amt ) && $amt >= 0 ? $amt : '' );
		}
		if ( isset( $meta['og_image'] ) ) {
			update_post_meta( $post_id, '_vn_og_image', absint( $meta['og_image'] ) );
		}
		if ( isset( $meta['gallery'] ) ) {
			update_post_meta( $post_id, '_vn_gallery', implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $meta['gallery'] ) ) ) ) );
		}
		if ( 'vn_project' === $post->post_type ) {
			update_post_meta( $post_id, '_vn_services', array_values( array_filter( array_map( 'absint', (array) ( $meta['services'] ?? array() ) ) ) ) );
		}
	},
	10,
	2
);

/* New services from users without publish rights go to review, never straight to the site. */
add_filter(
	'wp_insert_post_data',
	function ( $data ) {
		if ( in_array( $data['post_type'], array( 'vn_service', 'vn_project' ), true ) && 'publish' === $data['post_status'] ) {
			$type = get_post_type_object( $data['post_type'] );
			if ( is_admin() && $type && ! current_user_can( $type->cap->publish_posts ) ) {
				$data['post_status'] = 'pending';
			}
		}
		return $data;
	}
);

/* Arabic alt text on images. */
add_filter(
	'attachment_fields_to_edit',
	function ( $fields, $post ) {
		if ( wp_attachment_is_image( $post->ID ) ) {
			$fields['vn_alt_ar'] = array(
				'label' => 'Arabic alt text',
				'input' => 'html',
				'html'  => '<input type="text" dir="rtl" class="text" name="attachments[' . (int) $post->ID . '][vn_alt_ar]" value="' . esc_attr( get_post_meta( $post->ID, '_vn_alt_ar', true ) ) . '">',
			);
		}
		return $fields;
	},
	10,
	2
);
add_filter(
	'attachment_fields_to_save',
	function ( $post, $attachment ) {
		if ( isset( $attachment['vn_alt_ar'] ) ) {
			update_post_meta( $post['ID'], '_vn_alt_ar', sanitize_text_field( $attachment['vn_alt_ar'] ) );
		}
		return $post;
	},
	10,
	2
);

/* Restrict uploads to common image and document types (no SVG, no executables). */
add_filter(
	'upload_mimes',
	function ( $mimes ) {
		$allowed = array( 'jpg|jpeg|jpe', 'png', 'gif', 'webp', 'avif', 'pdf', 'mp4', 'ico' );
		return array_intersect_key( $mimes, array_flip( $allowed ) ) + array( 'avif' => 'image/avif', 'webp' => 'image/webp' );
	}
);
