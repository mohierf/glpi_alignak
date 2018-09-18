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

class PluginAlignakComputerCountersTemplate extends CommonDBTM {

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
         $query = "CREATE TABLE `$table` (
                  `id` int(11) NOT NULL auto_increment,
                  `itemtype` varchar(100) DEFAULT NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `plugin_alignak_counters_template_id` int(11) NOT NULL,
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
      if ($item->getType() == 'Computer' and $item->getID() > -1) {
         if (Session::haveRight('plugin_alignak_counters', READ)) {
            $array_ret[0] = self::createTabEntry(__('Monitoring counters', 'alignak'));
         }
      }
      return $array_ret;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Computer' and $item->getID() > -1) {
         $paComputerCountersTemplate = new PluginAlignakComputerCountersTemplate();
         $paComputerCountersTemplate->showForm($item);
      }
      return true;
   }

   /**
   * Display form for the computer counters tempalte and the counters
   *
   * @param $item Computer
   *
   *@return bool true if form is ok
   *
   **/
   function showForm($item) {
      global $DB,$CFG_GLPI;

      // The computer's entity
      $paEntity = new Entity();
      $paEntity->getFromDB($item->fields['entities_id']);

      $paCountersTemplate = new PluginAlignakCountersTemplate();
      $computer_relation = false;
      $entity_relation = false;
      if (! $this->getFromDBByCrit(["itemtype" => 'Computer', "items_id" => $item->fields['id']])) {
         // No direct relation between this computer and a counters template
         $this->getEmpty();

         // Searching for an entity / counters template relation in the computer entity
         PluginAlignakToolbox::log("Computer entity: ". $item->fields['entities_id']);
         if (! $paCountersTemplate->getFromDBByCrit(["entities_id" => $item->fields['entities_id']])) {
            // Searching for an entity / counters template relation in the computer entity ancestors
            $ancestors = getAncestorsOf('glpi_entities', $item->fields['entities_id']);
            PluginAlignakToolbox::log("Entity ancestors: ". serialize($ancestors));
            $entity = new Entity();
            foreach ($ancestors as $index=>$id) {
               $entity->getFromDB($id);
               if ($paCountersTemplate->getFromDBByCrit(["entities_id" => $entity->getID()])) {
                  // Found a relation in the computer entity ancestors
                  PluginAlignakToolbox::log("Computer entity ancestor relation: ". serialize($paCountersTemplate->fields));
                  $entity_relation = true;
                  $paEntity->getFromDB($entity->getID());
                  break;
               }
            }
         } else {
            // Found a relation in the computer entity
            PluginAlignakToolbox::log("Computer entity relation: ". serialize($paCountersTemplate->fields));
            $entity_relation = true;
         }
      } else {
         PluginAlignakToolbox::log("Existing relation: ". serialize($this->fields));
         $paCountersTemplate->getFromDB($this->fields['template_id']);
         $computer_relation = true;
         PluginAlignakToolbox::log("Existing relation: ". serialize($paCountersTemplate->fields));
      }

      // Here, we know if a relation exists:
      // $computer_relation is set if a direct relation exists between the computer and a counters template
      // $entity_relation is set if an indirect relation exists through an entity counters template
      // $paCountersTemplate if the found counters template object

      $canedit = $this->canEdit($this->getID());
      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }
      echo '<table class="tab_cadre_fixe"';

      echo '<tr class="tab_bg_1">';
      echo '<th colspan="2">';
      echo __('Computer counters template', 'alignak');
      echo '</th>';
      echo '</tr>';

      if ($entity_relation) {
         echo '<tr class="tab_bg_1">';
         echo '<td colspan="2">';
         echo __("This computer is inheriting a counters template from its entity.", 'alignak');
         echo '</td>';
         echo '</tr>';

         echo '<tr class="tab_bg_1">';
         echo '<td>';
         echo __('Entity counters template', 'alignak');
         echo '</td>';
         echo '<td>';
         echo '<span><strong></strong>' . $paCountersTemplate->getName() . '</strong></span>';
         // Link to the entity
         echo '<em class="green">&nbsp;'. __('Inherited from the entity: ') . $paEntity->getLink() .'</em>';
         echo '</td>';
         echo '</tr>';
      } else {
         echo '<tr class="tab_bg_1">';
         echo '<td colspan="2">';
         echo __("You should define a counters template in the computer's entity.", 'alignak');
         echo '</td>';
         echo '</tr>';
      }

      if (! $computer_relation) {
         echo '<tr class="tab_bg_1">';
         echo '<td colspan="2">';
         echo __("You can set a direct relation between this computer and a counters template.", 'alignak');
         echo '</td>';
         echo '</tr>';
      }
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Computer counters template', 'alignak');
      echo '</td>';
      echo '<td>';
      if ($computer_relation) {
         if ($canedit) {
            Dropdown::show('PluginAlignakCountersTemplate',
               ['name' => 'plugin_alignak_counters_template_id',
                  'value' => $paCountersTemplate->getID(),
                  'comments' => false]);
         } else {
            echo '<span>' . $this->fields["plugin_alignak_counters_template_id"] . '</span>';
         }
         // Link to the object
         if ($this->fields["plugin_alignak_counters_template_id"] != 0) {
            $paObject = new PluginAlignakCountersTemplate();
            $paObject->getFromDB($this->fields["plugin_alignak_counters_template_id"]);
            echo $paObject->getLink();
         }
      } else {
         Dropdown::show('PluginAlignakCountersTemplate',
            ['name' => 'plugin_alignak_counters_template_id',
               'value' => -1,
               'comments' => false]);
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td colspan="2">';
      echo '<hr>';
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<th colspan="2">';
      echo __('Computer counters', 'alignak');
      echo '</th>';
      echo '</tr>';

      if ($canedit) {
         echo '<tr>';
         echo '<td class="tab_bg_2 center" colspan="4">';
         echo '<input type="hidden" name="id" value="'. $this->fields['id'] .'">';
         echo '<input type="hidden" name="entities_id" value="'. $this->fields['entities_id'] .'">';
         echo '<input type="submit" name="update" value="'. _sx('button', 'Save'). '" class="submit">';
         echo '</td>';
         echo '</tr>';
         echo '</table>';
         Html::closeForm();
      } else {
         echo '</table>';
      }

      echo '</div>';

      return true;
   }
}
