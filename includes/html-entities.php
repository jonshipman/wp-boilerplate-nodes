<?php

// Sourced from https://gist.github.com/esamattis/54abd801b078056f9e646d63d47ccc4c.

/**
 * WordPress does automatic HTML entity encoding but so does React which
 * results in double encoding. This reverts the one from WordPress when
 * the content is requested using wp-graphql making your life as a React
 * dev easier.
 */
class HTMLEntities {

	public function init() {
		 // Requires this to be merged https://github.com/wp-graphql/wp-graphql-acf/pull/74
		add_filter(
			'graphql_acf_field_value',
			array( $this, 'decode_acf_entities' ),
			10,
			2
		);
		add_filter(
			'graphql_resolve_field',
			array( $this, 'decode_entities' ),
			10,
			8
		);
	}
	/**
	 * Decode HTML entities from ACF fields
	 */
	function decode_acf_entities( $value, $acf_field ) {
		$text_types = array( 'textarea', 'text' );
		if ( in_array( $acf_field['type'], $text_types ) ) {
			return html_entity_decode( $value );
		}
		if ( 'link' === $acf_field['type'] && ! empty( $value ) ) {
			$value['title'] = html_entity_decode( $value['title'] );
		}
		return $value;
	}
	/**
	 * Decode HTML entities from other applicable fields.
	 *
	 * XXX This is not complete.
	 */
	function decode_entities(
		$result,
		$source,
		$args,
		$context,
		$info,
		$type_name,
		$field_key
	) {
		if ( ! \is_string( $result ) || ! $result ) {
			return $result;
		}
		$decode = false;
		if ( $source instanceof \WPGraphQL\Model\Post ) {
			if ( 'title' === $field_key ) {
				$decode = true;
			}
		}
		if ( $source instanceof \WPGraphQL\Model\MenuItem ) {
			if ( 'title' === $field_key ) {
				$decode = true;
			}
			if ( 'label' === $field_key ) {
				$decode = true;
			}
		}
		if ( is_array( $source ) ) {
			if ( 'title' === $field_key ) {
				$decode = true;
			}

			if ( false !== stripos( $field_key, 'meta' ) ) {
				$decode = true;
			}
		}
		if ( 'generalSettingsTitle' === $field_key ) {
			$decode = true;
		}

		if ( 'generalSettingsDescription' === $field_key ) {
			$decode = true;
		}
		if ( $decode ) {
			return \html_entity_decode( trim( $result ) );
		}
		return $result;
	}
}

if ( apply_filters( 'wp_boilerplate_nodes_gql_entity_decode', true ) ) {
	( new HTMLEntities() )->init();
}
