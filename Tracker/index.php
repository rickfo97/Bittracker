<?php
/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-04
 * Time: 15:40
 */
ignore_user_abort(true);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include 'config.php';
include 'Logger/log.php';
include 'database.php';
include 'core.php';

$core = new Core();

switch ($_GET['action']){
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