<?php
require_once 'notifier.php';
// require_once 'create.php';

class Api
{
    function __construct()
    {
        add_action('rest_api_init', array($this, 'rest_api_inits'));
    }
    function rest_api_inits()
    {
        $notifiers = new noitifier();
        $notifiers->resgister_routes();
    }
}

new Api();
