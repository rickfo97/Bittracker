<?php
require_once('vendor/autoload.php');
ignore_user_abort(true);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

use Rickfo97\Bittracker\Api\Api;

$core = new \Rickfo97\Bittracker\Core\Tracker();

switch ($_GET['action']) {
    case 'announce':
        echo $core->announce();
        break;
    case 'scrape':
        echo $core->scrape();
        break;
    case 'api':
        header('Content-Type: text/json');
        echo json_encode(Api::process($_GET['method']));
        break;
    default:
        echo 'no action found';
        break;
}
