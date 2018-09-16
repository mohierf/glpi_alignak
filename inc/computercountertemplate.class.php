<?php
/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Francois Mohier
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class PluginAlignakComputerCounterTemplate
class PluginAlignakComputerCounterTemplate extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_counters';

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
//         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `glpi_plugin_alignak_computercountertemplates` (
                  `id` int(11) NOT NULL auto_increment,
                  `computer_id` int(11) NOT NULL ,
                  `template_id` int(11) NOT NULL,
                PRIMARY KEY  (`id`)
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

   static function getTypeName($nb = 0) {
      return _n('Computer counters template', 'Computer counters templates', $nb, 'alignak');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         $array_ret[] = self::createTabEntry(__('Counters', 'alignak'));
      }
      // PHP Catchable Fatal Error: Object of class Computer could not be converted to string in /var/www/html/glpi/plugins/alignak/inc/computercountertemplate.class.php at line 95
      return $array_ret;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

//      echo "computercountertemplate displayTabContentForItem".$tabnum.":".$item;

      if ($item->getID() > -1) {
//         $pmCounter = new PluginAlignakComputerCounterTemplate();
//         $pmHostconfig = new PluginAlignakHostconfig();
//
//         $pmHostconfig->showForm($item->getID(), "ComputerCounterTemplate");
//         $pmCounter->showForm($item->fields['id']);
         $pmCounter = new PluginAlignakCounter();
         $pmCounter->listCounters($item->fields['id']);
      }
      return true;
   }

   /**
   * Display form for counter template tag
   *
   * @param $items_id integer ID of the counter
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
      echo __('Set tag to link entity with a specific Shinken server', 'monitoring');
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
}
