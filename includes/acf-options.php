<?php
/**
 * Add ACF options page.
 *
 * @package  Wp_Boilerplate_Nodes
 */

/**
 * This function will add acf fields to the post or page when added to the theme.
 * Format:
 *  add_filter(
 *      'acf_wpgraphql_locations',
 *      function( $locations ) {
 *          $locations[] = array(
 *          'operator' => '==',
 *          'param' => 'page_template',
 *          'value' => 'templates/landing-page.php',
 *          'field' => 'Page',
 *          );
 *
 *          return $locations;
 *      }
 *  );
 */
if ( class_exists( 'WPGraphQL\Registry\TypeRegistry' ) ) {
	if ( ! function_exists( 'wp_boilerplate_nodes_acf_wpgraphql_locations_filter_exec' ) ) {
		function wp_boilerplate_nodes_acf_wpgraphql_locations_filter_exec( \WPGraphQL\Registry\TypeRegistry $type_registry ) {
			if ( function_exists( 'acf_get_field_groups' ) && class_exists( '\WPGraphQL\ACF\Config' ) ) {
				$acf_fields = apply_filters( 'acf_wpgraphql_locations', array() );

				if ( ! empty( $acf_fields ) ) {
					foreach ( $acf_fields as $acf_field ) {
						$operator = $acf_field['operator'];
						$param    = $acf_field['param'];
						$value    = $acf_field['value'];
						$__field  = isset( $acf_field['field'] ) ? $acf_field['field'] : '';

						if ( empty( $__field ) ) {
							if ( false !== strpos( $param, 'page' ) ) {
								$__field = 'Page';
							}

							if ( false !== strpos( $param, 'post' ) ) {
								$__field = 'Post';
							}
						}

						$ConfigClass       = new \WPGraphQL\ACF\Config();
						$field_groups      = acf_get_field_groups();
						$post_field_groups = array();

						foreach ( $field_groups as $field_group ) {
							if ( ! empty( $field_group['location'] ) && is_array( $field_group['location'] ) ) {
								foreach ( $field_group['location'] as $locations ) {
									if ( ! empty( $locations ) && is_array( $locations ) ) {
										foreach ( $locations as $location ) {
											if ( '!=' === $location['operator'] ) {
												continue;
											}

											if ( $operator === $location['operator'] && $param === $location['param'] && $value === $location['value'] ) {
												$post_field_groups[] = $field_group;
											}
										}
									}
								}
							}
						}

						/**
						 * If no field groups are assigned to a specific post, we don't need to modify the Schema
						 */
						if ( empty( $post_field_groups ) ) {
							continue;
						}

						foreach ( $post_field_groups as $field_group ) {
							$field_name = isset( $field_group['graphql_field_name'] ) ? $field_group['graphql_field_name'] : $field_group['title'];
							$field_name = \WPGraphQL\ACF\Config::camel_case( $field_name );

							$field_group['type'] = 'group';
							$field_group['name'] = $field_name;
							$description         = ! empty( $field_group['description'] ) ? $field_group['description'] : '';
							$config              = array(
								'name'            => $field_name,
								'description'     => $description,
								'acf_field'       => $field_group,
								'acf_field_group' => null,
								'resolve'         => function ( $root ) use ( $field_group ) {
									return isset( $root ) ? $root : null;
								},
							);

							// Using a binding closure until this gets an API.
							$closure = function () use ( $field_name, $config, $type_registry, $__field ) {
								$this->type_registry = $type_registry;
								$this->register_graphql_field( $__field, $field_name, $config );
							};

							$binding = $closure->bindTo( $ConfigClass, 'WPGraphQL\ACF\Config' );
							$binding();
						}
					}
				}
			}
		}
	}

	add_action(
		'graphql_register_types',
		'wp_boilerplate_nodes_acf_wpgraphql_locations_filter_exec',
		25
	);
}

// Add globally unique ids to acf groups (posts) for cache merging.
if ( ! function_exists( 'wp_boilerplate_nodes_acf_fields_adds_global_id_posts' ) ) {
	function wp_boilerplate_nodes_acf_fields_adds_global_id_posts() {
		if ( function_exists( 'acf_get_field_groups' ) && class_exists( '\WPGraphQL\ACF\Config' ) ) {
			$graphql_post_types = get_post_types( array( 'show_in_graphql' => true ) );

			if ( empty( $graphql_post_types ) || ! is_array( $graphql_post_types ) ) {
				return;
			}

			foreach ( $graphql_post_types as $post_type ) {
				$field_groups = acf_get_field_groups(
					array(
						'post_type' => $post_type,
					)
				);

				if ( empty( $field_groups ) || ! is_array( $field_groups ) ) {
					continue;
				}

				$post_type_object = get_post_type_object( $post_type );

				foreach ( $field_groups as $field_group ) {
					$field_name      = isset( $field_group['graphql_field_name'] ) ? $field_group['graphql_field_name'] : \WPGraphQL\ACF\Config::camel_case( $field_name );
					$field_type_name = $post_type_object->graphql_single_name . '_' . ucfirst( $field_name );

					$config = array(
						'type'        => 'ID',
						'description' => __( 'Unique id useful for cache merging', 'wp-boilerplate-nodes' ),
						'resolve'     => function ( $root ) use ( $field_type_name ) {
							$id = null;

							if ( $root instanceof \WPGraphQL\Model\Post ) {
								$id = absint( $root->ID );
								$id = apply_filters( 'graphql_acf_get_root_id', $id, $root );
							}

							if ( empty( $id ) ) {
								return null;
							}

							return \GraphQLRelay\Relay::toGlobalId( $field_type_name, $id );
						},
					);

					register_graphql_field( $field_type_name, 'id', $config );
				}
			}
		}
	}
}

add_action(
	'graphql_register_types',
	'wp_boilerplate_nodes_acf_fields_adds_global_id_posts'
);

// Add globally unique ids to acf groups (terms) for cache merging.
if ( ! function_exists( 'wp_boilerplate_nodes_acf_fields_adds_global_id_terms' ) ) {
	function wp_boilerplate_nodes_acf_fields_adds_global_id_terms() {
		if ( function_exists( 'acf_get_field_groups' ) && class_exists( '\WPGraphQL\ACF\Config' ) ) {
			$graphql_taxonomies = \WPGraphQL::get_allowed_taxonomies();

			if ( empty( $graphql_taxonomies ) || ! is_array( $graphql_taxonomies ) ) {
				return;
			}

			foreach ( $graphql_taxonomies as $taxonomy ) {
				$field_groups = acf_get_field_groups(
					array(
						'taxonomy' => $taxonomy,
					)
				);

				if ( empty( $field_groups ) || ! is_array( $field_groups ) ) {
					continue;
				}

				$tax_object = get_taxonomy( $taxonomy );

				if ( empty( $tax_object ) || ! isset( $tax_object->graphql_single_name ) ) {
					return;
				}

				foreach ( $field_groups as $field_group ) {
					$field_name      = isset( $field_group['graphql_field_name'] ) ? $field_group['graphql_field_name'] : \WPGraphQL\ACF\Config::camel_case( $field_name );
					$field_type_name = $tax_object->graphql_single_name . '_' . ucfirst( $field_name );

					$config = array(
						'type'        => 'String',
						'description' => __( 'Unique id useful for cache merging', 'wp-boilerplate-nodes' ),
						'resolve'     => function ( $root ) use ( $field_type_name ) {
							$id = null;

							if ( $root instanceof \WPGraphQL\Model\Term ) {
								$id = acf_get_term_post_id( $root->taxonomyName, $root->term_id );
								$id = apply_filters( 'graphql_acf_get_root_id', $id, $root );
							}

							if ( empty( $id ) ) {
								return null;
							}

							return \GraphQLRelay\Relay::toGlobalId( $field_type_name, $id );
						},
					);

					register_graphql_field( $field_type_name, 'id', $config );
				}
			}
		}
	}
}

add_action(
	'graphql_register_types',
	'wp_boilerplate_nodes_acf_fields_adds_global_id_terms'
);

// Hide the ACF menu when not debugging.
if ( ! function_exists( 'wp_boilerplate_nodes_hide_acf_menu' ) ) {
	function wp_boilerplate_nodes_hide_acf_menu( $bool ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		return false;
	}
}

add_filter(
	'acf/settings/show_admin',
	'wp_boilerplate_nodes_hide_acf_menu'
);
