<?php

/**
 * Code hoisted from wp-graphql-acf to query by acf group.
 */

// Working type registry.
if ( ! function_exists( 'wp_boilerplate_nodes_type_registry' ) ) {
	function wp_boilerplate_nodes_type_registry( $type_registry = null ) {
		global $__WPBNODES_TYPEREG;

		if ( $type_registry !== null ) {
			$__WPBNODES_TYPEREG = $type_registry;
		}

		return $__WPBNODES_TYPEREG;
	}
}

// Registered field names.
if ( ! function_exists( 'wp_boilerplate_nodes_registered_field_names' ) ) {
	function wp_boilerplate_nodes_registered_field_names( $field_name = null ) {
		global $__WPBNODES_REGFIELDNMS;
		if ( ! isset( $__WPBNODES_REGFIELDNMS ) ) {
			$__WPBNODES_REGFIELDNMS = array();
		}

		if ( $field_name !== null ) {
			$__WPBNODES_REGFIELDNMS[] = $field_name;
		}

		return $__WPBNODES_REGFIELDNMS;
	}
}

// Filter to resolve revision meta from parent derived from registered field names.
add_filter(
	'graphql_resolve_revision_meta_from_parent',
	function( $should, $object_id, $meta_key, $single ) {
		if ( in_array( $meta_key, wp_boilerplate_nodes_registered_field_names(), true ) ) {
			return false;
		}
		return $should;
	},
	10,
	4
);

/**
 * Determines whether a field group should be exposed to the GraphQL Schema. By default, field
 * groups will not be exposed to GraphQL.
 *
 * @return bool
 */
if ( ! function_exists( 'wp_boilerplate_nodes_should_field_group_show_in_graphql' ) ) {
	function wp_boilerplate_nodes_should_field_group_show_in_graphql( $field_group ) {

		// By default, field groups will not be exposed to GraphQL.
		$show = false;

		// If.
		if ( isset( $field_group['show_in_graphql'] ) && true === (bool) $field_group['show_in_graphql'] ) {
			$show = true;
		}

		/**
		 * Determine conditions where the GraphQL Schema should NOT be shown in GraphQL for
		 * root groups, not nested groups with parent.
		 */
		if ( ! isset( $field_group['parent'] ) ) {
			if (
			( isset( $field_group['active'] ) && true != $field_group['active'] ) ||
			( empty( $field_group['location'] ) || ! is_array( $field_group['location'] ) )
			) {
				$show = false;
			}
		}

		/**
		 * Whether a field group should show in GraphQL.
		 *
		 * @var boolean $show        Whether the field group should show in the GraphQL Schema
		 * @var array   $field_group The ACF Field Group
		 * @var Config  $this        The Config for the ACF Plugin
		 */
		return $show;
	}
}

// Converts score_case to CamelCase (technically PascalCase).
if ( ! function_exists( 'wp_boilerplate_nodes_camel_case' ) ) {
	function wp_boilerplate_nodes_camel_case( $str, array $no_strip = array() ) {
		// non-alpha and non-numeric characters become spaces.
		$str = preg_replace( '/[^a-z0-9' . implode( '', $no_strip ) . ']+/i', ' ', $str );
		$str = trim( $str );
		// Lowercase the string.
		$str = strtolower( $str );
		// uppercase the first character of each word.
		$str = ucwords( $str );
		// Replace spaces.
		$str = str_replace( ' ', '', $str );
		// Lowecase first letter.
		$str = lcfirst( $str );

		return $str;
	}
}

// Determin the id for acf field.
if ( ! function_exists( 'wp_boilerplate_nodes_get_acf_field_id' ) ) {
	function wp_boilerplate_nodes_get_acf_field_id( $root ) {
		$id = null;

		switch ( true ) {
			case $root instanceof \WPGraphQL\Model\Term:
				$id = acf_get_term_post_id( $root->taxonomyName, $root->term_id );
				break;
			case $root instanceof \WPGraphQL\Model\Post:
				$id = absint( $root->ID );
				break;
			case $root instanceof \WPGraphQL\Model\MenuItem:
				$id = absint( $root->menuItemId );
				break;
			case $root instanceof \WPGraphQL\Model\Menu:
				$id = acf_get_term_post_id( 'nav_menu', $root->menuId );
				break;
			case $root instanceof \WPGraphQL\Model\User:
				$id = 'user_' . absint( $root->userId );
				break;
			case $root instanceof \WPGraphQL\Model\Comment:
				$id = 'comment_' . absint( $root->comment_ID );
				break;
			case is_array( $root ) && ! empty( $root['type'] ) && 'options_page' === $root['type']:
				$id = $root['post_id'];
				break;
			default:
				$id = null;
				break;
		}

		return $id;
	}
}

// Determin the id for acf field.
if ( ! function_exists( 'wp_boilerplate_nodes_get_acf_field_global_id' ) ) {
	function wp_boilerplate_nodes_get_acf_field_global_id( $field_name, $root = null, $id = null ) {
		if ( $root !== null ) {
			$id = wp_boilerplate_nodes_get_acf_field_id( $root );
		}

		if ( $id === null ) {
			$id = get_the_ID();
		}

		return \GraphQLRelay\Relay::toGlobalId( 'acf_unique_' . $field_name, $id );
	}
}

// Returns the ACF field value given $root.
if ( ! function_exists( 'wp_boilerplate_nodes_get_acf_field_value' ) ) {
	function wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field, $format = false ) {
		$value = null;
		$id    = null;

		if ( is_array( $root ) && ! ( ! empty( $root['type'] ) && 'options_page' === $root['type'] ) ) {

			if ( isset( $root[ $acf_field['key'] ] ) ) {
				$value = $root[ $acf_field['key'] ];

				if ( 'wysiwyg' === $acf_field['type'] ) {
					$value = apply_filters( 'the_content', $value );
				}
			}
		} else {

			$id = wp_boilerplate_nodes_get_acf_field_id( $root );

			// Filters the root ID, allowing additional Models the ability to provide a way to resolve their ID.
			$id = apply_filters( 'graphql_acf_get_root_id', $id, $root );

			if ( empty( $id ) ) {
				return null;
			}

			$format = false;

			if ( 'wysiwyg' === $acf_field['type'] ) {
				$format = true;
			}

			// Check if cloned field and retrieve the key accordingly.
			if ( ! empty( $acf_field['_clone'] ) ) {
				$key = $acf_field['__key'];
			} else {
				$key = $acf_field['key'];
			}

			$field_value = get_field( $key, $id, $format );

			$value = ! empty( $field_value ) ? $field_value : null;
		}

		// Filters the returned ACF field value.
		return apply_filters( 'graphql_acf_field_value', $value, $acf_field, $root, $id );
	}
}

// List of supported fields.
if ( ! function_exists( 'wp_boilerplate_nodes_get_supported_fields' ) ) {
	function wp_boilerplate_nodes_get_supported_fields() {
		$supported_fields = array(
			'text',
			'textarea',
			'number',
			'range',
			'email',
			'url',
			'password',
			'image',
			'file',
			'wysiwyg',
			'oembed',
			'gallery',
			'select',
			'checkbox',
			'radio',
			'button_group',
			'true_false',
			'link',
			'post_object',
			'page_link',
			'relationship',
			'taxonomy',
			'user',
			'google_map',
			'date_picker',
			'date_time_picker',
			'time_picker',
			'color_picker',
			'group',
			'repeater',
			'flexible_content',
		);

		// Filter the supported fields.
		return apply_filters( 'wpgraphql_acf_supported_fields', $supported_fields );
	}
}

// Registers a acf field to a graphql type.
if ( ! function_exists( 'wp_boilerplate_nodes_register_graphql_field' ) ) {
	function wp_boilerplate_nodes_register_graphql_field( $type_name, $field_name, $config ) {
		$type_registry = wp_boilerplate_nodes_type_registry();

		$acf_field = isset( $config['acf_field'] ) ? $config['acf_field'] : null;
		$acf_type  = isset( $acf_field['type'] ) ? $acf_field['type'] : null;

		if ( empty( $acf_type ) ) {
			return false;
		}

		// Filter the field config for custom field types.
		$field_config = apply_filters(
			'wpgraphql_acf_register_graphql_field',
			array(
				'type'    => null,
				'resolve' => isset( $config['resolve'] ) && is_callable( $config['resolve'] ) ? $config['resolve'] : function( $root, $args, $context, $info ) use ( $acf_field ) {
					$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

					return ! empty( $value ) ? $value : null;
				},
			),
			$type_name,
			$field_name,
			$config
		);

		switch ( $acf_type ) {
			case 'button_group':
			case 'color_picker':
			case 'email':
			case 'text':
			case 'message':
			case 'oembed':
			case 'password':
			case 'wysiwyg':
			case 'url':
				/**
				 * Even though Selects and Radios in ACF can _technically_ be an integer
				 * we're choosing to always cast as a string because with
				 * GraphQL we can't return different types.
				 */
				$field_config['type'] = 'String';
				break;
			case 'textarea':
				$field_config['type']    = 'String';
				$field_config['resolve'] = function( $root ) use ( $acf_field ) {
					$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

					if ( ! empty( $acf_field['new_lines'] ) ) {
						if ( 'wpautop' === $acf_field['new_lines'] ) {
							$value = wpautop( $value );
						}
						if ( 'br' === $acf_field['new_lines'] ) {
							$value = nl2br( $value );
						}
					}
					return $value;

				};
				break;
			case 'select':
				/**
				 * If the select field is configured to not allow multiple values
				 * the field will return a string, but if it is configured to allow
				 * multiple values it will return a list of strings, and an empty array
				 * if no values are set.
				 *
				 * @see: https://github.com/wp-graphql/wp-graphql-acf/issues/25
				 */
				if ( empty( $acf_field['multiple'] ) ) {
					$field_config['type'] = 'String';
				} else {
					$field_config['type']    = array( 'list_of' => 'String' );
					$field_config['resolve'] = function( $root ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						return ! empty( $value ) && is_array( $value ) ? $value : array();
					};
				}
				break;
			case 'radio':
				$field_config['type'] = 'String';
				break;
			case 'range':
				$field_config['type'] = 'Integer';
				break;
			case 'number':
				$field_config['type'] = 'Float';
				break;
			case 'true_false':
				$field_config['type'] = 'Boolean';
				break;
			case 'date_picker':
			case 'time_picker':
			case 'date_time_picker':
				$field_config = array(
					'type'    => 'String',
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {

						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field, true );

						if ( ! empty( $value ) && ! empty( $acf_field['return_format'] ) ) {
							$value = date( $acf_field['return_format'], strtotime( $value ) );
						}
						return ! empty( $value ) ? $value : null;
					},
				);
				break;
			case 'relationship':
				if ( isset( $acf_field['post_type'] ) && is_array( $acf_field['post_type'] ) ) {

					$field_type_name = $type_name . '_' . ucfirst( wp_boilerplate_nodes_camel_case( $acf_field['name'] ) );

					if ( $type_registry->get_type( $field_type_name ) == $field_type_name ) {
						$type = $field_type_name;
					} else {
						$type_names = array();
						foreach ( $acf_field['post_type'] as $post_type ) {
							if ( in_array( $post_type, get_post_types( array( 'show_in_graphql' => true ) ), true ) ) {
								$type_names[ $post_type ] = get_post_type_object( $post_type )->graphql_single_name;
							}
						}

						if ( empty( $type_names ) ) {
							$type = 'PostObjectUnion';
						} else {
							register_graphql_union_type(
								$field_type_name,
								array(
									'typeNames'   => $type_names,
									'resolveType' => function( $value ) use ( $type_names, $type_registry ) {
										$post_type_object = get_post_type_object( $value->post_type );
										return ! empty( $post_type_object->graphql_single_name ) ? $type_registry->get_type( $post_type_object->graphql_single_name ) : null;
									},
								)
							);

							$type = $field_type_name;
						}
					}
				} else {
					$type = 'PostObjectUnion';
				}

				$field_config = array(
					'type'    => array( 'list_of' => $type ),
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$relationship = array();
						$value        = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $post_id ) {
								$post_object = get_post( $post_id );
								if ( $post_object instanceof \WP_Post ) {
									$post_model = new Post( $post_object );
									$relationship[] = $post_model;
								}
							}
						}

						return isset( $value ) ? $relationship : null;

					},
				);
				break;
			case 'page_link':
			case 'post_object':
				if ( isset( $acf_field['post_type'] ) && is_array( $acf_field['post_type'] ) ) {
					$field_type_name = $type_name . '_' . ucfirst( wp_boilerplate_nodes_camel_case( $acf_field['name'] ) );
					if ( $type_registry->get_type( $field_type_name ) == $field_type_name ) {
						$type = $field_type_name;
					} else {
						$type_names = array();
						foreach ( $acf_field['post_type'] as $post_type ) {
							if ( in_array( $post_type, \get_post_types( array( 'show_in_graphql' => true ) ), true ) ) {
								$type_names[ $post_type ] = get_post_type_object( $post_type )->graphql_single_name;
							}
						}

						if ( empty( $type_names ) ) {
							$field_config['type'] = null;
							break;
						}

						register_graphql_union_type(
							$field_type_name,
							array(
								'typeNames'   => $type_names,
								'resolveType' => function( $value ) use ( $type_names, $type_registry ) {
									$post_type_object = get_post_type_object( $value->post_type );
									return ! empty( $post_type_object->graphql_single_name ) ? $type_registry->get_type( $post_type_object->graphql_single_name ) : null;
								},
							)
						);

						$type = $field_type_name;
					}
				} else {
					$type = 'PostObjectUnion';
				}

				// If the field is allowed to be a multi select
				if ( 0 !== $acf_field['multiple'] ) {
					$type = array( 'list_of' => $type );
				}

				$field_config = array(
					'type'    => $type,
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						$return = array();
						if ( ! empty( $value ) ) {
							if ( is_array( $value ) ) {
								foreach ( $value as $id ) {
									$post = get_post( $id );
									if ( ! empty( $post ) ) {
										$return[] = new Post( $post );
									}
								}
							} else {
								$post = get_post( absint( $value ) );
								if ( ! empty( $post ) ) {
									$return[] = new Post( $post );
								}
							}
						}

						// If the field is allowed to be a multi select
						if ( 0 !== $acf_field['multiple'] ) {
							$return = ! empty( $return ) ? $return : null;
						} else {
							$return = ! empty( $return[0] ) ? $return[0] : null;
						}

						/**
						 * This hooks allows for filtering of the post object source. In case an non-core defined
						 * post-type is being targeted.
						 */
						return apply_filters(
							'graphql_acf_post_object_source',
							$return,
							$value,
							$context,
							$info
						);

					},
				);
				break;
			case 'link':
				$field_type_name = 'ACF_Link';
				if ( $type_registry->get_type( $field_type_name ) == $field_type_name ) {
					$field_config['type'] = $field_type_name;
					break;
				}

				register_graphql_object_type(
					$field_type_name,
					array(
						'description' => __( 'ACF Link field', 'wp-boilerplate-nodes' ),
						'fields'      => array(
							'url'    => array(
								'type'        => 'String',
								'description' => __( 'The url of the link', 'wp-boilerplate-nodes' ),
							),
							'title'  => array(
								'type'        => 'String',
								'description' => __( 'The title of the link', 'wp-boilerplate-nodes' ),
							),
							'target' => array(
								'type'        => 'String',
								'description' => __( 'The target of the link (_blank, etc)', 'wp-boilerplate-nodes' ),
							),
						),
					)
				);
				$field_config['type'] = $field_type_name;
				break;
			case 'image':
			case 'file':
				$field_config = array(
					'type'    => 'MediaItem',
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						return \WPGraphQL\Data\DataSource::resolve_post_object( (int) $value, $context );
					},
				);
				break;
			case 'checkbox':
				$field_config = array(
					'type'    => array( 'list_of' => 'String' ),
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						return is_array( $value ) ? $value : null;
					},
				);
				break;
			case 'gallery':
				$field_config = array(
					'type'    => array( 'list_of' => 'MediaItem' ),
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );
						$gallery = array();
						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $image ) {
								$post_object = get_post( (int) $image );
								if ( $post_object instanceof \WP_Post ) {
									$post_model = new \WPGraphQL\Model\Post( $post_object );
									$gallery[]  = $post_model;
								}
							}
						}

						return isset( $value ) ? $gallery : null;
					},
				);
				break;
			case 'user':
				$type = 'User';

				if ( isset( $acf_field['multiple'] ) && 1 === $acf_field['multiple'] ) {
					$type = array( 'list_of' => $type );
				}

				$field_config = array(
					'type'    => $type,
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						$return = array();
						if ( ! empty( $value ) ) {
							if ( is_array( $value ) ) {
								foreach ( $value as $id ) {
									$user = get_user_by( 'id', $id );
									if ( ! empty( $user ) ) {
										$user = new \WPGraphQL\Model\User( $user );
										if ( 'private' !== $user->get_visibility() ) {
											$return[] = $user;
										}
									}
								}
							} else {
								$user = get_user_by( 'id', absint( $value ) );
								if ( ! empty( $user ) ) {
									$user = new \WPGraphQL\Model\User( $user );
									if ( 'private' !== $user->get_visibility() ) {
										$return[] = $user;
									}
								}
							}
						}

						// If the field is allowed to be a multi select
						if ( 0 !== $acf_field['multiple'] ) {
							$return = ! empty( $return ) ? $return : null;
						} else {
							$return = ! empty( $return[0] ) ? $return[0] : null;
						}

						return $return;
					},
				);
				break;
			case 'taxonomy':
				$type = 'TermObjectUnion';

				if ( isset( $acf_field['taxonomy'] ) ) {
					$tax_object = get_taxonomy( $acf_field['taxonomy'] );
					if ( isset( $tax_object->graphql_single_name ) ) {
						$type = $tax_object->graphql_single_name;
					}
				}

				$is_multiple = isset( $acf_field['field_type'] ) && in_array( $acf_field['field_type'], array( 'checkbox', 'multi_select' ) );

				$field_config = array(
					'type'    => $is_multiple ? array( 'list_of' => $type ) : $type,
					'resolve' => function( $root, $args, $context, $info ) use ( $acf_field, $is_multiple ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );
						/**
						 * If this is multiple, the value will most likely always be an array.
						 * If it isn't, we want to return a single term id.
						 */
						if ( ! empty( $value ) && is_array( $value ) ) {
							foreach ( $value as $term ) {
								$terms[] = \WPGraphQL\Data\DataSource::resolve_term_object( (int) $term, $context );
							}
							return $terms;
						} else {
							return \WPGraphQL\Data\DataSource::resolve_term_object( (int) $value, $context );
						}
					},
				);
				break;

			// Accordions are not represented in the GraphQL Schema.
			case 'accordion':
				$field_config = null;
				break;
			case 'group':
				$field_type_name = $type_name . '_' . ucfirst( wp_boilerplate_nodes_camel_case( $acf_field['name'] ) );

				if ( $type_registry->get_type( $field_type_name ) ) {
					$field_config['type'] = $field_type_name;
					break;
				}

				register_graphql_object_type(
					$field_type_name,
					array(
						'description' => __( 'Field Group', 'wp-boilerplate-nodes' ),
						'fields'      => array(
							'id'             => array(
								'type'    => 'ID',
								'resolve' => function( $source ) use ( $field_type_name ) {
									return wp_boilerplate_nodes_get_acf_field_global_id( $field_type_name, $source );
								},
							),
							'fieldGroupName' => array(
								'type'    => 'String',
								'resolve' => function( $source ) use ( $acf_field ) {
									return ! empty( $acf_field['name'] ) ? $acf_field['name'] : null;
								},
							),
						),
					)
				);

				wp_boilerplate_nodes_add_field_group_fields( $acf_field, $field_type_name );

				$field_config['type'] = $field_type_name;
				break;

			case 'google_map':
				$field_type_name = 'ACF_GoogleMap';
				if ( $type_registry->get_type( $field_type_name ) == $field_type_name ) {
					$field_config['type'] = $field_type_name;
					break;
				}

				$fields = array(
					'streetAddress' => array(
						'type'        => 'String',
						'description' => __( 'The street address associated with the map', 'wp-boilerplate-nodes' ),
						'resolve'     => function( $root ) {
							return isset( $root['address'] ) ? $root['address'] : null;
						},
					),
					'latitude'      => array(
						'type'        => 'Float',
						'description' => __( 'The latitude associated with the map', 'wp-boilerplate-nodes' ),
						'resolve'     => function( $root ) {
							return isset( $root['lat'] ) ? $root['lat'] : null;
						},
					),
					'longitude'     => array(
						'type'        => 'Float',
						'description' => __( 'The longitude associated with the map', 'wp-boilerplate-nodes' ),
						'resolve'     => function( $root ) {
							return isset( $root['lng'] ) ? $root['lng'] : null;
						},
					),
				);

				// ACF 5.8.6 added more data to Google Maps field value
				// https://www.advancedcustomfields.com/changelog/
				if ( \acf_version_compare( acf_get_db_version(), '>=', '5.8.6' ) ) {
									$fields += array(
										'streetName'   => array(
											'type'        => 'String',
											'description' => __( 'The street name associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['street_name'] ) ? $root['street_name'] : null;
											},
										),
										'streetNumber' => array(
											'type'        => 'String',
											'description' => __( 'The street number associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['street_number'] ) ? $root['street_number'] : null;
											},
										),
										'city'         => array(
											'type'        => 'String',
											'description' => __( 'The city associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['city'] ) ? $root['city'] : null;
											},
										),
										'state'        => array(
											'type'        => 'String',
											'description' => __( 'The state associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['state'] ) ? $root['state'] : null;
											},
										),
										'stateShort'   => array(
											'type'        => 'String',
											'description' => __( 'The state abbreviation associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['state_short'] ) ? $root['state_short'] : null;
											},
										),
										'postCode'     => array(
											'type'        => 'String',
											'description' => __( 'The post code associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['post_code'] ) ? $root['post_code'] : null;
											},
										),
										'country'      => array(
											'type'        => 'String',
											'description' => __( 'The country associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['country'] ) ? $root['country'] : null;
											},
										),
										'countryShort' => array(
											'type'        => 'String',
											'description' => __( 'The country abbreviation associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['country_short'] ) ? $root['country_short'] : null;
											},
										),
										'placeId'      => array(
											'type'        => 'String',
											'description' => __( 'The country associated with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['place_id'] ) ? $root['place_id'] : null;
											},
										),
										'zoom'         => array(
											'type'        => 'String',
											'description' => __( 'The zoom defined with the map', 'wp-boilerplate-nodes' ),
											'resolve'     => function( $root ) {
													return isset( $root['zoom'] ) ? $root['zoom'] : null;
											},
										),
									);
				}

				register_graphql_object_type(
					$field_type_name,
					array(
						'description' => __( 'Google Map field', 'wp-boilerplate-nodes' ),
						'fields'      => $fields,
					)
				);
				$field_config['type'] = $field_type_name;
				break;
			case 'repeater':
				$field_type_name = $type_name . '_' . wp_boilerplate_nodes_camel_case( $acf_field['name'] );

				if ( $type_registry->get_type( $field_type_name ) ) {
					$field_config['type'] = $field_type_name;
					break;
				}

				register_graphql_object_type(
					$field_type_name,
					array(
						'description' => __( 'Field Group', 'wp-boilerplate-nodes' ),
						'fields'      => array(
							'fieldGroupName' => array(
								'type'    => 'String',
								'resolve' => function( $source ) use ( $acf_field ) {
									return ! empty( $acf_field['name'] ) ? $acf_field['name'] : null;
								},
							),
						),
						'resolve'     => function( $source ) use ( $acf_field ) {
							$repeater = wp_boilerplate_nodes_get_acf_field_value( $source, $acf_field );

							return ! empty( $repeater ) ? $repeater : array();
						},
					)
				);

				wp_boilerplate_nodes_add_field_group_fields( $acf_field, $field_type_name );

				$field_config['type'] = array( 'list_of' => $field_type_name );
				break;

			/**
			 * Flexible content fields should return a Union of the Layouts that can be configured.
			 *
			 * Example Query of a flex field with the name "flex_field" and 2 groups
			 *
			 * {
			 *   post {
			 *      flexField {
			 *         ...on GroupOne {
			 *           textField
			 *           textAreaField
			 *         }
			 *         ...on GroupTwo {
			 *           imageField {
			 *             id
			 *             title
			 *           }
			 *         }
			 *      }
			 *   }
			 * }
			 */
			case 'flexible_content':
				$field_config    = null;
				$field_type_name = $type_name . '_' . ucfirst( wp_boilerplate_nodes_camel_case( $acf_field['name'] ) );
				if ( $type_registry->get_type( $field_type_name ) ) {
					$field_config['type'] = $field_type_name;
					break;
				}

				if ( ! empty( $acf_field['layouts'] ) && is_array( $acf_field['layouts'] ) ) {

					$union_types = array();
					foreach ( $acf_field['layouts'] as $layout ) {

						$flex_field_layout_name = ! empty( $layout['name'] ) ? ucfirst( wp_boilerplate_nodes_camel_case( $layout['name'] ) ) : null;
						$flex_field_layout_name = ! empty( $flex_field_layout_name ) ? $field_type_name . '_' . $flex_field_layout_name : null;

						/**
						 * If there are no layouts defined for the Flex Field
						 */
						if ( empty( $flex_field_layout_name ) ) {
							continue;
						}

						$layout_type = $type_registry->get_type( $flex_field_layout_name );

						if ( $layout_type ) {
							$union_types[ $layout['name'] ] = $layout_type;
						} else {

							register_graphql_object_type(
								$flex_field_layout_name,
								array(
									'description' => __( 'Group within the flex field', 'wp-boilerplate-nodes' ),
									'fields'      => array(
										'fieldGroupName' => array(
											'type'    => 'String',
											'resolve' => function( $source ) use ( $flex_field_layout_name ) {
												return ! empty( $flex_field_layout_name ) ? $flex_field_layout_name : null;
											},
										),
									),
								)
							);

							$union_types[ $layout['name'] ] = $flex_field_layout_name;

							$layout['parent']          = $acf_field;
							$layout['show_in_graphql'] = isset( $acf_field['show_in_graphql'] ) ? (bool) $acf_field['show_in_graphql'] : true;
							wp_boilerplate_nodes_add_field_group_fields( $layout, $flex_field_layout_name );
						}
					}

					register_graphql_union_type(
						$field_type_name,
						array(
							'typeNames'   => $union_types,
							'resolveType' => function( $value ) use ( $union_types ) {
								return isset( $union_types[ $value['acf_fc_layout'] ] ) ? $type_registry->get_type( $union_types[ $value['acf_fc_layout'] ] ) : null;
							},
						)
					);

					$field_config['type']    = array( 'list_of' => $field_type_name );
					$field_config['resolve'] = function( $root, $args, $context, $info ) use ( $acf_field ) {
						$value = wp_boilerplate_nodes_get_acf_field_value( $root, $acf_field );

						return ! empty( $value ) ? $value : array();
					};
				}
				break;
			default:
				break;
		}

		if ( empty( $field_config ) || empty( $field_config['type'] ) ) {
			return null;
		}

		$config = array_merge( $config, $field_config );

		wp_boilerplate_nodes_registered_field_names( $acf_field['name'] );

		add_action(
			'wp_boilerplate_nodes_acf_group_register',
			function() use ( $type_registry, $type_name, $field_name, $config ) {
				$type_registry->register_field( $type_name, $field_name, $config );
			}
		);
	}
}

// Given a field group array, this adds the fields to the specified Type in the Schema.
if ( ! function_exists( 'wp_boilerplate_nodes_add_field_group_fields' ) ) {
	function wp_boilerplate_nodes_add_field_group_fields( $field_group, $type_name ) {

		/**
		 * If the field group has the show_in_graphql setting configured, respect it's setting
		 * otherwise default to true (for nested fields)
		 */
		$field_group['show_in_graphql'] = isset( $field_group['show_in_graphql'] ) ? (bool) $field_group['show_in_graphql'] : true;

		/**
		 * Determine if the field group should be exposed
		 * to graphql
		 */
		if ( ! wp_boilerplate_nodes_should_field_group_show_in_graphql( $field_group ) ) {
			return;
		}

		/**
		 * Get the fields in the group.
		 */
		$acf_fields = ! empty( $field_group['sub_fields'] ) ? $field_group['sub_fields'] : acf_get_fields( $field_group );

		/**
		 * If there are no fields, bail
		 */
		if ( empty( $acf_fields ) || ! is_array( $acf_fields ) ) {
			return;
		}

		/**
		 * Stores field keys to prevent duplicate field registration for cloned fields
		 */
		$processed_keys = array();

		/**
		 * Loop over the fields and register them to the Schema
		 */
		foreach ( $acf_fields as $acf_field ) {
			if ( in_array( $acf_field['key'], $processed_keys, true ) ) {
				continue;
			} else {
				$processed_keys[] = $acf_field['key'];
			}

			/**
			 * Setup data for register_graphql_field
			 */
			$explicit_name   = ! empty( $acf_field['graphql_field_name'] ) ? $acf_field['graphql_field_name'] : null;
			$name            = empty( $explicit_name ) && ! empty( $acf_field['name'] ) ? wp_boilerplate_nodes_camel_case( $acf_field['name'] ) : $explicit_name;
			$show_in_graphql = isset( $acf_field['show_in_graphql'] ) ? (bool) $acf_field['show_in_graphql'] : true;
			$description     = isset( $acf_field['instructions'] ) ? $acf_field['instructions'] : __( 'ACF Field added to the Schema by WPGraphQL ACF' );

			/**
			 * If the field is missing a name or a type,
			 * we can't add it to the Schema.
			 */
			if (
			empty( $name ) ||
			true != $show_in_graphql
			) {

				/**
				 * Uncomment line below to determine what fields are not going to be output
				 * in the Schema.
				 */
				continue;
			}

			$config = array(
				'name'            => $name,
				'description'     => $description,
				'acf_field'       => $acf_field,
				'acf_field_group' => $field_group,
			);

			wp_boilerplate_nodes_register_graphql_field( $type_name, $name, $config );

		}
	}
}

add_action(
	'graphql_register_types',
	function( $type_registry ) {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return;
		}

		wp_boilerplate_nodes_type_registry( $type_registry );

		// Get all the groups.
		$groups = acf_get_field_groups();

		if ( ! empty( $groups ) ) {
			// AcfGroup type.
			register_graphql_object_type(
				'AcfGroup',
				array(
					'description' => __( 'All the field groups', 'wp-boilerplate-nodes' ),
				)
			);

			// Register the field for queries.
			register_graphql_field(
				'RootQuery',
				'AcfGroups',
				array(
					'type'        => 'AcfGroup',
					'description' => __( 'All the field groups', 'wp-boilerplate-nodes' ),
					'args'        => array(
						'post_id'      => array(
							'type'        => 'Integer',
							'description' => __( 'Post database id to get the fields for', 'wp-bolierplate-nodes' ),
						),
						'term_id'      => array(
							'type'        => 'Integer',
							'description' => __( 'Term database id to get the fields for', 'wp-bolierplate-nodes' ),
						),
						'menu_item_id' => array(
							'type'        => 'Integer',
							'description' => __( 'Menu Item database id to get the fields for', 'wp-bolierplate-nodes' ),
						),
						'user_id'      => array(
							'type'        => 'Integer',
							'description' => __( 'User database id to get the fields for', 'wp-bolierplate-nodes' ),
						),
						'comment_id'   => array(
							'type'        => 'Integer',
							'description' => __( 'Comment database id to get the fields for', 'wp-bolierplate-nodes' ),
						),
					),
					'resolve'     => function( $root, $args, $context, $info ) {
						if ( isset( $root ) ) {
							return $root;
						}

						switch ( true ) {
							case ! empty( $args['post_id'] ):
								return \WPGraphQL\Data\DataSource::resolve_post_object( $args['post_id'], $context );

							case ! empty( $args['term_id'] ):
								return \WPGraphQL\Data\DataSource::resolve_term_object( $args['term_id'], $context );

							case ! empty( $args['menu_item_id'] ):
								return \WPGraphQL\Data\DataSource::resolve_menu_item( $args['menu_item_id'], $context );

							case ! empty( $args['user_id'] ):
								return \WPGraphQL\Data\DataSource::resolve_user( $args['user_id'], $context );

							case ! empty( $args['comment_id'] ):
								return \WPGraphQL\Data\DataSource::resolve_comment( $args['comment_id'], $context );
						}
					},
				)
			);

			// Loop over the groups.
			foreach ( $groups as $field_group ) {
				$field_name = isset( $field_group['graphql_field_name'] ) ? $field_group['graphql_field_name'] : wp_boilerplate_nodes_camel_case( $field_group['title'] );

				$field_group['type'] = 'group';
				$field_group['name'] = $field_name;
				$config              = array(
					'name'            => $field_name,
					'description'     => $field_group['description'],
					'acf_field'       => $field_group,
					'acf_field_group' => null,
					'resolve'         => function( $root ) use ( $field_group ) {
						return isset( $root ) ? $root : null;
					},
				);

				wp_boilerplate_nodes_register_graphql_field( 'AcfGroup', $field_name, $config );
			}
		}

		do_action( 'wp_boilerplate_nodes_acf_group_register' );
	}
);
