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
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAlignakEntity extends CommonDBTM {


   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `$table` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `comment` text collate utf8_unicode_ci,
                PRIMARY KEY  (`id`),
                KEY `name` (`name`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

         $DB->query($query) or die("error creating $table". $DB->error());

         /*
         $thelist = "";
         if ($handle = opendir('default')) {
            while (false !== ($file = readdir($handle)))
            {
               if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'xml')
               {
                  $thelist .= '<li><a href="'.$file.'">'.$file.'</a></li>';
               }
            }
            closedir($handle);
         }

         // Could not get the templates, raise an error !
         Session::addMessageAfterRedirect($thelist, true, ERROR);
         */

         /* Populate data
         $query = "INSERT INTO `glpi_plugin_alignak_dropdowns`
                          (`id`, `name`, `comment`)
                   VALUES (1, 'dp 1', 'comment 1'),
                          (2, 'dp2', 'comment 2')";

         $DB->query($query) or die("error populate glpi_plugin_alignak_dropdowns". $DB->error());
         */
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
      return 'Entity';
   }


/*
   static function canCreate() {
      return Session::haveRight('plugin_alignak_entity', CREATE));
   }


   static function canView() {
      return PluginAlignakProfile::haveRight("config", 'r');
   }
*/


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         if (Session::haveRight('config', READ)) {
            $array_ret[0] = self::createTabEntry(__('Monitoring', 'monitoring'));
         }
      }
      return $array_ret;
   }



   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getID() > -1) {
         $pmEntity = new PluginAlignakEntity();
         $pmEntity->showForm($item->fields['id']);
      }
      return true;
   }



   /**
   * Display form for entity tag
   *
   * @param $items_id integer ID of the entity
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   function showForm($items_id, $options = []) {
      global $DB,$CFG_GLPI;

      $a_entities = $this->find("`entities_id`='".$items_id."'", "", 1);
      if (count($a_entities) == '0') {
         $input = [];
         $input['entities_id'] = $items_id;
         $id = $this->add($input);
         $this->getFromDB($id);
      } else {
         $a_entity = current($a_entities);
         $this->getFromDB($a_entity['id']);
      }

      echo "<form name='form' method='post' 
         action='".$CFG_GLPI['root_doc']."/plugins/monitoring/front/entity.form.php'>";

      echo "<table class='tab_cadre_fixe'";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo __('Set tag to link entity with a specific Alignak server', 'monitoring');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Tag', 'monitoring')." :</td>";
      echo "<td>";
      echo "<input type='text' name='tag' value='".$this->fields["tag"]."' size='30'/>";

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' align='center'>";
      echo "<input type='hidden' name='id' value='".$this->fields['id']."'/>";
      echo "<input type='submit' name='update' value=\"".__('Save')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();

      return true;
   }


   function getEntitiesByTag($tag = '') {
      global $DB;

      if ($tag == '') {
         return ['-1' => "-1"];
      } else {
         $output = [];
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `tag`='".$tag."'";
         $result = $DB->query($query);
         while ($data=$DB->fetch_array($result)) {
            $output[$data['entities_id']] = $data['entities_id'];
         }
         return $output;
      }
   }


   static function getTagByEntities($entities_id) {
      global $DB;

      $query = "SELECT * FROM `glpi_plugin_monitoring_entities`
         WHERE `entities_id`='".$entities_id."'
            LIMIT 1";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         return $data['tag'];
      }
   }

}

