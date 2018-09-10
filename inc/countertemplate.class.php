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


error_reporting(E_ALL);
 ini_set('display_errors', 1);
 

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class PluginAlignakCounterTemplate
class PluginAlignakCounterTemplate extends CommonDBTM {

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `glpi_plugin_alignak_countertemplates` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(25) collate utf8_unicode_ci NOT NULL,
                  `entities_id` int(11),
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

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('CounterTemplate', 'CounterTemplate', $nb);
   }

   static function canCreate() {
      return PluginAlignakProfile::haveRight("config", 'w');
   }



   static function canView() {
      return PluginAlignakProfile::haveRight("config", 'r');
   }


/*
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];
      if ($item->getID() > -1) {
         if (PluginAlignakProfile::haveRight("config", 'r')) {
            $array_ret[0] = self::createTabEntry(__('Monitoring', 'monitoring'));
         }
      }
      return $array_ret;
   }*/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      $ong = array();
      $ong[1] = 'titre de mon premier onglet';
      $ong[2] = 'titre de mon second onglet';
      return $ong;
   }

/*
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      echo "DISPLAY SOMETING".$item->getID();
      if ($item->getID() > -1) {
         $pmCounter = new PluginAlignakCounterTemplate();
         $pmHostconfig = new PluginAlignakHostconfig();

         $pmHostconfig->showForm($item->getID(), "CounterTemplate");
         $pmEntity->showForm($item->fields['id']);
      }
      return true;
   }
   */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

   switch ($tabnum) {
      case 1 : // mon premier onglet
         $item->nomDeLaFonctionQuiAfficheraLeContenuDeMonPremierOnglet();
         break;

      case 2 : // mon second onglet
         $item->nomDeLaFonctionQuiAfficheraLeContenuDeMonSecondOnglet();
         break;
   }
   return true;
}    

   public function defineTabs($options=[]) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginAlignakCounter', $ong, $options);
      return $ong;
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
   function showForm($template_id, $options = []) {
      global $DB,$CFG_GLPI;

      
      $counters = $this->find("`id`='".$template_id."'", "", 1);
      $counter = current($counters);
      $this->getFromDB($counter['id']);

 /*     echo "<form name='form' method='post' 
         action='".$CFG_GLPI['root_doc']."/plugins/alignak/front/counter_template.form.php'>";
*/
      $this->initForm($template_id, $options);
      $this->showFormHeader($options);

      echo "<table class='tab_cadre_fixe'";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo __('Set the counter template', 'alignak');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Template Name', 'alignak')." :</td>";
      echo "<td>";
      echo "<input type='text' name='name' value='".$this->fields["name"]."' size='30'/>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Entity', 'alignak')." :</td>";
      echo "<td>";
      echo "<input type='text' name='entities_id' value='".$this->fields["entities_id"]."' size='30'/>";
      
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
   
   function getCounterTemplateListForDropdown($entities_id = '') {
      global $DB;

      $output = [];
      $query = "SELECT * FROM `".$this->getTable()."` ";
      
      if ($template_id != '') {
        $query .= " WHERE `template_id`='".$template_id."'";
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $output[$data['id']] = $data['name'];
      }
      return $output;
   }
   
   
   function getCounterTemplateList($entities_id = '') {
      global $DB;

      $output = [];
      $query = "SELECT * FROM `".$this->getTable()."` ";
      
      if ($template_id != '') {
        $query .= " WHERE `template_id`='".$template_id."'";
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $output[$data['id']] = $data['id'];
         $output[$data['name']] = $data['name'];
         $output[$data['entities_id']] = $data['entities_id'];
      }
      return $output;
   }
}
?>
