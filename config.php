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
   echo(nl2br("Loaded: " . $template . "\n"));
   $result = $template->render(['template' => 'test-host', 'name' => 'localhost', 'address' => '127.0.0.1']);
   echo nl2br("\nTemplate result is: \n", true);
   echo nl2br("\n-----\n", true);
   echo nl2br($result, true);
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

echo '<div style="margin-left: 350px; background: #eee; border: outset 2px white; padding: 0.5%;">';

if (Session::haveRight("config", 'r')) {
   echo "
   <div style='margin-top: 5px;'>
   <b>". __("Applications", "kiosks") ."</b>
   <br/>
   <small><i>". __("Manage kiosks applications and embedded user's services", "kiosks") ."</i></small>
   <ul style='margin-left: 5px;'>
      <li><a href='front/application.php'>".  __("Applications", "kiosks") ."</a></li>
   </ul>
   </div>
   ";
}


if (Session::haveRight("config", 'r')) {
   echo "
   <div style='margin-top: 5px;'>
   <b>". __("Mail notifications", "kiosks") ."</b>
   <br/>
   <small><i>". __("Configure mail notifications for counters", "kiosks") ."</i></small>
   <br/>
   <ul style='margin-left: 5px;'>
      <li><a href='front/mailnotification.php'>".  __("Mail notifications", "kiosks") ."</a></li>
   </ul>
   </div>
   ";
}


if (Session::haveRight("messages", 'r')) {
   echo "
   <div style='margin-top: 5px;'>
   <b>". __("User messages", "kiosks") ."</b>
   <br/>
   <ul style='margin-left: 5px;'>
      <li><a href='front/message.php'>".  __("User messages", "kiosks") ."</a></li>
      <li><a href='front/message.table.php?table=". 'glpi_plugin_kiosks_messages' ."'>". __("User messages table", "kiosks") ."</a></li>
   </ul>
   </div>
   ";
}


if (Session::haveRight("counters", 'r')) {
   $components = new PluginMonitoringComponent();
   $component_list = $components->find();
   echo "
   <div style='margin-top: 5px;'>
   <b>". __("Components counters", "kiosks") ."</b>
   <br/>
   <small><i>". __("Display known counters for a specific component", "kiosks") ."</i></small>

   <table><tr>";
   $i=1;
   foreach($component_list as $component) {
      $component_name = str_replace(" ", "", $component['description']);
      // $component_name = str_replace("'", "", $component_name);
      $hdc_table = "glpi_plugin_kiosks_hdc_".$component_name;


      if (TableExists($hdc_table)) {
         echo "<td><a style='display: block; padding: 3px; margin: 1px; text-align: center; background: #eee; border: outset 2px white; ' href='front/componentcountertables.php?id_component=".$component['id']."'>". __("Counters for", "kiosks") . " '{$component['name']}' (<i>{$component['description']}</i>)</a></td>";

         if ($i > 4) {
            $i = 1;
            echo '</tr><tr>';
         } else {
            $i++;
         }
      }
   }

   echo "
   </tr></table>
   </div>
   <br/>
   ";
}


if (Session::haveRight("counters", 'r')) {
   $sql = "SHOW TABLES LIKE 'glpi_plugin_kiosks_monitoring_%'";
   $result = $DB->query($sql);
   if ($DB->numrows($result)) {
      echo "
      <div style='margin-top: 5px;'>
      <b>". __("Specific client monitoring page", "kiosks") ."</b>
      <br/>
      <small><i>". __("Specific counters views for a client", "kiosks") ."</i></small>

      <table><tr>";

      $i=1;
      while ($row = $DB->fetch_array($result)) {
         $array_client = explode("_", $row[0]);
         $client = array_pop($array_client);
         echo "<td><a style='display: block; padding: 3px; margin: 1px; text-align: center; background: #eee; border: outset 2px white; ' href='front/monitoring".$client.".php'>". __("Monitoring for", "kiosks") . " '".$client."'</a></td>";

         if ($i > 4) {
            $i = 1;
            echo '</tr><tr>';
         } else {
            $i++;
         }
      }

      echo "
      </tr></table>
      </div>
      <br/>
      ";
   }
}

/*
if (Session::haveRight("counters", 'r')) {
   echo "
   <div style='margin-top: 5px;'>
   <b>".  __("Counters", "kiosks") ."</b>
   <br/>
   <ul style='margin-left: 5px;'>
      <li><a href='front/hostcounterdaily.php'>". __("Daily counters display", "kiosks") ."</a></li>
      <li><a href='front/hostcounterall.php'>". __("All time counters display", "kiosks") ."</a></li>

      <li><a href='front/hostcounterrecord.php'>".  __("Record counters display", "kiosks") ."</a></li>

      <li><a href='front/hostcountercond.php'>".  __("Conditional counters display", "kiosks") ."</a></li>
   </ul>
   </div>
   ";
}
*/

if (Session::haveRight("alerts", 'r')) {
   echo "
   <div style='margin-top: 5px;'>
   <b>".  __("Alerts", "kiosks") ."</b>
   <br/>
   <small><i>". __("Display kiosks alerts", "kiosks") ."</i></small>

   <ul style='margin-left: 5px;'>
      <li><a href='front/alert.php'>". __("Alerts", "kiosks") ."</a></li>
   </ul>
   </div>
   <br/>
   ";
}

if (Session::haveRight("config", 'r')) {
   echo"
   <div style='margin-top: 5px;'>
   <b>". __("DashKiosk configuration", "kiosks") ."</b>
   <br/>
   <small><i>". __("Manage Dashkiosk configurations and mail notifications", "kiosks") ."</i></small>

   <ul style='margin-left: 5px;'>
      <li><a href='front/dashboard.php'>". __("Dashkiosk configurations", "kiosks") . "</a></li>
      <li><a href='front/mailnotification.php'>". __("Mail counters notifications", "kiosks") . "</a></li>
      <li>
   </ul>
   <br/>
   ";

   echo"
   <div style='margin-top: 5px;'>
   <b>". __("DashKiosk table", "kiosks") ."</b>
   <br/>
   <small><i>". __("Display known counters tables for the current entity", "kiosks") ."</i></small>
   <table><tr>";
   $i=1;
   foreach (glob(GLPI_ROOT . "/plugins/kiosks/conf/table.*.php") as $file) {
      if($file == '.' || $file == '..') continue;
      if (preg_match("/table.(\w+).php/i", $file, $matches, PREG_OFFSET_CAPTURE)) {
         echo "<td><a style='display: block; padding: 3px; margin: 1px; text-align: center; background: #eee; border: outset 2px white;' href='front/dashkiosk.table.php?table=". $matches[1][0] ."'>". __("DashKiosk table", "kiosks") . ": " . $matches[1][0] ."</a></td>";

         if ($i > 4) {
            $i = 1;
            echo '</tr><tr>';
         } else {
            $i++;
         }
      }
   }
   echo "
   </tr></table>
   </div>
   <br/>
   ";

   echo"
   <div style='margin-top: 5px;'>
   <b>". __("Counters configuration", "kiosks") ."</b>
   <br/>
   <small><i>". __("Manage known counters configurations", "kiosks") ."</i></small>

   <ul style='margin-left: 5px;'>
      <li><a href='front/counter.php'>". __("Counters configuration", "kiosks") . "</a></li>
      <li><a href='front/condcounter.php'>". __("Conditional counters configuration", "kiosks") ."</a></li>
   </ul>
   </div>
   <br/>
   ";
}

if (Session::haveRight("production", 'r')) {
   echo"
   <div style='margin-top: 5px;'>
   <b>". __("Kiosks configuration", "kiosks") ."</b>
   <br/>
   <small><i>". __("Manage kiosks production configurations", "kiosks") ."</i></small>

   <ul style='margin-left: 5px;'>
      <li><a href='front/kioskconfiguration.php'>". __("Kiosks configuration", "kiosks") . "</a></li>
   </ul>
   </div>
   <br/>
   ";
};
echo "</div>";

Html::footer();
