<?php

$dir = dirname(__FILE__) . DS;

// Let the FuelPHP autoloader handle loading for Doctrine classes
Autoloader::add_namespace("Doctrine", $dir . 'Doctrine' . DS, true);

// Set up wrapper namespace
Autoloader::add_namespace('Doctrine_Fuel', $dir . 'classes' . DS);
Autoloader::alias_to_namespace('Doctrine_Fuel\Doctrine_Fuel');