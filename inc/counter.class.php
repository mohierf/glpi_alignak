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
// Original Author of file: Francois Mohier
// Purpose of file:
// ----------------------------------------------------------------------

class PluginAlignakCounter extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_counters';

   static function getTypeName($nb = 0) {
      return _n('Counter', 'Counters', $nb, 'alignak');
   }

   /**
   * Display form for a counter
   *
   * @param $counter_id integer ID of the counter
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   function showForm($counter_id, $options = []) {
      global $DB,$CFG_GLPI;

      $this->initForm($counter_id, $options);
      $this->showFormHeader($options);
      echo "<table class='tab_cadre_fixe'";

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Name', 'alignak').'<span style="color:red;">*</span></td>';

      echo '<td>';
      echo "<input type='text' name='name' value='".$this->fields["name"]."' size='30'/>";
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="124" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Type', 'alignak')." :</td>";
      echo '<td>';
      Dropdown::showFromArray('type_counter', ['INTEGER'=>'INTEGER', 'FLOAT'=>'FLOAT', 'POURCENTAGE'=>'POURCENTAGE', 'OCTETS'=>'OCTETS'], ['value'=>$this->fields["type_counter"]]);
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Cumulatif', 'alignak')." :</td>";
      echo '<td>';
      Dropdown::showYesNo("cumulatif", $this->fields["cumulatif"]);
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Graphite Name', 'alignak').'<span style="color:red;">*</span></td>';
      echo '<td>';
      echo "<input type='text' name='graphite_name' value='".$this->fields['graphite_name']."' size='30'/>";
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Template', 'alignak').' <span style="color:red;">*</span></td>';
      echo '<td>';
      Dropdown::show('PluginAlignakCountersTemplate',
         ['name' => 'plugin_alignak_counters_template_id',
            'value' => $this->fields["plugin_alignak_counters_template_id"],
            'comments' => false]);
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo "<td colspan='2' align='center'>";
      echo '<input type="submit" name="save" value="'. __('Save') .'" class="submit">';
      echo '</td>';
      echo '</tr>';

      echo '<input type="hidden" name="id" value="' .$this->fields['id'] .'"/>';
      echo '<input type="hidden" name="templateid" value="' .$this->fields["plugin_alignak_counters_template_id"] .'"/>';
      echo '</table>';
      Html::closeForm();

      return true;
   }
}

