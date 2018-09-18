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
   echo("Running Travis tests...\n");
   //   define('GLPI_ROOT', dirname(dirname(__DIR__)));
   define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));

} else {
   echo("Running local tests...\n");
   define('GLPI_ROOT', '/home/glpi/glpi-dev-plugins');
}
echo("Glpi root dir: " . GLPI_ROOT . "\n");

define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo "config_db.php missing. Did GLPI successfully initialized ?\n";
   exit(1);
}

// Fake Glpi log dir to a local dir
define('GLPI_LOG_DIR', __DIR__ . '/logs');
@mkdir(GLPI_LOG_DIR);
if (!defined('STDERR')) {
   define('STDERR', fopen(GLPI_LOG_DIR . 'stderr.log', 'w'));
}
echo("Glpi log dir: " . GLPI_LOG_DIR . "\n");

// Giving --debug argument to atoum will be detected by GLPI too
// the error handler in Toolbox may output to stdout a message and break process communication
// in atoum
$key = array_search('--debug', $_SERVER['argv']);
if ($key) {
   unset($_SERVER['argv'][$key]);
}

include (GLPI_ROOT . "/inc/includes.php");
echo("Included: " . GLPI_ROOT . "/inc/includes.php" . "\n");

//// If GLPI debug mode is disabled, atoum cannot produce backtraces
//\Toolbox::setDebugMode(Session::DEBUG_MODE);
//
//// Installing the plugin
//$plugin = new \Plugin();
//$plugin->getFromDBbyDir('alignak');
//// Check from prerequisites as Plugin::install() does not!
//if (!plugin_alignak_check_prerequisites()) {
//   echo "\nPrerequisites are not met!";
//   die(1);
//}
//if (! $plugin->isInstalled('alignak')) {
//   echo("Installing the plugin...\n");
//   call_user_func([$plugin, 'install'], $plugin->getID());
//   echo("Installed\n");
//} else {
//   echo("Plugin is installed\n");
//}
//if (! $plugin->isActivated('alignak')) {
//   echo("Activating the plugin...\n");
//   call_user_func([$plugin, 'activate'], $plugin->getID());
//   echo("Activated\n");
//} else {
//   echo("Plugin is activated\n");
//}
//
////include_once __DIR__ . '/AlignakDbTestCase.php';
