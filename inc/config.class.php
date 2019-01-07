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
// Purpose of file: Alignak monitoring plugin configuration file
// ----------------------------------------------------------------------

class PluginAlignakConfig extends CommonDBTM
{
   static $rightname = 'plugin_alignak_configuration';

   /**
     * Get name of this type
     *
     * @return string, text name of this type by language of the user connected
     **/
   static function getTypeName($nb = 0) {
      return _n('Configuration', 'Configurations', $nb, 'alignak');
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
    *
    * @return PluginAlignakConfig
     */
   static function loadConfiguration() {
      global $DB, $PA_CONFIG;

      $table = self::getTable();
      if ($DB->tableExists($table)) {
         $PA_CONFIG = [];

         $paConfig = new self();
         if (! $paConfig->getFromDBByCrit(['1'])) {
            PluginAlignakToolbox::log("Not found any configuration parameters!");
            $paConfig = new PluginAlignakConfig();
            $paConfig->initConfiguration();
         }
         $paConfig->getFromDBByCrit(['1']);
         $PA_CONFIG = $paConfig->fields;
      } else {
         // todo: Should be moved elsewhere ... indeed, this may not be useful!
         $newTable = "glpi_plugin_alignak_configs";
         $query = "CREATE TABLE `".$newTable."` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `timezones` varchar(255) NOT NULL DEFAULT '[\"0\"]',
                 `extradebug` tinyint(1) NOT NULL DEFAULT '0',
                 `alignak_webui_url` varchar(255) DEFAULT 'http://127.0.0.1:5001',
                 `alignak_backend_url` varchar(255) DEFAULT 'http://127.0.0.1:5000',
                 `graphite_url` varchar(255) DEFAULT 'http://127.0.0.1:8080',
                 `graphite_prefix` varchar(255) DEFAULT '',
                  PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query);

         $paConfig = new PluginAlignakConfig();
         $paConfig->initConfiguration();
         $paConfig->getFromDBByCrit(['1']);
         $PA_CONFIG = $paConfig->fields;
      }

      return $paConfig;
   }


   /**
    * Initialize the database with the default configuration parameters
    *
    * @global object $DB
    */
   function initConfiguration() {
      global $DB;

      $query = "SELECT * FROM `".$this->getTable()."` LIMIT 1";

      $result = $DB->query($query);
      if ($DB->numrows($result) == '0') {
         $input = array();
         $input['timezones'] = '["0"]';
         $input['extradebug'] = 0;
         $input['alignak_backend_url'] = 'http://127.0.0.1:5000';
         $input['alignak_webui_url'] = 'http://127.0.0.1:5001';
         $input['graphite_url'] = 'http://127.0.0.1:8080';
         $input['graphite_prefix'] = '';
         $this->add($input);
      }
   }

   /**
     * Get a configuration value
     *
     * @global array $PA_CONFIG
     * @param  string $name name in configuration
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
     * @param  string $name  name of configuration
     * @param  string $value
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

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

//      if (($item->getType() == 'Config')
//         && ($item->getID() > 0)
//         && Session::haveRight('plugin_alignak_configuration', READ)) {
//         $config = new self();
//         $config->showForm();
//      }
      $config = new self();
      $config->showForm();
   }

   static function configUpdate($input) {
      $input['configuration'] = 1 - $input['configuration'];
      return $input;
   }

   function showForm($ID = 0, $options = []) {
      global $PLUGIN_ALIGNAK_LOG;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

//      $my_config = Config::getConfigurationValues('plugin:Alignak');

//      $this->showFormHeader();

//      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
//      echo "<div class='center' id='tabsbody'>";
//      echo "<table class='tab_cadre_fixe'>";




      echo "<tr><th colspan='2'>" . __('Alignak monitoring plugin setup') . "</th></tr>";
      echo "<td >" . __('Log extra debug:') . "</td>";
      echo "<td colspan='3'>";
//      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
//      echo "<input type='hidden' name='config_context' value='plugin:Alignak'>";
      Dropdown::showYesNo("configuration", $this->fields['extradebug']);
      echo "</td></tr>";

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Alignak backend URL', 'alignak');
      echo '</td>';
      echo '<td>';
      Html::autocompletionTextField($this, 'alignak_backend_url', array('value' => $this->fields['alignak_backend_url']));
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Alignak Web UI URL', 'alignak');
      echo '</td>';
      echo '<td>';
      Html::autocompletionTextField($this, 'alignak_webui_url', array('value' => $this->fields['alignak_webui_url']));
      echo '</td>';
      echo '</tr>';

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

//      $this->showFormButtons();

      echo "</table>";
      echo "</div>";
      Html::closeForm();
   }
}
