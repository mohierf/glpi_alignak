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
// Original Author of file: Francois Mohier
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAlignakCounter extends CommonDBTM {


   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `glpi_plugin_alignak_counters` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `comment` text collate utf8_unicode_ci,
                  `type_counter` ENUM( 'INTEGER', 'FLOAT', 'POURCENTAGE', 'OCTETS') NOT NULL,
                  `cumulatif` BOOLEAN NOT NULL DEFAULT FALSE,
                  `template_id` int(11) NOT NULL,
                PRIMARY KEY  (`id`),
                KEY `name` (`name`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

         $DB->query($query) or die("error creating $table". $DB->error());
      }

      return true;
   }


   static function uninstall() {
      global $DB;

      $DB->query("DROP TABLE IF EXISTS `".self::getTable()."`");

      return true;
   }


   // Should return the localized name of the type
   static function getTypeName($nb = 0) {
      return 'Counter';
   }


   static function canCreate() {
      return PluginAlignakProfile::haveRight("config", 'w');
   }



   static function canView() {
      return PluginAlignakProfile::haveRight("config", 'r');
   }



   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         if (PluginAlignakProfile::haveRight("config", 'r')) {
            $array_ret[0] = self::createTabEntry(__('Counters', 'alignak'));
         }
      }
      return $array_ret;
   }



   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getID() > -1) {
         $pmCounter = new PluginAlignakCounter();
         
         $pmCounter->showForm($item->fields['id']);
      }
      return true;
   }



   /**
   * Display form for template tag
   *
   * @param $template_id integer ID of the template
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   function showForm($template_id, $options = []) {
      global $DB,$CFG_GLPI;

      $counters = $this->find("`template_id`='".$template_id."'", "", 1);
      if (count($counters) == '0') {
         $input = [];
         $input['template_id'] = $template_id;
         $id = $this->add($input);
         $this->getFromDB($id);
      } else {
         $counter = current($counters);
         $this->getFromDB($counter['id']);
      }

/*      echo "<form name='form' method='post' 
         action='".$CFG_GLPI['root_doc']."/plugins/alignak/front/counter.form.php'>"; */
      $this->initForm($template_id, $options);
      $this->showFormHeader($options);
      
      echo "<table class='tab_cadre_fixe'";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo __('Counters', 'alignak');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name', 'alignak')." :</td>";
      echo "<td>";
      echo "<input type='text' name='name' value='".$this->fields["name"]."' size='30'/>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comment', 'alignak')." :</td>";
      echo "<td>";
      echo '<textarea name="comment" cols="124" rows="10">' . $this->fields["comment"] . '</textarea>';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Type', 'alignak')." :</td>";
      echo "<td>";
      Dropdown::showFromArray('type_counter', ['INTEGER'=>'INTEGER', 'FLOAT'=>'FLOAT', 'POURCENTAGE'=>'POURCENTAGE', 'OCTETS'=>'OCTETS'], ['value'=>$this->fields["type_counter"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Cumulatif', 'alignak')." :</td>";
      echo "<td>";
      Dropdown::showYesNo("cumulatif", $this->fields["cumulatif"]);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Template', 'alignak')." :</td>";
      echo "<td>";
      $templates = new PluginAlignakCounterTemplate();
      $templateList = $templates->getCounterTemplateListForDropdown();
      Dropdown::showFromArray('template_id', $templateList, ['value'=>$this->fields["template_id"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' align='center'>";
      echo "<input type='hidden' name='id' value='".$this->fields['id']."'/>";
      echo "<input type='submit' name='save' value=\"".__('Save')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();

      return true;
   }



   function getCounterListByTemplate($template_id = '') {
      global $DB;

      if ($template_id == '') {
         return ['-1' => "-1"];
      } else {
         $output = [];
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `template_id`='".$template_id."'";
         $result = $DB->query($query);
         while ($data=$DB->fetch_array($result)) {
            $output[$data['id']] = $data['id'];
            $output[$data['name']] = $data['name'];
         }
         return $output;
      }
   }

}

