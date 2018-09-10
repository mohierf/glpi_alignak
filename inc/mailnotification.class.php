<?php

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAlignakMailNotification extends CommonDBTM {

//   static function getTypeName($nb=0) {
//      if ($nb==1) {
//         return __('Mail notification', 'kiosks');
//      }
//      return __('Mail notification', 'kiosks');
//   }
//
//   function getSearchOptions() {
//      $tab = array();
//      $i = 1;
//
//      $tab['common'] = self::getTypeName();
//
//      $i=1;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'name';
//      $tab[$i]['name']            = __('Name');
//      $tab[$i]['datatype']        = 'itemlink';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'user_to_id';
//      $tab[$i]['name']            = __('To: ', 'kiosks');
//      $tab[$i]['datatype']        = 'specific';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'user_cc_1_id';
//      $tab[$i]['name']            = __('Cc: ', 'kiosks');
//      $tab[$i]['datatype']        = 'specific';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'user_cc_2_id';
//      $tab[$i]['name']            = __('Cc: ', 'kiosks');
//      $tab[$i]['datatype']        = 'specific';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'user_cc_3_id';
//      $tab[$i]['name']            = __('Cc: ', 'kiosks');
//      $tab[$i]['datatype']        = 'specific';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'user_bcc_id';
//      $tab[$i]['name']            = __('Bcc: ', 'kiosks');
//      $tab[$i]['datatype']        = 'specific';
//
//      $i++;
//      $tab[$i]['table']           = 'glpi_plugin_monitoring_components';
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'component_1';
//      $tab[$i]['name']            = __('Component', 'monitoring')." 1";
//      $tab[$i]['datatype']        = 'itemlink';
//      $tab[$i]['itemlink_type']   = 'PluginMonitoringComponent';
//
//      $i++;
//      $tab[$i]['table']           = 'glpi_plugin_monitoring_components';
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'component_2';
//      $tab[$i]['name']            = __('Component', 'monitoring')." 2";
//      $tab[$i]['datatype']        = 'itemlink';
//      $tab[$i]['itemlink_type']   = 'PluginMonitoringComponent';
//
//      $i++;
//      $tab[$i]['table']           = 'glpi_plugin_monitoring_components';
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'component_3';
//      $tab[$i]['name']            = __('Component', 'monitoring')." 3";
//      $tab[$i]['datatype']        = 'itemlink';
//      $tab[$i]['itemlink_type']   = 'PluginMonitoringComponent';
//
//      $i++;
//      $tab[$i]['table']           = 'glpi_plugin_monitoring_components';
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'component_4';
//      $tab[$i]['name']            = __('Component', 'monitoring')." 2";
//      $tab[$i]['datatype']        = 'itemlink';
//      $tab[$i]['itemlink_type']   = 'PluginMonitoringComponent';
//
//      $i++;
//      $tab[$i]['table']           = 'glpi_plugin_monitoring_components';
//      $tab[$i]['field']           = 'name';
//      $tab[$i]['linkfield']       = 'component_5';
//      $tab[$i]['name']            = __('Component', 'monitoring')." 3";
//      $tab[$i]['datatype']        = 'itemlink';
//      $tab[$i]['itemlink_type']   = 'PluginMonitoringComponent';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'daily_mail';
//      $tab[$i]['name']            = __('Daily mail', 'kiosks');
//      $tab[$i]['datatype']        = 'bool';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'weekly_mail';
//      $tab[$i]['name']            = __('Weekly mail', 'kiosks');
//      $tab[$i]['datatype']        = 'bool';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'monthly_mail';
//      $tab[$i]['name']            = __('Monthly mail', 'kiosks');
//      $tab[$i]['datatype']        = 'bool';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'is_active';
//      $tab[$i]['name']            = __('Is active ?', 'kiosks');
//      $tab[$i]['datatype']        = 'bool';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'weekly_mail_day';
//      $tab[$i]['name']            = __('Weekly mail day', 'kiosks');
//      $tab[$i]['datatype']        = 'int';
//
//      $i++;
//      $tab[$i]['table']           = $this->getTable();
//      $tab[$i]['field']           = 'monthly_mail_day';
//      $tab[$i]['name']            = __('Monthly mail day', 'kiosks');
//      $tab[$i]['datatype']        = 'int';
//
//      return $tab;
//   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }

      switch ($field) {

         case 'user_to_id':
         case 'user_cc_1_id':
         case 'user_cc_2_id':
         case 'user_cc_3_id':
         case 'user_bcc_id':
         if ($values[$field] == 0) {
            return " ";
         } else if ($values[$field] != -1) {
            $item = new User();
            $item->getFromDB($values[$field]);
            return $item->getLink();
         } else {
            return "-";
         }
         break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   function defineTabs($options=array()){
      $ong = array();
      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {

         case 'User' :
            if (self::canView()) {
               return array(1 => __('DashKiosk mail', 'kiosks'));
            } else {
               return '';
            }
            break;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {

         case 'User' :
            if (Session::haveRight("config", 'r')) {
               $pkMailNotification = new PluginAlignakMailNotification();
               // Show form from entity Id
               $pkMailNotification->showForm(-1, $item->getID(), array( 'canedit'=>self::canUpdate(), 'colspan'=>4 ));
            } else {
               return '';
            }
            break;
      }
      return true;
  }


/**
    * Get value of config
    *
    * @global object $DB
    * @param value $name field name
    * @param integer $users_id
    *
    * @return value of field
    */
   function getValueAncestor($name, $users_id) {
      global $DB;

      $entities_ancestors = getAncestorsOf("glpi_entities", $users_id);

      $nbentities = count($entities_ancestors);
      for ($i=0; $i<$nbentities; $i++) {
         $entity = array_pop($entities_ancestors);
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `users_id`='".$entity."'
               AND `".$name."` IS NOT NULL
            LIMIT 1";
         $result = $DB->query($query);
         if ($DB->numrows($result) != 0) {
            $data = $DB->fetch_assoc($result);
            return $data[$name];
         }
      }
      // $this->getFromDB(1);
      return $this->getField($name);
   }

   /**
    * Get the value (of this entity or parent entity or in general config
    *
    * @global object $DB
    * @param value $name field name
    * @param integet $users_id
    *
    * @return value value of this field
    */
   function getValue($name, $users_id) {
      global $DB;

      $where = '';
      if ($name == 'agent_base_url') {
         $where = "AND `".$name."` != ''";
      }

      $query = "SELECT `".$name."` FROM `".$this->getTable()."`
         WHERE `users_id`='".$users_id."'
            AND `".$name."` IS NOT NULL
            ".$where."
         LIMIT 1";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_assoc($result);
         return $data[$name];
      }
      return $this->getValueAncestor($name, $users_id);
   }



   /**
    * Set default content
    */
   function setDefaultContent($users_id=-1) {
      // $this->fields["id"]             = -1;
      $this->fields["name"]           = "";

      $this->fields["user_to_id"]     = -1;
      $this->fields["user_cc_1_id"]   = -1;
      $this->fields["user_cc_2_id"]   = -1;
      $this->fields["user_cc_3_id"]   = -1;
      $this->fields["user_bcc_id"]    = -1;

      $this->fields["component_1"]    = -1;
      $this->fields["component_2"]    = -1;
      $this->fields["component_3"]    = -1;
      $this->fields["component_4"]    = -1;
      $this->fields["component_5"]    = -1;

      $this->fields["daily_mail"]               = 0;
      $this->fields["daily_subject_template"]   = __('Daily counters (#date#)', 'kiosks');
      $this->fields["weekly_mail"]              = 0;
      $this->fields["weekly_mail_day"]          = 1;
      $this->fields["weekly_subject_template"]  = __('Weekly counters (#date#)', 'kiosks');
      $this->fields["monthly_mail"]             = 0;
      $this->fields["monthly_subject_template"] = __('Monthy counters (#date#)', 'kiosks');
      $this->fields["monthly_mail_day"]         = 1;
   }


   function showForm($items_id=-1, $users_id=-1, $options=array(), $copy=array()) {
      global $DB,$CFG_GLPI;

      if ($items_id != -1) {
         // Show mail notification by ID (not in entity tab form !)
         $this->getFromDB($items_id);
      } else {
         // Show mail notification parameters in user tab form ...
         $a_confs = $this->find("`user_to_id`='".$users_id."'", "", 1);
         if (count($a_confs) > 0) {
            $a_conf = current($a_confs);
            $items_id = $a_conf['id'];
            $this->getFromDB($items_id);
         } else {
           // Add item
           $this->getEmpty();
           $this->setDefaultContent();
           $this->fields['user_to_id'] = $users_id;
           // $items_id = $this->add($this->fields);
           // $this->getFromDB($items_id);
         }
      }

      $users_id = $this->fields["user_to_id"];
      $user = new User();
      $user->getFromDB($users_id);

      if (count($copy) > 0) {
         // Copy item
         foreach ($copy as $key=>$value) {
            $this->fields[$key] = stripslashes($value);
         }
      }

      $this->initForm($items_id, $options);
      // $this->showTabs($options);
      $this->showFormHeader($options);


      echo "<tr>";
      echo "<td>";
      echo __('Is active?', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
      } else {
         echo Dropdown::getYesNo($this->fields['is_active']);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>".__('Recipient', "kiosks")."</td>";
      echo "<td colspan='5'>";
      // User must have an email set ...
      echo $user->getName();
      if ($user->getDefaultEmail()) {
         echo "&nbsp;:&nbsp;".$user->getDefaultEmail();
         echo "<input type='hidden' name='id' value='".$this->fields['id']."' size='20'/>";
         echo "<input type='hidden' name='user_to_id' value='".$users_id."' size='20'/>";
      } else {
         echo "&nbsp;:&nbsp;".'<strong><i class="red">&nbsp;'.__('User email is not defined, notifications will not be sent !')."&nbsp;".'</i></strong>';
      }
      echo "</td>";
      echo "</tr>";

      $entity = new Entity();
      $entity->getFromDB($user->fields['entities_id']);

      echo "<tr>";
      echo "<td>".__('Mail notification name', "kiosks")."</td>";
      echo "<td colspan='7'>";
      if (! empty($this->fields["name"])) {
         echo "<input type='text' name='name' value='".$this->fields["name"]."' size='20'/>";
      } else {
         echo "<input type='text' name='name' value='". __('Mail notification ', 'kiosks') . $user->fields["name"] ."' size='20'/>";
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan=\"8\">";
      echo "<hr/>";
      echo "</td></tr>";

      // Counters components
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Components: ', 'monitoring')."</strong>";
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>".__('Components', "monitoring")."</td>";

      echo "<td colspan='1'>";
      Dropdown::show("PluginMonitoringComponent",
            array('name'=>'component_1',
                  'value'=>$this->fields['component_1']));
      echo "</td>";

      echo "<td colspan='1'>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_2',
                              'value'=>$this->fields['component_2']));
      echo "</td>";

      echo "<td colspan='1'>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_3',
                              'value'=>$this->fields['component_3']));
      echo "</td>";

      echo "<td colspan='1'>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_4',
                              'value'=>$this->fields['component_4']));
      echo "</td>";

      echo "<td colspan='1'>";
      Dropdown::show("PluginMonitoringComponent",
                        array('name'=>'component_5',
                              'value'=>$this->fields['component_5']));
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "</tr>";


      // Mail copies
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Send copies to: ', 'kiosks')."</strong>";
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>".__('Copies', "kiosks")."</td>";

      echo "<td colspan='2'>";
      $user->dropdown(array(
                      'name'=>'user_cc_1_id',
                      'value'=>$this->fields['user_cc_1_id'],
                      'right'=>'all',
                      'comments'=>true,
                      'entity'=>$entity->getID(),
                      'entity_sons'=>true
                      )
      );
      echo "</td>";

      echo "<td colspan='2'>";
      $user->dropdown(array(
                      'name'=>'user_cc_2_id',
                      'value'=>$this->fields['user_cc_2_id'],
                      'right'=>'all',
                      'comments'=>true,
                      'entity'=>$entity->getID(),
                      'entity_sons'=>true
                      )
      );
      echo "</td>";

      echo "<td colspan='2'>";
      $user->dropdown(array(
                      'name'=>'user_cc_3_id',
                      'value'=>$this->fields['user_cc_3_id'],
                      'right'=>'all',
                      'comments'=>true,
                      'entity'=>$entity->getID(),
                      'entity_sons'=>true
                      )
      );
      echo "</td>";
      echo "<td colspan='1'>";
      echo "</td>";
      echo "</tr>";


      echo "<tr>";
      echo "<td>".__('Blind copies', "kiosks")."</td>";

      echo "<td colspan='6'>";
      $user->dropdown(array(
         'name'=>'user_bcc_id',
         'value'=>$this->fields['user_bcc_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$entity->getID(),
         'entity_sons'=>true
      ));
      echo "</td>";
      echo "<td colspan='1'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan=\"8\">";
      echo "<hr/>";
      echo "</td></tr>";

      // Mail notifications
      echo "<tr><td colspan=\"8\">";
      echo "<strong>".__('Mail notifications types: ', 'kiosks')."</strong>";
      echo "/".$this->fields['daily_subject_template']."/";
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>";
      echo __('Daily mail', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         Dropdown::showYesNo('daily_mail', $this->fields['daily_mail']);
      } else {
         echo Dropdown::getYesNo($this->fields['daily_mail']);
      }
      echo "</td>";
      echo "<td>";
      echo "</td>";
      echo "<td>";
      echo __(', subject template:', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td colspan='5'>";
      if (self::canUpdate()) {
         echo '<input type="text" name="daily_subject_template" value="' . $this->fields["daily_subject_template"] . '" size="80"/>';
      } else {
         echo '<input type="text" name="daily_subject_template" value="' . $this->fields["daily_subject_template"] . '" size="80" readonly="1" disabled="1" />';
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>";
      echo __('Weekly mail', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         Dropdown::showYesNo('weekly_mail', $this->fields['weekly_mail']);
      } else {
         echo Dropdown::getYesNo($this->fields['weekly_mail']);
      }
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         echo '<input type="text" name="weekly_mail_day" value="' . $this->fields["weekly_mail_day"] . '" size="2"/>';
      } else {
         echo '<input type="text" name="weekly_mail_day" value="' . $this->fields["weekly_mail_day"] . '" size="2" readonly="1" disabled="1" />';
      }
      echo "</td>";
      echo "<td>";
      echo __(', subject template:', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td colspan='5'>";
      if (self::canUpdate()) {
         echo '<input type="text" name="weekly_subject_template" value="'.$this->fields["weekly_subject_template"].'" size="80"/>';
      } else {
         echo '<input type="text" name="weekly_subject_template" value="'.$this->fields["weekly_subject_template"].'" size="80" readonly="1" disabled="1" />';
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>";
      echo __('Monthly mail', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         Dropdown::showYesNo('monthly_mail', $this->fields['monthly_mail']);
      } else {
         echo Dropdown::getYesNo($this->fields['monthly_mail']);
      }
      echo "</td>";
      echo "<td>";
      if (self::canUpdate()) {
         echo '<input type="text" name="monthly_mail_day" value="' . $this->fields["monthly_mail_day"] . '" size="2"/>';
      } else {
         echo '<input type="text" name="monthly_mail_day" value="' . $this->fields["monthly_mail_day"] . '" size="2" readonly="1" disabled="1" />';
      }
      echo "</td>";
      echo "<td>";
      echo __(', subject template:', 'kiosks').'&nbsp;';
      echo "</td>";
      echo "<td colspan='5'>";
      if (self::canUpdate()) {
         echo '<input type="text" name="monthly_subject_template" value="'.$this->fields["monthly_subject_template"].'" size="80"/>';
      } else {
         echo '<input type="text" name="monthly_subject_template" value="'.$this->fields["monthly_subject_template"].'" size="80" readonly="1" disabled="1" />';
      }
      echo "</td>";
      echo "</tr>";

      $options['addbuttons'] = array ("send" => __('Send notification', 'kiosks'));
      $this->showFormButtons($options);

      Html::closeForm();

      return true;
   }
}