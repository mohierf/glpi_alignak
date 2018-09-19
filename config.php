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
   @co-author David Durieux
   @copyright Copyright (c) 2018 Alignak team
   @license   AGPLv3 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://alignak.net/
   @link      http://alignak.net/
   @since     2018

   ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file: plugin configuration management
// ----------------------------------------------------------------------

// Non menu entry case
//header("Location:../../central.php");

// Entry menu case
define('GLPI_ROOT', '../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", UPDATE);

// To be available when plugin in not activated
Plugin::load('alignak');

global $PLUGIN_ALIGNAK_NAME;
Html::header(__($PLUGIN_ALIGNAK_NAME . " - configuration page", 'alignak'), $_SERVER['PHP_SELF'], "config", "plugins");
echo __("This is the plugin configuration page", 'alignak');
echo ("<br>");



/*
 * Test Twig for templating system similar to jinja templates!
 * See: https://twig.symfony.com/doc/2.x/templates.html for the templating patterns
 * See: https://twig.symfony.com/doc/2.x/api.html for the templating implementation
 */
require_once 'vendor/autoload.php';
/*
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

$filename = 'host.cfg';
try {
   $loader = new Twig_Loader_Filesystem('templates');
   $twig = new Twig_Environment($loader, [
      'debug' => false,
      'auto_reload' => PLUGIN_ALIGNAK_TPL_AUTO_RELOAD,
      'cache' => PLUGIN_ALIGNAK_TPL_CACHE,
      'strict_variables' => PLUGIN_ALIGNAK_TPL_RAISE_ERRORS
   ]);

   echo(nl2br("Loading template: " . $filename . "\n"));
   PluginAlignakToolbox::log("Loading template: " . $filename);
   $template = $twig->load($filename);
   $result = $template->render(['template' => 'test-host', 'name' => 'localhost', 'address' => '127.0.0.1']);
   echo nl2br("\nTemplate result is: \n", true);
   echo nl2br("\n-----\n", true);
   echo nl2br($result, true);
   echo nl2br("\n-----\n", true);
} catch (Twig_Error_Loader $e) {
   // Could not get the templates, raise an error !
   Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates are not available:", 'alignak'), true, ERROR);
   Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
} catch (Twig_Error_Runtime $e) {
   // Could not parse the templates, raise an error !
   Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates runtime exception:", 'alignak'), true, ERROR);
   Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
   echo nl2br(__("Alignak monitoring plugin templates runtime exception: \n", 'alignak') . $e->getMessage());
}


/*
 * Include here the main plugin configuration page !
 * ----------------------
 * Indeed, we should include some buttons that are links to the main plugin tables:
 * alignak, counters templates, ...
 *
 */


// Check if the Alignak CRON task is running
// todo: To be amended!
/*
$cronTask = new CronTask();
$cronTask->getFromDBbyName('PluginAlignakTask', 'taskscheduler');
if ($cronTask->fields['lastrun'] == ''
   OR strtotime($cronTask->fields['lastrun']) < strtotime("-3 day")) {
   $message = __('Alignak CRON task is not running, see ', 'alignak');
   $message .= " <a href='http://fusioninventory.org/documentation/fi4g/cron.html'>".__('documentation', 'fusioninventory')."</a>";
   Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
}
*/

Html::footer();
