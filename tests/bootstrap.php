<?php
// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;

//define plugin paths
define("PLUGINALIGNAK_DOC_DIR", __DIR__ . "/generated_test_data");

if (getenv(TRAVIS) !== false) {
   echo("Running Travis tests...\n");
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
} else {
   echo("Running local tests...\n");
   define('GLPI_ROOT', '/var/www/html/glpi');
}
echo("Glpi root dir: " . GLPI_ROOT . "\n");
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
include GLPI_ROOT . "/inc/includes.php";
include_once GLPI_ROOT . '/tests/DbTestCase.php';

//install plugin
$plugin = new \Plugin();
$plugin->getFromDBbyDir('alignak');
//check from prerequisites as Plugin::install() does not!
if (!plugin_alignak_check_prerequisites()) {
   echo "\nPrerequisites are not met!";
   die(1);
}
if (!$plugin->isInstalled('alignak')) {
   call_user_func([$plugin, 'install'], $plugin->getID());
}
if (!$plugin->isActivated('alignak')) {
   call_user_func([$plugin, 'activate'], $plugin->getID());
}

include_once __DIR__ . '/AlignakDbTestCase.php';
