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
// Purpose of file: Alignak monitoring plugin configuration file
// ----------------------------------------------------------------------

class PluginAlignakConfig extends CommonDBTM {

   /**
    * @param Migration $migration
    * @return bool
    */
   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();
      if (!$DB->tableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `$table` (
                  `id` int(11) NOT NULL auto_increment,
                  `extra_debug` int(1) NOT NULL DEFAULT 0,
                  `log_retention` int(2) NOT NULL DEFAULT 30,
                PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

         $DB->query($query) or die("error creating $table". $DB->error());
      }

      // Initialize the table
      $pmConfig = new PluginAlignakConfig();
      $pmConfig->fields['extra_debug'] = '0';
      $pmConfig->fields['log_retention'] = 30;
      $pmConfig->addToDB();

      return true;
   }


   static function uninstall() {
      global $DB;

      $DB->query("DROP TABLE IF EXISTS `".self::getTable()."`");

      return true;
   }


   /**
    * Get name of this type
    *
    *@return string, text name of this type by language of the user connected
    *
    **/
   static function getTypeName($nb = 0) {
      return __('Configuration', 'alignak');
   }


   /**
    * Load the plugin configuration in a global variable $PA_CONFIG
    *
    * Test if the table exists before loading cache
    * The only case where table does not exists is when you click on
    * uninstall the plugin and it's already uninstalled
    *
    * @global object $DB
    * @global array $PA_CONFIG
    */
   static function loadConfiguration() {
      global $DB, $PA_CONFIG;

      $table = self::getTable();
      if ($DB->tableExists($table)) {
         $PA_CONFIG = [];

         $pmConfig = new PluginAlignakConfig();
         if ($pmConfig->getFromDBByCrit(['1'])) {
            $PA_CONFIG = $pmConfig->fields;
         } else {
            PluginAlignakToolbox::log("Not found any configuration parameters!");
         }
      }
   }


   /**
    * Get a configuration value
    *
    * @global array $PA_CONFIG
    * @param string $name name in configuration
    * @return null|string|integer
    */
   function getValue($name) {
      global $PA_CONFIG;

      if (isset($PA_CONFIG[$name])) {
         return $PA_CONFIG[$name];
      }

      $config = current($this->find("`type`='".$name."'"));
      if (isset($config['value'])) {
         return $config['value'];
      }
      return null;
   }


   /**
    * Update a configuration value
    *
    * @param string $name name of configuration
    * @param string $value
    * @return boolean
    */
   function updateValue($name, $value) {
      global $PF_CONFIG;

      // retrieve current config
      $config = current($this->find("`type`='".$name."'"));

      // set in db
      if (isset($config['id'])) {
         $result = $this->update(['id'=> $config['id'], 'value'=>$value]);
      } else {
         $result = $this->add(['type' => $name, 'value' => $value]);
      }

      // set cache
      if ($result) {
         $PF_CONFIG[$name] = $value;
      }

      return $result;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'Config') {
            return __('Alignak monitoring plugin');
         }
      }
      return '';
   }

   static function configUpdate($input) {
      $input['configuration'] = 1 - $input['configuration'];
      return $input;
   }

   function showConfigurationForm() {
      global $PLUGIN_ALIGNAK_LOG;

      if (!Session::haveRight("config", UPDATE)) {
         return false;
      }

      $my_config = Config::getConfigurationValues('plugin:Alignak');

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Alignak monitoring plugin setup') . "</th></tr>";
      echo "<td >" . __('My boolean choice :') . "</td>";
      echo "<td colspan='3'>";
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Alignak'>";
      Dropdown::showYesNo("configuration", $my_config['configuration']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

      echo "</table></div>";
      echo "Log: $PLUGIN_ALIGNAK_LOG";
      Html::closeForm();
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Config') {
         $config = new self();
         $config->showConfigurationForm();
      }
   }

}
