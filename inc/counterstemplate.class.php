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


/// Class PluginAlignakCountersTemplate
class PluginAlignakCountersTemplate extends CommonDBTM {
   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_counters';

   static function getTypeName($nb = 0) {
      return _n('Counters template', 'Counters templates', $nb, 'alignak');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            return [1 => __('Counters template', 'alignak')];
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            // Call the show form for the current entity
            $paTemplate = new PluginAlignakCountersTemplate();
            $paTemplate->showForm(-1, $item->getID(), ['canedit'=>self::canUpdate(), 'colspan'=>4 ]);
            break;
      }
      return true;
   }

   function showForm($ID = -1, $entities_id = -1, $options = [], $copy = []) {

      if ($ID != -1) {
         // We still know which object if to be edited...
         $this->getFromDB($ID);
      } else {
         // Create a new object...

         // If no entity is specified, use the root entity
         if ($entities_id == -1) {
            // Root entity ...
            $entity = new Entity();
            $entity->getFromDBByCrit(['id' => '0']);
            $entities_id = $entity->getID();
         }

         // We still have an item for this entity?
         $a_confs = $this->find("`entities_id`='".$entities_id."'", "", 1);
         if (count($a_confs) > 0) {
            // If we have, use the found item
            $a_conf = current($a_confs);
            $ID = $a_conf['id'];
            $this->getFromDB($ID);
         } else {
            // else, create a new item
            $this->getEmpty();
            //$this->setDefaultContent();
            $this->fields['entities_id'] = $entities_id;
         }
      }

      // Get a user object
      $user = new User();
      $user->getFromDB(Session::getLoginUserID());

      // Get an entity object
      $entity = new Entity();
      $entity->getFromDB($this->fields["entities_id"]);
      $entities_id = $this->fields["entities_id"];
      //      echo '<input type="hidden" name="id" value="' . $this->fields['id'] . '"/>';
      //      echo '<input type="hidden" name="entities_id" value="' . $entities_id . '"/>';

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $canedit = $this->canEdit($this->getID());
      echo "<div class='spaced'>";
      //      if ($canedit) {
      //         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      //      }
      echo '<table class="tab_cadre_fixe"';

      /*
      @fred - For future idea
      echo '<tr>';
      echo '<td>';
      echo __('Is active?', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
      } else {
         echo Dropdown::getYesNo($this->fields['is_active']);
      }
      echo '</td>';
      echo '</tr>';
      */

      echo '<tr>';
      echo '<td>'.__('Entity', "alignak").'</td>';
      echo '<td colspan="5">';
      // Select an entity in the database
      Dropdown::show('Entity',
         ['name'=>'entities_id',
         'value'=>$this->fields['entities_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$user->fields['entities_id'],
         'entity_sons'=>true]);
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>'.__('Name', "alignak").'</td>';
      echo '<td colspan="7">';
      if (! empty($this->fields["name"])) {
         echo '<input type="text" name="name" value="'. $this->fields["name"] .'" size="20"/>';
      } else {
         echo '<input type="text" name="name" value="'. __("Template ", "alignak") . $user->fields["name"] .'" size="20"/>';
      }
      echo '</td>';
      echo '</tr>';

      $rowspan = 1;
      echo '<tr>';
      echo "<td rowspan='$rowspan'><label for='comment'>".__('Comments')."</label></td>";
      echo "<td rowspan='$rowspan' class='middle'>";

      echo "<textarea cols='45' rows='".($rowspan+3)."' id='comment' name='comment' >". $this->fields["comment"];
      echo "</textarea></td></tr>";
      echo '</tr>';

      if ($this->getID()) {
         $this->listCounters($this->getID());
      } else {
         echo '<tr class="tab_bg_1">';
         echo '<th colspan="2">';
         echo '<strong>';
         echo __('When you will save this new template, you will be able to add some counters.', 'alignak');
         echo '</strong>';
         echo '</th>';
         echo '</tr>';
      }

      $this->showFormButtons($options);
      Html::closeForm();
      //      if ($canedit) {
      //         echo '<tr>';
      //         echo '<td class="tab_bg_2 center" colspan="4">';
      //         echo Html::hidden('id', ['value' => $this->fields['id']]);
      //         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
      //         echo '</td>';
      //         echo '</tr>';
      //         echo '</table>';
      //         Html::closeForm();
      //      } else {
      //         echo '</table>';
      //      }

      echo '</div>';

      return true;
   }

   function alreadyExistCountersTemplateForThatEntity($entity_id) {
      $countersTemplate = new self();
      return($countersTemplate->find("entities_id = ".$entity_id));
   }

   /**
    * Define search options for forms
    *
    * @return Array Array of fields to show in search engine and options for each fields
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => true
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'searchtype'         => 'contains',
         'massiveaction'      => false
      ];

      /*
      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'entities_id',
         'name'               => __('Entity'),
         'massiveaction'      => false
      ];
      */

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];
      return $tab;
   }

   /**
   * listCounters for the counters template
   *
   * @param $template_id integer ID of the template
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   function listCounters($template_id, $options = []) {
      // todo: why not using self rather than $template_id ?
      global $DB,$CFG_GLPI;

      $paCounters = new PluginAlignakCounter();
      $counters = $paCounters->find("`plugin_alignak_counters_template_id`='".$template_id."'", "", "");

      $token = Session::getNewCSRFToken();

      // If counters exist for that template...
      if (count($counters)) {
         $tpl = new PluginAlignakCountersTemplate();
         $found = $tpl->getFromDB($template_id);
         if ($found) {
            echo '<table class="tab_cadre_fixe">';
            echo '<tr>';
            echo '<td class="tab_bg_2 center" colspan="4">';
            echo __('Template Name:'.$tpl->fields['name'], 'alignak');
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td>';
            echo __('Name:', 'alignak');
            echo '</td>';
            echo '<td>';
            echo __('Type:', 'alignak');
            echo '</td>';
            echo '<td>';
            echo __('Description:', 'alignak');
            echo '</td>';
            echo '<td>';
            echo __('Cumulated:', 'alignak');
            echo '</td>';
            echo '</tr>';
         }
      }

      $i = 0;
      foreach ($counters as $counter) {
         $i++;
         echo '<tr class="line' . ($i % 2) . '" id="counter_row_' . $counter['id'] . '">';
         echo '<td onclick="editCounter(' . $counter['id'] . ', \'' . $token . '\', ' . $counter['id'] . ', ' . $counter['id'] . ')">';
         echo "<a href='#'>";
         echo '<img src="' . $CFG_GLPI['root_doc'] . '/pics/edit.png" title="" /> ';
         echo $counter['name'];
         echo "<a>";
         echo '</td>';

         echo '<td>';
         echo $counter['type_counter'];
         echo '</td>';

         echo '<td>';
         echo $counter['comment'];
         echo '</td>';
         echo '<td>';
         if ($counter['cumulatif']) {
            echo '<img src="' . $CFG_GLPI['root_doc'] . '/pics/stats_item.png" title="" /> ';
         }
         echo '</td>';

         echo '<td align="center">';

         // avoid quote js error
         $counter['name'] = htmlspecialchars_decode($counter['name'], ENT_QUOTES);

         echo "<span class='form_control pointer'>";
         echo '<img src="' . $CFG_GLPI['root_doc'] . '/pics/delete.png"
                  title="' . __('Delete', 'alignak') . '"
                  onclick="deleteCounter(' . $counter['id'] . ', \'' . $token . '\', ' . $counter['id'] . ')"> ';
         echo "</span>";
         echo '</td>';

         echo '</tr>';
      }
      echo '<tr class="line' . (($i + 1) % 2) . '">';
      echo '<td colspan="6" id="add_counter_td_1" class="add_counter_tds">';
      echo '<a href="javascript:addCounter(' . $template_id . ', \'' . $token . '\', ' . $template_id . ');">
                <img src="'.$CFG_GLPI['root_doc'].'/pics/menu_add.png" alt="+"/>
                '.__('Add a counter', 'alignak').'
            </a>';
      echo '</td>';
      echo '</tr>';

      echo '</table>';
   }
}
