<?php

$autoloader = require(dirname(__DIR__) . '/vendor/autoload.php');
$autoloader->addPsr4('tests\\', [__DIR__]);
