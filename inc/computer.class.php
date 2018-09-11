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

// Class of the defined type

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginAlignakComputer extends CommonDBTM
{

   static function install(Migration $migration) {
       global $DB;

       $table = self::getTable();

      if (!$DB->tableExists($table)) {
          $migration->displayMessage(sprintf(__("Installing %s"), $table));

          $query = "CREATE TABLE `$table` (
                  `id` int(11) NOT NULL auto_increment,
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `itemtype` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
                  `items_id` int(11) NOT NULL,
                  `name` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
                  `comment` text collate utf8_unicode_ci,
                PRIMARY KEY  (`id`),
                KEY `computer` (`itemtype`, `items_id`)
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


    /**
     * Check if an item is monitored
     *
     * Monitored if item type and item id are found in the table. Returns true or false.
     * If the item exists, the caller object is initialized with the DB content.
     *
     * @param $item CommonGLPI
     *
     * @return true/false
     **/
   function exists(CommonGLPI $item) {
       $pmHost = new PluginAlignakComputer();
       PluginAlignakToolbox::logIfDebug("Check if monitored: " . $item->getType() . " / "  . $item->getName());
       return $pmHost->getFromDBByCrit(['itemtype' => 'computer', 'items_id' => $item->getID()]);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
       $array_ret = [];
      if ($item->getID() > -1) {
         if (Session::haveRight('config', READ)) {
            array_push($array_ret, self::createTabEntry(__('Monitoring configuration', 'monitoring')));
         }

            // If the item is monitored, add some more tabs...
            $pmHost = new PluginAlignakComputer();
         if ($pmHost->exists($item)) {
             array_push($array_ret, self::createTabEntry(__('Monitoring live state', 'monitoring')));
             array_push($array_ret, self::createTabEntry(__('Monitoring history', 'monitoring')));
         }
      }
         return $array_ret;
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

       $pmHost = new PluginAlignakComputer();
       $pmHost->exists($item);
      switch ($tabnum) {
         case 1:
            $pmHost->showInfo();
            break;
         case 2:
            $pmHost->showLiveState();
            break;
         case 3:
            $pmHost->showInfo();
            break;
      }
         return true;
   }


   function showInfo() {

       echo '<table class="tab_glpi" width="100%">';
       echo '<tr>';
       echo '<th>'.__('More information', 'alignak').'</th>';
       echo '</tr>';
       echo '<tr class="tab_bg_1">';
       echo '<td>';
       echo __('Type:', 'alignak');
       echo '</td>';
       echo '<td>';
       echo $this->getTypeName();
       echo '</tr>';
       echo '</table>';
   }


    /**
     * Display the live state of a computer
     *
     * @param $items_id integer ID of the entity
     * @param $options array
     *
     * @return bool true if form is ok
     **/
   function showLiveState() {
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


    /**
     * Display form for computer configuration
     *
     * @param $items_id integer ID
     * @param $options array
     *
     * @return bool true if form is ok
     **/
   function showForm($items_id, $options = []) {
       global $DB,$CFG_GLPI;

       PluginAlignakToolbox::logIfDebug("Show form for: " . $items_id);
       /*
       if ($items_id!='') {
        $this->getFromDB($items_id);
       } else {
        $this->getEmpty();
       }
       */

       // $this->showTabs($options);
       $this->showFormHeader($options);

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Tag', 'monitoring')." :</td>";
       echo "<td>";
       echo $this->fields["tag"];
       echo "</td>";
       echo "<td>".__('Username (Shinken webservice)', 'monitoring')."&nbsp;:</td>";
       echo "<td>";
       echo "<input type='text' name='username' value='".$this->fields["username"]."' size='30'/>";
       echo "</td>";
       echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Shinken IP address', 'monitoring')." :</td>";
       echo "<td>";
       echo "<input type='text' name='ip' value='".$this->fields["ip"]."' size='30'/>";
       echo "</td>";
       echo "<td>".__('Password (Shinken webservice)', 'monitoring')."&nbsp;:</td>";
       echo "<td>";
       echo "<input type='text' name='password' value='".$this->fields["password"]."' size='30'/>";
       echo "</td>";
       echo "</tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td>".__('Lock shinken IP', 'monitoring')." :</td>";
       echo "<td>";
       Dropdown::showYesNo('iplock', $this->fields["iplock"]);
       echo "</td>";
       echo "<td colspan='2'>";
       echo "</td>";
       echo "</tr>";

       $this->showFormButtons($options);

       return true;
   }


   static function add_default_where($in) {

       list($itemtype, $condition) = $in;
      if ($itemtype == 'Computer') {
          $table = getTableForItemType($itemtype);
          $condition .= " (".$table.".groups_id NOT IN (".implode(',', $_SESSION["glpigroups"])."))";
      }
         return [$itemtype, $condition];
   }
}

