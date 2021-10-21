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
