<?php

/*
   ------------------------------------------------------------------------
   Glpi-Alignak
   Copyright (c) 2018 by the Alignak Team (http://alignak.net/)
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Glpi-Alignak project.

   Glpi-Alignak is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Glpi-Alignak is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Glpi-Alignak . If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Alignak
   @author    Frederic Mohier
   @copyright Copyright (c) 2018 Alignak team
   @license   AGPLv3 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://alignak.net/
   @since     2018

   ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file: Plugin setup and configuration
// ----------------------------------------------------------------------

/*
 * Plugin global configuration variables
 */
define ("PLUGIN_ALIGNAK_OFFICIAL_RELEASE", "0");
define ('PLUGIN_ALIGNAK_VERSION', '1.0-dev');
define ('PLUGIN_ALIGNAK_PHP_MIN_VERSION', '5.6');
define ('PLUGIN_ALIGNAK_GLPI_MIN_VERSION', '9.2');
define ('PLUGIN_ALIGNAK_NAME', 'Alignak monitoring plugin');
define ('PLUGIN_ALIGNAK_LOG', 'plugin-alignak');

if (!defined("PLUGIN_ALIGNAK_DIR")) {
   define("PLUGIN_ALIGNAK_DIR", GLPI_ROOT . "/plugins/alignak");
}
if (!defined("PLUGIN_ALIGNAK_DOC_DIR")) {
   define("PLUGIN_ALIGNAK_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/alignak");
}
if (!file_exists(PLUGIN_ALIGNAK_DOC_DIR)) {
   mkdir(PLUGIN_ALIGNAK_DOC_DIR);
}

//if (!defined("PLUGIN_ALIGNAK_CLASS_PATH")) {
//   define("PLUGIN_ALIGNAK_CLASS_PATH", PLUGIN_ALIGNAK_DIR . "/inc");
//}
//if (!file_exists(PLUGIN_ALIGNAK_CLASS_PATH)) {
//   mkdir(PLUGIN_ALIGNAK_CLASS_PATH);
//}
//
//if (!defined("PLUGIN_ALIGNAK_FRONT_PATH")) {
//   define("PLUGIN_ALIGNAK_FRONT_PATH", PLUGIN_ALIGNAK_DIR."/front");
//}
//if (!file_exists(PLUGIN_ALIGNAK_FRONT_PATH)) {
//   mkdir(PLUGIN_ALIGNAK_FRONT_PATH);
//}
//
if (!defined("PLUGIN_ALIGNAK_TEMPLATES_PATH")) {
   define("PLUGIN_ALIGNAK_TEMPLATES_PATH", PLUGIN_ALIGNAK_DOC_DIR ."/templates");
}
if (!file_exists(PLUGIN_ALIGNAK_TEMPLATES_PATH)) {
   mkdir(PLUGIN_ALIGNAK_TEMPLATES_PATH);
}

// Plugin global configuration
$PA_CONFIG = [];

/*
 For the Twig templating:

   The following options are available:
   debug boolean
      When set to true, the generated templates have a __toString() method that you can use to display
      the generated nodes (default to false).
   charset string (defaults to utf-8)
      The charset used by the templates.
   base_template_class string (defaults to Twig_Template)
      The base template class to use for generated templates.
   cache string or false
      An absolute path where to store the compiled templates, or false to disable caching (which is the default).
   auto_reload boolean
      When developing with Twig, it's useful to recompile the template whenever the source code changes.
      If you don't provide a value for the auto_reload option, it will be determined automatically based on the
      debug value.
   strict_variables boolean
      If set to false, Twig will silently ignore invalid variables (variables and or attributes/methods that
      do not exist) and replace them with a null value. When set to true, Twig throws an exception instead
      (default to false).
   autoescape string
      Sets the default auto-escaping strategy (name, html, js, css, url, html_attr, or a PHP callback that takes
      the template "filename" and returns the escaping strategy to use -- the callback cannot be a function name
      to avoid collision with built-in escaping strategies); set it to false to disable auto-escaping. The name
      escaping strategy determines the escaping strategy to use for a template based on the template filename
      extension (this strategy does not incur any overhead at runtime as auto-escaping is done at compilation time.)
   optimizations integer
      A flag that indicates which optimizations to apply (default to -1 -- all optimizations are enabled;
      set it to 0 to disable).
 */
define("PLUGIN_ALIGNAK_TPL_AUTO_RELOAD", true);
define("PLUGIN_ALIGNAK_TPL_CACHE", PLUGIN_ALIGNAK_DOC_DIR . '/templates_cache');
define("PLUGIN_ALIGNAK_TPL_RAISE_ERRORS", true);

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_alignak() {
   global $PLUGIN_HOOKS, $PA_CONFIG;

   spl_autoload_register('plugin_alignak_autoload');

   //   // manage autoload of plugin custom classes
   //   include_once(PLUGIN_ALIGNAK_DIR . "/vendor/autoload.php");
   //   include_once(PLUGIN_ALIGNAK_DIR . "/inc/autoload.php");
   //   $pluginfields_autoloader = new PluginAlignakAutoloader([PLUGIN_ALIGNAK_CLASS_PATH]);
   //   $pluginfields_autoloader->register();

   // CSRF compliance : All actions must be done via POST and forms closed by Html::closeForm();
   $PLUGIN_HOOKS['csrf_compliant']['alignak'] = true;

   $plugin = new Plugin();
   if ($plugin->isInstalled('alignak')
      && $plugin->isActivated('alignak')
      && Session::getLoginUserID() ) {

      // Alignak backend client library
      include GLPI_ROOT.'/plugins/alignak/lib/alignak-backend-php-client/src/Client.php';

      // Params : plugin name - string type - ID - Array of attributes
      // No specific information passed so not needed
      // Plugin::registerClass('PluginAlignakAlignak', ['classname' => 'PluginAlignakAlignak']);

      // Plugin Alignak - profile management
      Plugin::registerClass('PluginAlignakProfile',
         ['addtabon' => ['Profile']]);

      // Plugin Alignak - entities relations
      Plugin::registerClass('PluginAlignakEntity',
         ['addtabon' => ['Entity']]);

      // Plugin Alignak - monitoring management
      Plugin::registerClass('PluginAlignakAlignak',
         ['addtabon' => ['Central', 'Profile']]);
      Plugin::registerClass('PluginAlignakMonitoringTemplate',
         ['addtabon' => ['Entity', 'Computer']]);
      Plugin::registerClass('PluginAlignakComputer',
         ['addtabon' => ['Computer']]);

      // Plugin configuration class
      Plugin::registerClass('PluginAlignakConfig',
         ['addtabon' => 'Config']);

      // User
      Plugin::registerClass('PluginAlignakUser',
         ['addtabon' => ['User']]);

      // Plugin Alignak - Dashboard class
      Plugin::registerClass('PluginAlignakDashboard',
         ['addtabon' => ['Entity']]);

      // Plugin Alignak - Counters related classes
      Plugin::registerClass('PluginAlignakCounter');
      //      Plugin::registerClass('PluginAlignakCounter',
      //         ['addtabon' => ['Computer']]);
      Plugin::registerClass('PluginAlignakCountersTemplate');
      Plugin::registerClass('PluginAlignakComputerCountersTemplate',
         ['addtabon' => ['Computer']]);

      // Plugin Mail notification class
      Plugin::registerClass('PluginAlignakMailNotification',
         ['addtabon' => 'User']);

      // Load the plugin configuration
      PluginAlignakConfig::loadConfiguration();

      // Add tags for the plugin
      // todo: what for?
      if (version_compare(GLPI_VERSION, 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION', 'ge')) {
         if (class_exists('PluginAlignakAlignak')) {
            Link::registerTag(PluginAlignakAlignak::$tags);
         }
      }

      //      $PLUGIN_HOOKS["menu_toadd"]['alignak'] =
      //         ['admin'  => 'PluginAlignakAlignak'];

      // Display a menu entry ?
      if (Session::haveRight('config', UPDATE)) {
         // Configuration page
         $PLUGIN_HOOKS['config_page']['alignak'] = 'config.php';

         // Add an entry to the Administration menu
         if (Session::haveRight('plugin_alignak_menu', READ)) {
            $PLUGIN_HOOKS['menu_toadd']['alignak'] = ['admin' => 'PluginAlignakMenu', 'config' => 'PluginAlignakMenu'];
         }

         // No menu when on simplified interface
         $PLUGIN_HOOKS["helpdesk_menu_entry"]['alignak'] = false;
      }

      // Init session
      //$PLUGIN_HOOKS['init_session']['alignak'] = 'plugin_init_session_alignak';
      // When the user changes its profile
      // $PLUGIN_HOOKS['change_profile']['alignak'] = 'plugin_change_profile_alignak';
      // When the user changes its entity
      // $PLUGIN_HOOKS['change_entity']['alignak'] = 'plugin_change_entity_alignak';

      // Item action events // See define.php for defined ITEM_TYPE
      $PLUGIN_HOOKS['pre_item_update']['alignak'] = ['Computer' => 'plugin_pre_item_update_alignak'];
      $PLUGIN_HOOKS['item_update']['alignak'] = ['Computer' => 'plugin_item_update_alignak'];
      $PLUGIN_HOOKS['item_empty']['alignak'] = ['Computer' => 'plugin_item_empty_alignak'];

      // Restrict right
      $PLUGIN_HOOKS['item_can']['alignak'] = ['Computer' => ['PluginAlignakComputer', 'item_can']];
      $PLUGIN_HOOKS['add_default_where']['alignak'] = ['Computer' => ['PluginAlignakComputer', 'add_default_where']];

      // Alignak using a method in class (eg. Computer)
      $PLUGIN_HOOKS['pre_item_add']['alignak'] = ['Computer' => ['PluginAlignakAlignak', 'pre_item_add_computer']];
      $PLUGIN_HOOKS['post_prepareadd']['alignak'] = ['Computer' => ['PluginAlignakAlignak', 'post_prepareadd_computer']];
      $PLUGIN_HOOKS['item_add']['alignak'] = ['Computer' => ['PluginAlignakAlignak', 'item_add_computer']];

      $PLUGIN_HOOKS['pre_item_delete']['alignak'] = ['Computer' => 'plugin_pre_item_delete_alignak'];
      $PLUGIN_HOOKS['item_delete']['alignak'] = ['Computer' => 'plugin_item_delete_alignak'];

      $PLUGIN_HOOKS['pre_item_purge']['alignak'] = ['Computer' => 'plugin_pre_item_purge_alignak'];
      $PLUGIN_HOOKS['item_purge']['alignak'] = ['Computer' => 'plugin_item_purge_alignak'];

      $PLUGIN_HOOKS['pre_item_restore']['alignak'] = ['Computer' => 'plugin_pre_item_restore_alignak'];
      $PLUGIN_HOOKS['item_restore']['alignak'] = ['Computer' => 'plugin_item_restore_alignak'];

      /* Add event to GLPI core itemtype, event will be raised by the plugin.
      // See plugin_alignak_uninstall for cleanup of notification
      $PLUGIN_HOOKS['item_get_events']['alignak'] = ['NotificationTargetTicket' => 'plugin_alignak_get_events'];
      */

      /* Add datas to GLPI core itemtype for notifications template.
      $PLUGIN_HOOKS['item_get_datas']['alignak'] = [
         'NotificationTargetTicket' => 'plugin_alignak_get_datas'];
      */

      // $PLUGIN_HOOKS['item_transfer']['alignak'] = 'plugin_item_transfer_alignak';

      /*
      // function to populate planning
      // No more used since GLPI 0.84
      // $PLUGIN_HOOKS['planning_populate']['alignak'] = 'plugin_planning_populate_alignak';
      // Use instead : add class to planning types and define populatePlanning in class
      $CFG_GLPI['planning_types'][] = 'PluginAlignakAlignak';

      //function to display planning items
      // No more used since GLPi 0.84
      // $PLUGIN_HOOKS['display_planning']['alignak'] = 'plugin_display_planning_alignak';
      // Use instead : displayPlanningItem of the specific itemtype
      */

      // Massive Action definition
      $PLUGIN_HOOKS['use_massive_action']['alignak'] = 1;

      $PLUGIN_HOOKS['assign_to_ticket']['alignak'] = 1;

      /**
       * Load the relevant javascript/css files only on pages that need them.
       */
      //      $PLUGIN_HOOKS['add_javascript']['alignak'] = 'js/alignak.js';
      $PLUGIN_HOOKS['add_css']['alignak'] = 'alignak.css';
      if (strpos(filter_input(INPUT_SERVER, "SCRIPT_NAME"), "plugins/alignak") != false) {
         $PLUGIN_HOOKS['add_javascript']['alignak'][] = 'js/alignak-copyright.js';
      }
      // Specific Javascript file for the counters edition form
      if (strpos($_SERVER['REQUEST_URI'], "plugins/alignak/front/counterstemplate.form.php") !== false) {
         $PLUGIN_HOOKS['add_javascript']['alignak'][] = 'js/scripts.js.php';
      }

         /*
         // Reports
         $PLUGIN_HOOKS['reports']['alignak'] = [
            'report.php' => 'New Report',
            'report.php?other' => 'New Report 2'];
         */
      /*
      // Stats
      $PLUGIN_HOOKS['stats']['alignak'] = ['stat.php'       => 'New stat',
                                           'stat.php?other' => 'New stats 2',];
      */

      $PLUGIN_HOOKS['post_init']['alignak'] = 'plugin_alignak_postinit';

      $PLUGIN_HOOKS['status']['alignak'] = 'plugin_alignak_status';

      // To display on central home page
      $PLUGIN_HOOKS['display_central']['alignak'] = "plugin_alignak_display_central";
      // To display on login page
      $PLUGIN_HOOKS['display_login']['alignak'] = "plugin_alignak_display_login";
      // To display on infocom
      $PLUGIN_HOOKS['infocom']['alignak'] = "plugin_alignak_infocom_hook";

      /* pre_show and post_show for tabs and items,
      // see PluginAlignakShowtabitem class for implementation explanations
      $PLUGIN_HOOKS['pre_show_tab']['alignak']     = ['PluginAlignakShowtabitem', 'pre_show_tab'];
      $PLUGIN_HOOKS['post_show_tab']['alignak']    = ['PluginAlignakShowtabitem', 'post_show_tab'];
      $PLUGIN_HOOKS['pre_show_item']['alignak']    = ['PluginAlignakShowtabitem', 'pre_show_item'];
      $PLUGIN_HOOKS['post_show_item']['alignak']   = ['PluginAlignakShowtabitem', 'post_show_item'];

      $PLUGIN_HOOKS['pre_item_form']['alignak']    = ['PluginAlignakItemForm', 'preItemForm'];
      $PLUGIN_HOOKS['post_item_form']['alignak']   = ['PluginAlignakItemForm', 'postItemForm'];
      */

      // Plugin configuration parameters
      if (! isset($PA_CONFIG['id'])) {
         $paConfig = new PluginAlignakConfig();
         $paConfig->loadConfiguration();
         Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Got the plugin configuration: ". serialize($PA_CONFIG) ."\n");
      }

      /*
      Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Alignak backend url: ". $PA_CONFIG['alignak_backend_url'] ."\n");
      $abc = new Alignak_Backend_Client($PA_CONFIG['alignak_backend_url']);
      $token = PluginAlignakUser::myToken($abc);
      if ($token != '') {
         Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "Token: ". $token ."\n");
      } else {
         Toolbox::logInFile(PLUGIN_ALIGNAK_LOG, "No token!\n");
      }*/

   }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_alignak() {
   // Use requirements (Glpi > 9.2)
   return [
      'name'           => 'Alignak monitoring plugin',
      'version'        => PLUGIN_ALIGNAK_VERSION,
      'author'         => 'Frédéric Mohier & <a href="http://alignak.net" target="_blank">Alignak Team</a >',
      'license'        => '<a href="../plugins/alignak/LICENSE" target="_blank">AGPLv3</a>',
      'homepage'       => 'https://github.com/mohierf/alignak',
      'requirements'   => [
         'php' => [
            'min' => 'PLUGIN_ALIGNAK_PHP_MIN_VERSION'
         ],
         'glpi' => [
            'min' => 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION',
            'max' => '9.4',
            'dev' => (PLUGIN_ALIGNAK_OFFICIAL_RELEASE == 0)
         ],
         /* Required Glpi parameters
         'params' => [

         ],
         */
         /* Required installed and enabled plugins
         'plugins' => [

         ]
         */
      ]
   ];
}


/**
 * Check pre-requisites before install
 * OPTIONAL, but recommended
 * For Glpi < 9.2, else requirements are managed by core Glpi
 *
 * @return boolean
 */
function plugin_alignak_check_prerequisites() {

   $version = rtrim(GLPI_VERSION, '-dev');
   if (version_compare($version, 'PLUGIN_ALIGNAK_GLPI_MIN_VERSION', 'lt')) {
      echo __('This plugin requires GLPI ' . PLUGIN_ALIGNAK_GLPI_MIN_VERSION, 'alignak');

      return false;
   }

   return true;
}


/**
 * Check all stored containers files (classes & front) are present, or create they if needed
 *
 * @return void
 */
function plugin_alignak_checkFiles($force = false) {
   global $DB;

   $plugin = new Plugin();

   //   if ($force) {
   //      // Clean all plugin files
   //      array_map('unlink', glob(PLUGIN_ALIGNAK_DOC_DIR.'/*/*'));
   //   }

   //   if (isset($_SESSION['glpiactiveentities'])
   //      && $plugin->isInstalled('alignak')
   //      && $plugin->isActivated('alignak')
   //      && Session::getLoginUserID()) {
   //
   //      /*
   //       * Clean if necessary...
   //       */
   //   }
}


/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_alignak_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'alignak');
   }
   return false;
}


/**
 * Autoloader
 * @param string $classname
 */
function plugin_alignak_autoload($classname) {
   if (strpos($classname, 'PluginAlignak') === 0) {
      /*
      // Search first for field clases
      $filename = __DIR__ . '/inc/fields/' . strtolower(str_replace('PluginAlignak', '', $classname)) . '.class.php';
      if (is_readable($filename) && is_file($filename)) {
         include_once($filename);
         return true;
      }*/

      // useful only for installer GLPi autoloader already handles inc/ folder
      $filename = __DIR__ . '/inc/' . strtolower(str_replace('PluginAlignak', '', $classname)). '.class.php';
      if (is_readable($filename) && is_file($filename)) {
         include_once($filename);
         return true;
      }
   }
}


/**
 * Show the last SQL error, logs its backtrace and dies
 * @param Migration $migration
 */
function plugin_alignak_upgrade_error(Migration $migration) {
   global $DB;

   $error = $DB->error();
   $migration->log($error . "\n" . Toolbox::backtrace(false, '', ['Toolbox::backtrace()']), false);
   die($error . "<br><br> Please, check the migration log");
}

