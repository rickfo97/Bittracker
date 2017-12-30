<?php
require_once('vendor/autoload.php');
ignore_user_abort(true);

$settings = [
    'numwant_max' => 300,
    'announce_interval_min' => 3600,

    'log_level' => 1
];
$core = new \Rickfo\Bittracker\Core\Tracker($settings);

switch ($_GET['action']) {
    case 'announce':
        echo $core->announce();
        break;
    case 'scrape':
        echo $core->scrape();
        break;
    default:
        echo 'no action found';
        break;
}
