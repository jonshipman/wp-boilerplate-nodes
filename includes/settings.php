<?php
/**
 * Adds a settings screen.
 *
 * @package  Wp_Boilerplate_Nodes
 */

class HeadlessWpSettings {
	private $settings;

	// Start up the class.
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'init', array( $this, 'settings_fields' ), 20 );
		add_action( 'init', array( $this, 'register' ), 30 );
		add_action( 'graphql_register_types', array( $this, 'graphql' ) );
	}

	public function settings_fields() {
		$settings = array();

		$this->settings = apply_filters( 'wp_boilerplate_nodes_settings', $settings );
	}

	private function get_key() {
		return apply_filters( 'wp_boilerplate_nodes_settings_key', 'wp_boilerplate_nodes' );
	}

	// Create the admin menu.
	public function admin_menu() {
		add_options_page(
			apply_filters( 'wp_boilerplate_nodes_settings_page_label', __( 'Headless Theme Settings', 'wp-boilerplate-nodes' ) ),
			apply_filters( 'wp_boilerplate_nodes_settings_menu_label', __( 'Headless Theme', 'wp-boilerplate-nodes' ) ),
			'manage_options',
			$this->get_key(),
			array( $this, 'display_options_page' )
		);
	}

	// Markup for options page.
	public function display_options_page() {
		ob_start();
		settings_fields( $this->get_key() );
		do_settings_sections( $this->get_key() );
		submit_button();
		$settings = ob_get_clean();

		printf(
			'<div class="wrap"><h2>%s</h2><form action="options.php" method="post">%s</form></div>',
			esc_html( get_admin_page_title() ),
			$settings
		);
	}

	// Builds our settings.
	public function admin_init() {
		foreach ( $this->settings as $group => $options ) {
			add_settings_section(
				$group,
				$options['label'],
				'__return_false',
				$this->get_key()
			);

			foreach ( $options['fields'] as $field => $field_options ) {
				if ( isset( $field_options['callback'] ) && is_callable( $field_options['callback'] ) && ! is_string( $field_options['callback'] ) ) {
					$callback = function() use ( $field, $field_options ) {
						$field_options['callback']( $field );
					};
				} else {
					$callback = function() use ( $field, $field_options ) {
						$value = get_option( $field );

						if ( isset( $field_options['args'] ) && isset( $field_options['args']['type'] ) ) {
							if ( 'boolean' === $field_options['args']['type'] ) {
								printf(
									'<select name="%s" id="%s"><option value="0" %s>No</option><option value="1" %s>Yes</option></select>',
									esc_attr( $field ),
									esc_attr( $field ),
									selected( $value, '0', false ),
									selected( $value, '1', false )
								);

								return;
							}
						}

						if ( isset( $field_options['display_as'] ) && 'textarea' === $field_options['display_as'] ) {
							printf(
								'<textarea class="large-text" rows="5" type="text" name="%s" id="%s">%s</textarea>',
								esc_attr( $field ),
								esc_attr( $field ),
								esc_attr( $value )
							);

							return;
						}

						printf(
							'<input class="large-text" type="text" name="%s" id="%s" value="%s"> ',
							esc_attr( $field ),
							esc_attr( $field ),
							esc_attr( $value )
						);
					};
				}
				add_settings_field(
					$field,
					$field_options['label'],
					$callback,
					$this->get_key(),
					$group,
					array( 'label_for' => $field )
				);
			}
		}
	}

	// Register the settings on the frontend too for wp-graphql.
	public function register() {
		foreach ( $this->settings as $group => $options ) {
			foreach ( $options['fields'] as $field => $field_options ) {
				if ( ! empty( $field_options['args'] ) ) {
					register_setting(
						$this->get_key(),
						$field,
						$field_options['args']
					);
				} else {
					register_setting(
						$this->get_key(),
						$field,
						'sanitize_text_field'
					);
				}
			}
		}
	}

	// Adds unique id to the settings type.
	public function graphql() {
		$types = array( 'Settings', 'HeadlessWpSettings', 'DiscussionSettings', 'GeneralSettings', 'ReadingSettings', 'WritingSettings' );

		if ( function_exists( 'register_graphql_field' ) ) {
			foreach ( $types as $type ) {
				register_graphql_field(
					$type,
					'id',
					array(
						'type'        => 'ID',
						'description' => __( 'Id for merging in cache', 'wp-boilerplate-nodes' ),
						'resolve'     => function () use ( $type ) {
							return \GraphQLRelay\Relay::toGlobalId( 'setting', $type );
						},
					)
				);
			}
		}
	}
}

if ( apply_filters( 'wp_boilerplate_nodes_settings_init', true ) ) {
	new HeadlessWpSettings();
}
