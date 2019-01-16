<?php
require 'vendor/autoload.php';
use Reloop\Manager;
use Reloop\Context;

$job = function(){
    sleep(1);
    Context::log(time());
};

$manager = new Manager([
    'pidDir'=>'/tmp/reloop',
    'logDir'=>'/tmp/relooplog',
    'processNum'=>2,
]);
$manager->run($job);