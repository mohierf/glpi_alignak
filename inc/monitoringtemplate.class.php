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
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------


class PluginAlignakMonitoringTemplate extends CommonDBTM {
   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_monitoring';

   static function getTypeName($nb = 0) {
      return _n('Monitoring template', 'Monitoring templates', $nb, 'alignak');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $array_ret = [];
      if ($item->getID() > -1) {
         if (Session::haveRight('config', READ)) {
            switch ($item->getType()) {
               case 'Entity' :
                  $array_ret[] = self::createTabEntry(__('Monitoring template', 'alignak'));
                  break;
               case 'Computer' :
                  $array_ret[] = self::createTabEntry(__('Monitoring template', 'alignak'));
                  break;
            }
         }
      }
      return $array_ret;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'Entity' :
            // Call the show form for the provided entity
            $paTemplate = new PluginAlignakMonitoringTemplate();
            $paTemplate->showForm(-1, $item->getID(), ['canedit'=>self::canUpdate(), 'colspan'=>4 ]);
            break;

         case 'Computer' :
            // Call the show form for the current entity
            $paTemplate = new PluginAlignakMonitoringTemplate();
            $paTemplate->showForm(-1, $item->getID(), ['canedit'=>self::canUpdate(), 'colspan'=>4 ]);
            break;
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
   function showForm($ID = -1, $entities_id = -1, $options = [], $copy = []) {
      global $DB,$CFG_GLPI;

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
      echo '<input type="hidden" name="id" value="' . $this->fields['id'] . '"/>';
      echo '<input type="hidden" name="entities_id" value="' . $entities_id . '"/>';

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

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
      echo '<td>'.__('Computer template name', "alignak").'</td>';
      echo '<td colspan="7">';
      if (! empty($this->fields["name"])) {
         echo '<input type="text" name="name" value="'. $this->fields["name"] .'" size="20"/>';
      } else {
         echo '<input type="text" name="name" value="'. __("Template ", "alignak") . $user->fields["name"] .'" size="20"/>';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Comment', 'alignak')." :</td>";
      echo '<td>';
      echo '<textarea name="comment" cols="124" rows="3">' . $this->fields["comment"] . '</textarea>';
      echo '</td>';
      echo '</tr>';

      echo '<tr><td colspan="8">';
      echo '<hr/>';
      echo '</td></tr>';

      try {
         $filename = 'host.cfg';

         $loader = new Twig_Loader_Filesystem(PLUGIN_ALIGNAK_TEMPLATES_PATH);
         $twig = new Twig_Environment($loader, [
            'debug' => false,
            'auto_reload' => PLUGIN_ALIGNAK_TPL_AUTO_RELOAD,
            'cache' => PLUGIN_ALIGNAK_TPL_CACHE,
            'strict_variables' => PLUGIN_ALIGNAK_TPL_RAISE_ERRORS
         ]);

         echo(nl2br("Loading template: " . $filename . "\n"));
         PluginAlignakToolbox::log("Loading template: " . $filename);
         $template = $twig->load($filename);
         $result = $template->render(['template' => 'test-host', 'name' => 'localhost', 'address' => '127.0.0.1']);
         echo nl2br("\nTemplate result is: \n", true);
         echo nl2br("\n-----\n", true);
         echo nl2br($result, true);
         echo nl2br("\n-----\n", true);
      } catch (Twig_Error_Loader $e) {
         // Could not get the templates, raise an error !
         Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates are not available:", 'alignak'), true, ERROR);
         Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
      } catch (Twig_Error_Runtime $e) {
         // Could not parse the templates, raise an error !
         Session::addMessageAfterRedirect(__("Alignak monitoring plugin templates runtime exception:", 'alignak'), true, ERROR);
         Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
         echo nl2br(__("Alignak monitoring plugin templates runtime exception: \n", 'alignak') . $e->getMessage());
      }

      $this->showFormButtons($options);

      Html::closeForm();

      return true;
   }

   /**
    * Define search options for forms
    *
    * @return Array Array of fields to show in search engine and options for each fields
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
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'searchtype'         => 'contains',
         'massiveaction'      => false
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
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'entities_id',
         'name'               => __('Entity'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];
      return $tab;
   }
    */

}
