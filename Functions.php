<?php
function wd_ac_get_addresses($args = [])
{

    global $wpdb;
    $defaults = [
        'number' => 20,
        'offset' => 0,
        'order' => 'id',
        'orderby' => 'ASC'
    ];
    $args = wp_parse_args($args, $defaults);
    $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}addresses ORDER BY {$args['order']} {$args['orderby']} LIMIT %d, %d", $args['offset'], $args['number']);
    $items = $wpdb->get_results($sql);
    return $items;
}
function wd_ac_address_count()
{
    global $wpdb;
    return (int) $wpdb->get_var("SELECT count(id) FROM {$wpdb->prefix}addresses");
}


function wd_ac_get_address($id)
{
    global $wpdb;

    $address = wp_cache_get('book-' . $id, 'address');

    if (false === $address) {
        $address = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}addresses WHERE id = %d", $id)
        );

        wp_cache_set('book-' . $id, $address, 'address');
    }

    return $address;
}
function wd_ac_delete_address($id)
{
    global $wpdb;
    $sql = $wpdb->delete($wpdb->prefix . 'addresses', ['id' => $id], ['%d']);
    return $sql;
}
function weDevs_insert_address($arrgs = [])
{
    global $wpdb;
    if (empty($arrgs['name'])) {
        return new \WP_Error("no-name", __("You must  provide a name", "weDevs"));
    }
    $default = array(
        'name' => '',
        'address' => '',
        'phone' => '',
        'created_at' => current_time('mysql'),
        'created_by' => get_current_user_id()
    );
    $data = wp_parse_args($arrgs, $default);
    if (isset($data['id'])) {
        $id = $data['id'];
        unset($data['id']);
        $updated = $wpdb->update(
            'wp_addresses',
            $data,
            ['id' => $id],
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%d'

            ),
            ['%d']
        );

        return $updated;
    } else {
        $inserted = $wpdb->insert(
            'wp_addresses',
            $data,
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%d'

            )
        );

        if (!$inserted) {
            return new \WP_Error('failed-to-inserted', __("Failed to insert data", 'weDevs'));
        }
        return $wpdb->insert_id;
    }
}
