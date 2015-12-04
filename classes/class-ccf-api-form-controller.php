<?php

class CCF_API_Form_Controller extends WP_REST_Controller {

	/**
	 * Array of field attributes with sanitization/escaping callbacks
	 *
	 * @var array
	 * @since 7.0
	 */
	protected $field_attribute_keys;

	/**
	 * Array of field choice attributes with sanitization/escaping callbacks
	 *
	 * @var array
	 * @since 7.0
	 */
	protected $choice_attribute_keys;

	/**
	 * Setup instance variables
	 * 
	 * @since 7.0
	 */
	public function __construct() {
		$this->field_attribute_keys = apply_filters( 'ccf_field_attributes', array(
			'type' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'slug' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'placeholder' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'className' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'label' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'description' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'value' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'required' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'showDate' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'addressType' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'siteKey' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'secretKey' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'phoneFormat' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'emailConfirmation' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'showTime' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'heading' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'subheading' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'html' => array(
				'sanitize' => 'wp_kses_post',
				'escape' => 'wp_kses_post',
			),
			'maxFileSize' => array(
				'sanitize' => 'intval',
				'escape' => 'intval',
			),
			'fileExtensions' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
		) );

		$this->choice_attribute_keys = apply_filters( 'ccf_choice_attributes', array(
			'label' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'value' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'selected' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
		) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 7.0
	 */
	public function register_routes() {
		$version = '1';
		$namespace = 'ccf/v' . $version;

		register_rest_route( $namespace, '/forms', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(),
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( true ),
			),
		) );

		register_rest_route( $namespace, '/forms/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
				    	'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
				    	'default'      => false,
					),
				),
			),
		) );

		register_rest_route( $namespace, '/forms/(?P<id>[\d]+)/submissions', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_submissions' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
				    	'default'      => 'view',
					),
				),
			),
		) );

		register_rest_route( $namespace, '/forms/(?P<id>[\d]+)/fields', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_fields' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
				    	'default'      => 'view',
					),
				),
			),
		) );
	}

	/**
	 * Create field choices and attach them to fields. Not an API route.
	 *
	 * @param array $choices
	 * @param int $field_id
	 * @since 7.0
	 */
	public function _create_and_map_choices( $choices, $field_id ) {
		$new_choices = array();

		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['label'] ) ) {
				if ( empty( $choice['ID'] ) ) {
					$args = array(
						'post_title' => $choice['label'] . '-' . (int) $field_id,
						'post_status' => 'publish',
						'post_parent' => $field_id,
						'post_type' => 'ccf_choice',
					);

					$choice_id = wp_insert_post( $args );
				} else {
					$choice_id = $choice['ID'];
				}

				if ( ! is_wp_error( $choice_id ) ) {
					foreach ( $this->choice_attribute_keys as $key => $functions ) {
						if ( isset( $choice[$key] ) ) {
							update_post_meta( $choice_id, 'ccf_choice_' . $key, call_user_func( $functions['sanitize'], $choice[$key] ) );
						}
					}

					$new_choices[] = $choice_id;
				}
			} else {
				if ( ! empty( $choice['ID'] ) ) {
					wp_delete_post( $choice['ID'], true );
				}
			}
		}

		$current_choices = get_post_meta( $field_id, 'ccf_attached_choices', true );
		$new_choices = array_map( 'absint', $new_choices );

		if ( ! empty( $current_choices ) ) {
			$deleted_choices = array_diff( $current_choices, $new_choices );
			foreach ( $deleted_choices as $choice_id ) {
				wp_delete_post( $choice_id, true );
			}
		}

		update_post_meta( $field_id, 'ccf_attached_choices', array_map( 'absint', $new_choices ) );
	}

	/**
	 * Create fields and map them to forms. Not an API route.
	 *
	 * @param array $fields
	 * @param int $form_id
	 * @since 7.0
	 */
	public function _create_and_map_fields( $fields, $form_id ) {
		$new_fields = array();

		foreach ( $fields as $field ) {
			if ( empty( $field['ID'] ) ) {
				$args = array(
					'post_title' => $field['slug'] . '-' . (int) $form_id,
					'post_status' => 'publish',
					'post_parent' => $form_id,
					'post_type' => 'ccf_field',
				);

				$field_id = wp_insert_post( $args );
			} else {
				$field_id = $field['ID'];
			}

			if ( ! is_wp_error( $field_id ) ) {
				foreach ( $this->field_attribute_keys as $key => $functions ) {
					if ( isset( $field[$key] ) ) {
						update_post_meta( $field_id, 'ccf_field_' . $key, call_user_func( $functions['sanitize'], $field[$key] ) );
					}
				}

				if ( isset( $field['choices'] ) ) {
					$choices = ( empty( $field['choices'] ) ) ? array() : $field['choices'];
					$this->_create_and_map_choices( $choices, $field_id );
				}

				$new_fields[] = $field_id;
			}
		}

		$current_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );
		$new_fields = array_map( 'absint', $new_fields );

		if ( ! empty( $current_fields ) ) {
			$deleted_fields = array_diff( $current_fields, $new_fields );
			foreach ( $deleted_fields as $field_id ) {
				wp_delete_post( $field_id, true );
			}
		}

		update_post_meta( $form_id, 'ccf_attached_fields', $new_fields );
	}

	/**
	 * Create/update a form
	 * 
	 * @param array $data
	 * @since 7.0
	 * @return array
	 */
	public function _create_item( $data ) {

		$args = array(
			'post_title' => $data['title'],
			'post_type' => 'ccf_form',
			'post_status' => 'publish',
		);

		if ( ! empty( $data['id'] ) ) {
			$args['ID'] = (int) $data['id'];
		}

		$result = wp_insert_post( $args );

		if ( ! is_wp_error( $result ) ) {
			if ( empty( $data['fields'] ) ) {
				$data['fields'] = array();
			}

			$this->_create_and_map_fields( $data['fields'], $result );

			if ( isset( $data['buttonText'] ) ) {
				update_post_meta( $result, 'ccf_form_buttonText', sanitize_text_field( $data['buttonText'] ) );
			}

			if ( isset( $data['description'] ) ) {
				update_post_meta( $result, 'ccf_form_description', sanitize_text_field( $data['description'] ) );
			}

			if ( isset( $data['completionActionType'] ) ) {
				update_post_meta( $result, 'ccf_form_completion_action_type', sanitize_text_field( $data['completionActionType'] ) );
			}

			if ( isset( $data['completionMessage'] ) ) {
				update_post_meta( $result, 'ccf_form_completion_message', sanitize_text_field( $data['completionMessage'] ) );
			}

			if ( isset( $data['completionRedirectUrl'] ) ) {
				update_post_meta( $result, 'ccf_form_completion_redirect_url', esc_url_raw( $data['completionRedirectUrl'] ) );
			}

			if ( isset( $data['sendEmailNotifications'] ) ) {
				update_post_meta( $result, 'ccf_form_send_email_notifications', (bool) $data['sendEmailNotifications'] );
			}

			if ( isset( $data['pause'] ) ) {
				update_post_meta( $result, 'ccf_form_pause', (bool) $data['pause'] );
			}

			if ( isset( $data['pauseMessage'] ) ) {
				update_post_meta( $result, 'ccf_form_pause_message', sanitize_text_field( $data['pauseMessage'] ) );
			}

			if ( isset( $data['emailNotificationAddresses'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_addresses', sanitize_text_field( $data['emailNotificationAddresses'] ) );
			}

			if ( isset( $data['emailNotificationFromType'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_type', sanitize_text_field( $data['emailNotificationFromType'] ) );
			}

			if ( isset( $data['emailNotificationFromAddress'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_address', sanitize_text_field( $data['emailNotificationFromAddress'] ) );
			}

			if ( isset( $data['emailNotificationFromField'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_field', sanitize_text_field( $data['emailNotificationFromField'] ) );
			}

			if ( isset( $data['emailNotificationSubjectType'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_subject_type', sanitize_text_field( $data['emailNotificationSubjectType'] ) );
			}

			if ( isset( $data['emailNotificationSubject'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_subject', sanitize_text_field( $data['emailNotificationSubject'] ) );
			}

			if ( isset( $data['emailNotificationSubjectField'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_subject_field', sanitize_text_field( $data['emailNotificationSubjectField'] ) );
			}

			if ( isset( $data['emailNotificationFromNameType'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_name_type', sanitize_text_field( $data['emailNotificationFromNameType'] ) );
			}

			if ( isset( $data['emailNotificationFromName'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_name', sanitize_text_field( $data['emailNotificationFromName'] ) );
			}

			if ( isset( $data['emailNotificationFromNameField'] ) ) {
				update_post_meta( $result, 'ccf_form_email_notification_from_name_field', sanitize_text_field( $data['emailNotificationFromNameField'] ) );
			}

			return $result;
		} else {
			return new WP_Error( 'create-form-error' );
		}
	}

	/**
	 * Ensure value is boolean
	 *
	 * @param mixed $value
	 * @since 7.0
	 * @return bool
	 */
	protected function boolval( $value ) {
		return !! $value;
	}

	/**
	 * Get a collection of submissions
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_submissions( $request ) {
		$params = $request->get_params();

		$data = array();

		if ( ! empty( $params['id'] ) ) {
			$page = 1;
			if ( ! empty( $params['page'] ) ) {
				$page = $params['page'];
			}

			$query = new WP_Query( array(
				'post_type'   => 'ccf_submission',
				'page'        => (int) $page,
				'post_status' => 'publish',
				'post_parent' => (int) $params['id']
			) );

			foreach ( $query->posts as $item ) {
				$data[] = $this->prepare_submission_for_response( $item );
			}
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get a collection of fields
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_fields( $request ) {
		$params = $request->get_params();

		$fields = array();

		if ( ! empty( $params['id'] ) ) {
			$fields = $this->_get_fields( $params['id'] );
		}

		return new WP_REST_Response( $fields, 200 );
	}

	/**
	 * Prepare submission for the REST response
	 *
	 * @param  int $item
	 * @param  int $item_id
	 * @since  7.0
	 * @return array
	 */
	public function prepare_submission_for_response( $item ) {
		$data = array(
			'id'           => $item->ID,
			'date'         => $this->prepare_date_response( $item->post_date_gmt, $item->post_date ),
			'date_gmt'     => $this->prepare_date_response( $item->post_date_gmt ),
			'guid'         => array(
				'raw'      => $item->guid,
			),
			'modified'     => $this->prepare_date_response( $item->post_modified_gmt, $item->post_modified ),
			'modified_gmt' => $this->prepare_date_response( $item->post_modified_gmt ),
			'slug'         => $item->post_name,
			'status'       => $item->post_status,
			'type'         => $item->post_type,
			'link'         => get_permalink( $item->ID ),
			'title'        => array(
				'raw'      => $item->post_title,
				'rendered' => get_the_title( $item->ID ),
			),
		);

		$data['data'] = get_post_meta( $item->ID, 'ccf_submission_data', true );
		$data['ip_address'] = esc_html( get_post_meta( $item->ID, 'ccf_submission_ip', true ) );

		return $data;
	}

	/**
	 * Get a collection of items
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$params = $request->get_params();

		$page = 1;
		if ( ! empty( $params['page'] ) ) {
			$page = $params['page'];
		}

		$query = new WP_Query( array(
			'post_type'   => 'ccf_form',
			'page'        => (int) $page,
			'post_status' => 'publish',
		) );

		$data = array();

		foreach ( $query->posts as $item ) {
			$data[] = $this->prepare_item_for_response( $item, $request );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get one item from the collection
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$params = $request->get_params();

		$item = get_post( $params['id'] );
		$item->ID = (int) $params['id'];

		$data = $this->prepare_item_for_response( $item, $request );

		if ( is_array( $data ) ) {
			return new WP_REST_Response( $data, 200 );
		} else {
			return new WP_Error( 'cant-find', __( 'Form not found', 'custom-contact-forms' ) );
		}
	}

	/**
	 * Create one item from the collection
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {

		$item = $this->prepare_item_for_database( $request );

		$item_id = $this->_create_item( $item );

		$item = get_post( $item_id );
		$item->ID = (int) $item_id;

		$data = $this->prepare_item_for_response( $item, $request );

		if ( is_array( $data ) ) {
			return new WP_REST_Response( $data, 200 );
		}

		return new WP_Error( 'cant-create', __( 'Could not create form', 'custom-contact-forms'), array( 'status' => 500 ) );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$params = $request->get_params();

		if ( ! empty( $params['id'] ) ) {
			$item = $this->prepare_item_for_database( $request );

			$item['id'] = $params['id'];

			$item_id = $this->_create_item( $item );

			$item = get_post( $item_id );
			$item->ID = (int) $item_id;

			$data = $this->prepare_item_for_response( $item, $request );

			if ( is_array( $data ) ) {
				return new WP_REST_Response( $data, 200 );
			}
		}

		return new WP_Error( 'cant-update', __( 'Could not update form', 'custom-contact-forms'), array( 'status' => 500 ) );
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		$params = $request->get_params();

		$force = false;
		if ( ! empty( $params['force'] ) ) {
			$force = (bool) $params['force'];
		}

		if ( $force ) {
			$deleted = wp_delete_post( $params['id'], true );
		} else {
			$deleted = wp_trash_post( $params['id'] );
		}

		if ( $deleted ) {
			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 'cant-delete', __( 'Could not delete form', 'custom-contact-forms'), array( 'status' => 500 ) );
	}

	/**
	 * Handle field deletion. We need to delete choices attached to the field too. Not an API route.
	 *
	 * @param int $form_id
	 * @since 7.0
	 */
	public function delete_fields( $form_id ) {
		$attached_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );
		if ( ! empty( $attached_fields ) ) {
			foreach ( $attached_fields as $field_id ) {
				$this->delete_choices( $field_id );
				wp_delete_post( $field_id, true );
			}
		}
	}
	/**
	 * Delete all submissionws associated with a post.
	 *
	 * @param int $form_id
	 * @since 7.0
	 */
	public function delete_submissions( $form_id ) {
		$submissions = get_children( array( 'post_parent' => $form_id, 'post_type' => 'ccf_submission', 'numberposts' => apply_filters( 'ccf_max_submissions', 5000, get_post( $form_id ) ) ) );
		if ( ! empty( $submissions ) ) {
			foreach ( $submissions as $submission ) {
				wp_delete_post( $submission->ID, true );
			}
		}
	}

	/**
	 * Delete field choices. Not an API route.
	 *
	 * @param int $field_id
	 * @since 7.0
	 */
	public function delete_choices( $field_id ) {
		$attached_choices = get_post_meta( $field_id, 'ccf_attached_choices', true );
		if ( ! empty( $attached_choices ) ) {
			foreach ( $attached_choices as $choice_id ) {
				wp_delete_post( $choice_id, true );
			}
		}
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @since  7.0
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param  WP_REST_Request $request Request object
	 * @since  7.0
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		$body = $request->get_body();

		if ( ! empty( $body ) ) {
			$body = json_decode( $body, true );

			$raw_title = ( ! empty( $body['title'] ) && ! empty( $body['title']['raw'] ) ) ? $body['title']['raw'] : '';
			$body['title'] = $raw_title;

			return $body;
		}

		return false;
	}

	/**
	 * Get fields given a form ID. Not an API route.
	 *
	 * @param int $form_id
	 * @since  7.0
	 * @return array
	 */
	public function _get_fields( $form_id ) {
		$fields = array();

		$attached_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );

		if ( ! empty( $attached_fields ) ) {
			foreach ( $attached_fields as $field_id ) {
				$field = array( 'id' => $field_id );

				foreach ( $this->field_attribute_keys as $key => $functions ) {
					$value = get_post_meta( $field_id, 'ccf_field_' . $key );

					if ( isset( $value[0] ) ) {
						$field[$key] = call_user_func( $functions['escape'], $value[0] );
					}
				}

				$choices = get_post_meta( $field_id, 'ccf_attached_choices' );

				if ( ! empty( $choices ) ) {
					$field['choices'] = array();

					if ( ! empty( $choices[0] ) ) {
						foreach ( $choices[0] as $choice_id ) {
							$choice = array( 'id' => $choice_id );

							foreach ( $this->choice_attribute_keys as $key => $functions ) {
								$value = get_post_meta( $choice_id, 'ccf_choice_' . $key );

								if ( isset( $value[0] ) ) {
									$choice[$key] = call_user_func( $functions['escape'], $value[0] );
								}
							}

							$field['choices'][] = $choice;
						}
					}
				}

				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param  int|object $item
	 * @param  WP_REST_Request
	 * @since  7.0
	 * @return array
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'           => $item->ID,
			'date'         => $this->prepare_date_response( $item->post_date_gmt, $item->post_date ),
			'date_gmt'     => $this->prepare_date_response( $item->post_date_gmt ),
			'guid'         => array(
				'raw'      => $item->guid,
			),
			'modified'     => $this->prepare_date_response( $item->post_modified_gmt, $item->post_modified ),
			'modified_gmt' => $this->prepare_date_response( $item->post_modified_gmt ),
			'slug'         => $item->post_name,
			'status'       => $item->post_status,
			'type'         => $item->post_type,
			'link'         => get_permalink( $item->ID ),
			'title'        => array(
				'raw'      => $item->post_title,
				'rendered' => get_the_title( $item->ID ),
			),
			'author'       => (int) $item->post_author,
		);

		$data['fields'] = $this->_get_fields( $data['id'] );

		$data['buttonText'] = esc_attr( get_post_meta( $data['id'], 'ccf_form_buttonText', true ) );
		$data['description'] = esc_html( get_post_meta( $data['id'], 'ccf_form_description', true ) );
		$data['completionActionType'] = esc_attr( get_post_meta( $data['id'], 'ccf_form_completion_action_type', true ) );
		$data['completionRedirectUrl'] = esc_url_raw( get_post_meta( $data['id'], 'ccf_form_completion_redirect_url', true ) );
		$data['completionMessage'] = esc_html( get_post_meta( $data['id'], 'ccf_form_completion_message', true ) );
		$data['sendEmailNotifications'] = (bool) get_post_meta( $data['id'], 'ccf_form_send_email_notifications', true );
		$data['pause'] = (bool) get_post_meta( $data['id'], 'ccf_form_pause', true );
		$data['pauseMessage'] = esc_html( get_post_meta( $data['id'], 'ccf_form_pause_message', true ) );
		$data['emailNotificationAddresses'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_addresses', true ) );
		
		$data['emailNotificationFromType'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_type', true ) );
		$data['emailNotificationFromAddress'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_address', true ) );
		$data['emailNotificationFromField'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_field', true ) );

		$data['emailNotificationSubjectType'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_subject_type', true ) );
		$data['emailNotificationSubject'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_subject', true ) );
		$data['emailNotificationSubjectField'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_subject_field', true ) );

		$data['emailNotificationFromNameType'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_name_type', true ) );
		$data['emailNotificationFromName'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_name', true ) );
		$data['emailNotificationFromNameField'] = esc_html( get_post_meta( $data['id'], 'ccf_form_email_notification_from_name_field', true ) );

		$submissions = get_children( array( 'post_parent' => $data['id'], 'numberposts' => array( 'ccf_max_submissions', 5000, $data ) ) );
		$data['submissions'] = esc_html( count( $submissions ) );

		return $data;
	}

	/**
	 * Format date for response
	 * 
	 * @param  string $date_gmt
	 * @param  string $date
	 * @since  7.0
	 * @return string
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}

		return mysql_to_rfc3339( $date_gmt );
	}

	/**
	 * Get the query params for collections
	 *
	 * @since  7.0
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'                   => array(
				'description'        => 'Current page of the collection.',
				'type'               => 'integer',
				'default'            => 1,
				'sanitize_callback'  => 'absint',
			),
			'per_page'               => array(
				'description'        => 'Maximum number of items to be returned in result set.',
				'type'               => 'integer',
				'default'            => 10,
				'sanitize_callback'  => 'absint',
			),
		);
	}
}
