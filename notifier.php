
<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once 'Functions.php';
require_once(ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
require_once(ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php');

class noitifier extends WP_REST_Controller
{

    function __construct()
    {
        $this->namespace = 'in_stock_notifiers/v1';
        $this->rest_base = 'notifier';
    }
    public function resgister_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permissions_callback' => [$this, 'get_item_permission_check'],
                'args' => $this->get_collection_params()
            ],
            'schema' => [$this, 'get_item_schema'],
        ]);
    }
    /**
     * check notifier permission.
     * return boolean
     */
    public function get_item_permission_check($request)
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        return false;
    }

    public function get_items($request)
    {
        $args = [];
        $params = $this->get_collection_params();

        foreach ($params as $key => $value) {
            if (isset($request[$key])) {
                $args[$key] = $request[$key];
            }
        }
        $args['number'] = $args['per_page'];
        $args['offset'] = $args['number'] * ($args['page'] - 1);

        // unset others
        unset($args['per_page']);
        unset($args['page']);


        $data     = [];
        $contacts = wd_ac_get_addresses($args);

        foreach ($contacts as $contact) {
            $response = $this->prepare_item_for_response($contact, $request);
            return $response;
            $data[]   = $this->prepare_response_for_collection($response);
        }
    }
    public function prepare_item_for_response($item, $request)
    {
        $data   = [];
        $fields = $this->get_fields_for_response($request);

        return $fields;
    }
    public function get_collection_params()
    {
        $params = parent::get_collection_params();
        unset($params['search']);
        return $params;
    }
    public function get_item_schema()
    {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'contact',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Unique identifier for the object.'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name' => [
                    'description' => __('Name of the contact.'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'required'    => true,
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'address' => [
                    'description' => __('Address of the contact.'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                ],
                'phone' => [
                    'description' => __('Phone number of the contact.'),
                    'type'        => 'string',
                    'required'    => true,
                    'context'     => ['view', 'edit'],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'date' => [
                    'description' => __("The date the object was published, in the site's timezone."),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
            ]
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema($this->schema);
    }
}
