<?php

ignore_user_abort(true);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

use Rickfo97\Bittracker\Core\Core;

$core = new Core();

switch ($_GET['action']) {
    case 'announce':
        $core->announce();
        break;
    case 'scrape':
        $core->scrape();
        break;
    default:
        echo 'no action found';
        break;
}
