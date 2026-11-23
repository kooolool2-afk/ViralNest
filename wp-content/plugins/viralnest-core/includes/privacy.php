<?php
/**
 * WordPress privacy tools integration: export and erase quote requests by email address
 * (Tools > Export Personal Data / Erase Personal Data).
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['viralnest-leads'] = array(
			'exporter_friendly_name' => 'ViralNest quote requests',
			'callback'               => function ( $email ) {
				global $wpdb;
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vn_leads WHERE email = %s", $email ) );
				$data = array();
				foreach ( $rows as $r ) {
					$data[] = array(
						'group_id'    => 'viralnest-leads',
						'group_label' => 'Quote requests',
						'item_id'     => 'lead-' . $r->id,
						'data'        => array(
							array( 'name' => 'Reference', 'value' => vn_lead_ref( $r->id ) ),
							array( 'name' => 'Submitted (UTC)', 'value' => $r->created_at ),
							array( 'name' => 'Services', 'value' => implode( '; ', vn_lead_service_names( $r ) ) ),
							array( 'name' => 'Email', 'value' => $r->email ),
							array( 'name' => 'Phone', 'value' => $r->phone_e164 ),
							array( 'name' => 'Name', 'value' => $r->name ),
							array( 'name' => 'Business', 'value' => $r->company ),
							array( 'name' => 'Website', 'value' => $r->website ),
							array( 'name' => 'Message', 'value' => $r->message ),
						),
					);
				}
				return array( 'data' => $data, 'done' => true );
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $erasers ) {
		$erasers['viralnest-leads'] = array(
			'eraser_friendly_name' => 'ViralNest quote requests',
			'callback'             => function ( $email ) {
				global $wpdb;
				$n = vn_delete_leads_where( $wpdb->prepare( 'email = %s', $email ) );
				return array( 'items_removed' => $n > 0, 'items_retained' => false, 'messages' => array(), 'done' => true );
			},
		);
		return $erasers;
	}
);
