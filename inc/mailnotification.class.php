<?php

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginAlignakMailNotification extends CommonDBTM
{

   public $dohistory = true;

   static $rightname = 'plugin_alignak_mailnotification';

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
//         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE `$table` (
                  `id` int(11) NOT NULL auto_increment,
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `is_active` tinyint(1) NOT NULL DEFAULT '1',
                  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `user_to_id` int(11) NOT NULL DEFAULT '-1',
                  `user_cc_1_id` int(11) NOT NULL DEFAULT '-1',
                  `user_cc_2_id` int(11) NOT NULL DEFAULT '-1',
                  `user_cc_3_id` int(11) NOT NULL DEFAULT '-1',
                  `user_bcc_id` int(11) NOT NULL DEFAULT '-1',
                  `daily_mail` tinyint(1) NOT NULL DEFAULT '0',
                  `daily_subject_template` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Compteurs quotidiens (#date#)',
                  `weekly_mail` tinyint(1) NOT NULL DEFAULT '0',
                  `weekly_subject_template` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Compteurs hebdomadaires (#date#)',
                  `weekly_mail_day` int(11) NOT NULL DEFAULT '1',
                  `monthly_mail` tinyint(1) NOT NULL DEFAULT '0',
                  `monthly_subject_template` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Compteurs mensuels (#date#)',
                  `monthly_mail_day` int(11) NOT NULL DEFAULT '1',
                  `component_1` int(11) NOT NULL DEFAULT '-1',
                  `component_2` int(11) NOT NULL DEFAULT '-1',
                  `component_3` int(11) NOT NULL DEFAULT '-1',
                  `component_4` int(11) NOT NULL DEFAULT '-1',
                  `component_5` int(11) NOT NULL DEFAULT '-1',
                  PRIMARY KEY (`id`)
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
      return _n('Mail notification', 'Mail notifications', $nb, 'alignak');
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
          $values = [$field => $values];
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

   function defineTabs($options = []) {
       $ong = [];
       return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'User' :
            if (self::canView()) {
                return [1 => __('Mail notification', 'alignak')];
            } else {
               return '';
            }
            break;
      }
         return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {

         case 'User' :
            if (Session::haveRight("config", READ)) {
                $pkMailNotification = new PluginAlignakMailNotification();
                // Show form from entity Id
                $pkMailNotification->showForm(-1, $item->getID(), [
                   'canedit'=>self::canUpdate(),
                   'colspan'=>4 ]);
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
     * @param  value   $name     field name
     * @param  integer $users_id
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
     * @param  value   $name     field name
     * @param  integet $users_id
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
   function setDefaultContent($users_id = -1) {
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
       $this->fields["daily_subject_template"]   = __('Daily counters (#date#)', 'alignak');
       $this->fields["weekly_mail"]              = 0;
       $this->fields["weekly_mail_day"]          = 1;
       $this->fields["weekly_subject_template"]  = __('Weekly counters (#date#)', 'alignak');
       $this->fields["monthly_mail"]             = 0;
       $this->fields["monthly_subject_template"] = __('Monthy counters (#date#)', 'alignak');
       $this->fields["monthly_mail_day"]         = 1;
   }

   function showForm($ID = -1, $users_id = -1, $options = [], $copy = []) {
      global $DB,$CFG_GLPI;

      if ($ID != -1) {
         // We still know which object if to be edited...
         $this->getFromDB($ID);
      } else {
         // Create a new mail notification...

         // If no user is specified, use the current logged-in user
         if ($users_id == -1) {
            $users_id = Session::getLoginUserID();
         }

         // We still have an item for this user?
         $a_confs = $this->find("`user_to_id`='".$users_id."'", "", 1);
         if (count($a_confs) > 0) {
            // If we have, use the found item
            $a_conf = current($a_confs);
            $ID = $a_conf['id'];
            $this->getFromDB($ID);
         } else {
            // else, create a new item
            $this->getEmpty();
            $this->setDefaultContent();
            $this->fields['user_to_id'] = $users_id;
         }
      }

      // Get a user object
      $users_id = $this->fields["user_to_id"];
      $user = new User();
      $user->getFromDB($users_id);
      echo '<input type="hidden" name="id" value="' . $this->fields['id'] . '"/>';
      echo '<input type="hidden" name="user_to_id" value="' . $users_id . '"/>';

      // Get an entity object
      $entity = new Entity();
      $entity->getFromDB($user->fields['entities_id']);

      // If we copy from another one
      if (count($copy) > 0) {
         // Copy item
         foreach ($copy as $key=>$value) {
            $this->fields[$key] = stripslashes($value);
         }
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

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

      echo '<tr>';
      echo '<td>'.__('Recipient', "alignak").'</td>';
      echo '<td colspan="5">';
      if ($ID != -1) {
         // User must have an email set ...
         echo $user->getLink();
         if ($user->getDefaultEmail()) {
            echo '&nbsp;:&nbsp;' . $user->getDefaultEmail();
         } else {
            echo '&nbsp;:&nbsp;<strong><em class="red">&nbsp;' . __('User email is not defined, notifications will not be sent !') . '&nbsp;</em></strong>';
         }
      } else {
         // Select a user in the database
         $user->dropdown([
            'name'=>'user_to_id',
            'value'=>$this->fields['user_to_id'],
            'right'=>'all',
            'comments'=>true,
            'entity'=>$entity->getID(),
            'entity_sons'=>true]);
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>'.__('Mail notification name', "alignak").'</td>';
      echo '<td colspan="7">';
      if (! empty($this->fields["name"])) {
         echo '<input type="text" name="name" value="'. $this->fields["name"] .'" size="20"/>';
      } else {
         echo '<input type="text" name="name" value="'. __("Mail notification ", "alignak") . $user->fields["name"] .'" size="20"/>';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr><td colspan="8">';
      echo '<hr/>';
      echo '</td></tr>';

      // Counters templates
      echo '<tr><td colspan="8">';
      echo '<strong>'. __('Components: ', 'alignak') .'</strong>';
      echo '</td></tr>';

      echo '<tr>';
      echo '<td>'.__('Components', 'alignak').'</td>';

      echo '<td colspan="1">';
      Dropdown::show(
        "PluginAlignakCounterTemplate",
        ['name'=>'component_1',
        'value'=>$this->fields['component_1']]);
      echo '</td>';

      echo '<td colspan="1">';
      Dropdown::show(
         "PluginAlignakCounterTemplate",
         ['name'=>'component_2',
         'value'=>$this->fields['component_2']]);
      echo '</td>';

      echo '<td colspan="1">';
      Dropdown::show(
         "PluginAlignakCounterTemplate",
         ['name'=>'component_3',
         'value'=>$this->fields['component_3']]);
      echo '</td>';

      echo '<td colspan="1">';
      Dropdown::show(
         "PluginAlignakCounterTemplate",
         ['name'=>'component_4',
         'value'=>$this->fields['component_4']]);
      echo '</td>';

      echo '<td colspan="1">';
      Dropdown::show(
         "PluginAlignakCounterTemplate",
         ['name'=>'component_5',
         'value'=>$this->fields['component_5']]);
      echo '</td>';
      echo '<td colspan="2">';
      echo '</td>';
      echo '</tr>';

      // Mail copies
      echo '<td colspan="8">';
      echo '<strong>'.__('Send copies to: ', 'alignak').'</strong>';
      echo '</td></tr>';

      echo '<tr>';
      echo '<td>'.__('Copies', "alignak").'</td>';

      echo "<td colspan='2'>";
      $user->dropdown([
         'name'=>'user_cc_1_id',
         'value'=>$this->fields['user_cc_1_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$entity->getID(),
         'entity_sons'=>true]);
      echo '</td>';

      echo "<td colspan='2'>";
      $user->dropdown([
         'name'=>'user_cc_2_id',
         'value'=>$this->fields['user_cc_2_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$entity->getID(),
         'entity_sons'=>true]);
      echo '</td>';

      echo "<td colspan='2'>";
      $user->dropdown([
         'name'=>'user_cc_3_id',
         'value'=>$this->fields['user_cc_3_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$entity->getID(),
         'entity_sons'=>true]);
      echo '</td>';
      echo '<td colspan="1">';
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>'.__('Blind copies', "alignak").'</td>';

      echo "<td colspan='6'>";
      $user->dropdown([
         'name'=>'user_bcc_id',
         'value'=>$this->fields['user_bcc_id'],
         'right'=>'all',
         'comments'=>true,
         'entity'=>$entity->getID(),
         'entity_sons'=>true]);
      echo '</td>';
      echo '<td colspan="1">';
      echo '</td>';
      echo '</tr>';

      echo '<td colspan="8">';
      echo "<hr/>";
      echo '</td></tr>';

      // Mail notifications
      echo '<td colspan="8">';
      echo '<strong>'.__('Mail notifications types: ', 'alignak').'</strong>';
      echo '</td></tr>';

      echo '<tr>';
      echo '<td>';
      echo __('Daily mail', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         Dropdown::showYesNo('daily_mail', $this->fields['daily_mail']);
      } else {
          echo Dropdown::getYesNo($this->fields['daily_mail']);
      }
      echo '</td>';
      echo '<td>';
      echo '</td>';
      echo '<td>';
      echo __(', subject template:', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td colspan="5">';
      if (self::canUpdate()) {
          echo '<input type="text" name="daily_subject_template" value="' . $this->fields["daily_subject_template"] . '" size="80"/>';
      } else {
         echo '<input type="text" name="daily_subject_template" value="' . $this->fields["daily_subject_template"] . '" size="80" readonly="1" disabled="1" />';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>';
      echo __('Weekly mail', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         Dropdown::showYesNo('weekly_mail', $this->fields['weekly_mail']);
      } else {
         echo Dropdown::getYesNo($this->fields['weekly_mail']);
      }
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         echo '<input type="text" name="weekly_mail_day" value="' . $this->fields["weekly_mail_day"] . '" size="2"/>';
      } else {
         echo '<input type="text" name="weekly_mail_day" value="' . $this->fields["weekly_mail_day"] . '" size="2" readonly="1" disabled="1" />';
      }
      echo '</td>';
      echo '<td>';
      echo __(', subject template:', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td colspan="5">';
      if (self::canUpdate()) {
         echo '<input type="text" name="weekly_subject_template" value="'.$this->fields["weekly_subject_template"].'" size="80"/>';
      } else {
         echo '<input type="text" name="weekly_subject_template" value="'.$this->fields["weekly_subject_template"].'" size="80" readonly="1" disabled="1" />';
      }
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>';
      echo __('Monthly mail', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         Dropdown::showYesNo('monthly_mail', $this->fields['monthly_mail']);
      } else {
         echo Dropdown::getYesNo($this->fields['monthly_mail']);
      }
      echo '</td>';
      echo '<td>';
      if (self::canUpdate()) {
         echo '<input type="text" name="monthly_mail_day" value="' . $this->fields["monthly_mail_day"] . '" size="2"/>';
      } else {
         echo '<input type="text" name="monthly_mail_day" value="' . $this->fields["monthly_mail_day"] . '" size="2" readonly="1" disabled="1" />';
      }
      echo '</td>';
      echo '<td>';
      echo __(', subject template:', 'alignak').'&nbsp;';
      echo '</td>';
      echo '<td colspan="5">';
      if (self::canUpdate()) {
         echo '<input type="text" name="monthly_subject_template" value="'.$this->fields["monthly_subject_template"].'" size="80"/>';
      } else {
         echo '<input type="text" name="monthly_subject_template" value="'.$this->fields["monthly_subject_template"].'" size="80" readonly="1" disabled="1" />';
      }
      echo '</td>';
      echo '</tr>';

      $options['addbuttons'] =  ["send" => __('Send notification', 'alignak')];
      $this->showFormButtons($options);

      Html::closeForm();

      return true;
   }
}