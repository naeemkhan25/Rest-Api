
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

            $data[]   = $this->prepare_response_for_collection($response);
        }
        $total     = wd_ac_address_count();
        $max_pages = ceil($total / (int) $args['number']);

        $response = rest_ensure_response($data);

        $response->header('X-WP-Total', (int) $total);
        $response->header('X-WP-TotalPages', (int) $max_pages);


        return $response;
    }
    public function prepare_item_for_response($item, $request)
    {
        $data   = [];
        $fields = $this->get_fields_for_response($request);
        if (in_array('id', $fields, true)) {
            $data['id'] = (int) $item->id;
        }

        if (in_array('name', $fields, true)) {
            $data['name'] = $item->name;
        }

        if (in_array('address', $fields, true)) {
            $data['address'] = $item->address;
        }

        if (in_array('phone', $fields, true)) {
            $data['phone'] = $item->phone;
        }

        if (in_array('date', $fields, true)) {
            $data['date'] = mysql_to_rfc3339($item->created_at);
        }

        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->filter_response_by_context($data, $context);

        $response = rest_ensure_response($data);
        //links use korno
        $response->add_links($this->prepare_links($item));


        return $response;
    }

    /**
     * Prepares links for the request.
     *
     * @param \WP_Post $post Post object.
     *
     * @return array Links for the given post.
     */
    protected function prepare_links($item)
    {
        $base = sprintf('%s/%s', $this->namespace, $this->rest_base);

        $links = [
            'self' => [
                'href' => rest_url(trailingslashit($base) . $item->id),
            ],
            'collection' => [
                'href' => rest_url($base),
            ],
        ];

        return $links;
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
