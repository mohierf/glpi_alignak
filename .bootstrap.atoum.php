<?php

/*
This file will automatically be included before EACH test if -bf/--bootstrap-file argument is not used.

Use it to initialize the tested code, add autoloader, require mandatory file, or anything that needs to be done before EACH test.

More information on documentation:
[en] http://docs.atoum.org/en/latest/chapter3.html#bootstrap-file
[fr] http://docs.atoum.org/fr/latest/lancement_des_tests.html#fichier-de-bootstrap
*/

/*
AUTOLOADER

// composer
require __DIR__ . '/vendor/autoload.php';
*/
// fix empty CFG_GLPI on bootstrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;

//define plugin paths
define("PLUGINALIGNAK_DOC_DIR", __DIR__ . "/generated_test_data");

if (getenv("TRAVIS") !== false) {
   echo("Running Travis tests...\n");
   define('GLPI_ROOT', dirname(dirname(__DIR__)));
} else {
   echo("Running local tests...\n");
   define('GLPI_ROOT', '/home/glpi/glpi-dev-plugins');
}
echo("Glpi root dir: " . GLPI_ROOT . "\n");
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
include GLPI_ROOT . "/inc/includes.php";
echo("Included: " . GLPI_ROOT . "/inc/includes.php" . "\n");
include_once GLPI_ROOT . '/tests/GLPITestCase.php';
include_once GLPI_ROOT . '/tests/DbTestCase.php';
echo("Included: " . GLPI_ROOT . '/tests/DbTestCase.php' . "\n");

// Installing the plugin
$plugin = new \Plugin();
$plugin->getFromDBbyDir('alignak');
// Check from prerequisites as Plugin::install() does not!
if (!plugin_alignak_check_prerequisites()) {
   echo "\nPrerequisites are not met!";
   die(1);
}
if (! $plugin->isInstalled('alignak')) {
   echo("Installing the plugin...\n");
   call_user_func([$plugin, 'install'], $plugin->getID());
   echo("Installed\n");
}
if (! $plugin->isActivated('alignak')) {
   echo("Activating the plugin...\n");
   call_user_func([$plugin, 'activate'], $plugin->getID());
   echo("Activated\n");
}

include_once __DIR__ . '/tests/AlignakDbTestCase.php';
