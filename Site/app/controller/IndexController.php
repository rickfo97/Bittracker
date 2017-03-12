<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 11:53
 */
class IndexController
{
    public function index(){
        return View::render('index.twig');
    }
}