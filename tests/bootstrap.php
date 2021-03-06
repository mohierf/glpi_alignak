<?php
// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;

//disable session cookies
ini_set('session.use_cookies', 0);
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

require_once __DIR__ . '/../vendor/autoload.php';

define('TEST_PLUGIN_NAME', 'alignak');

// glpi/inc/oolbox.class.php tests TU_USER to decide if it warns or not about mcrypt extension
define('TU_USER', '_test_user');

// Travis run or local run?
if (getenv("TRAVIS") !== false) {
   putenv('OLDDBNAME=glpiupgradetest');
   putenv('DBNAME=glpitest');
}
define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));
// Do not echo, else it may break the tests execution!
//echo("Glpi root dir: " . GLPI_ROOT . "\n");

define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
if (! file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo GLPI_CONFIG_DIR . "/config_db.php missing. Did GLPI successfully initialized ?\n";
   exit(1);
}

// Fake Glpi log dir to a local dir
define('GLPI_LOG_DIR', __DIR__ . '/logs');
@mkdir(GLPI_LOG_DIR);
if (!defined('STDERR')) {
   define('STDERR', fopen(GLPI_LOG_DIR . 'stderr.log', 'w'));
}

define('ALIGNAK_ROOT', GLPI_ROOT . DIRECTORY_SEPARATOR . '/plugins/alignak');
set_include_path(
   get_include_path() . PATH_SEPARATOR .
   GLPI_ROOT . PATH_SEPARATOR .
   GLPI_ROOT . "/plugins/alignak/tests/"
);

// Giving --debug argument to atoum will be detected by GLPI too
// the error handler in Toolbox may output to stdout a message and break process communication in atoum
$key = array_search('--debug', $_SERVER['argv']);
if ($key) {
   unset($_SERVER['argv'][$key]);
}

include (GLPI_ROOT . "/inc/includes.php");
