<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginAlignakCrontasks extends CommonDBTM
{

    // Return true to activate log in pk-cron.log file !
   static function logEnabled() {
       return true;
   }

   static function cronInfo($name) {
      switch ($name) {
         case 'genericGraphiteCounters':
            return  ['description' => __('Update generic Graphite counters', 'alignak')];

         case 'genericDailyCounters':
            return  ['description' => __('Update generic daily counters', 'alignak')];

         case 'specificDailyCounters':
            return ['description' => __('Update specific daily counters', 'alignak')];

         case 'getCSVDailyCounters':
            return ['description' => __('Send daily counters by mail', 'alignak')];
      }
      return [];
   }

    // Compute generic Graphite metrics counters
   static function crongenericGraphiteCounters() {
      global $DB;

      // Interval period ... default is 1 week.
      $interval="1 WEEK";

      /*
      * Create global daily counters table
      */
      $table = "glpi_plugin_alignak_metrics";
      if (! TableExists($table)) {
          $query = "CREATE TABLE `$table` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `timestamp` int(11) DEFAULT '0',
               `hostname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `service` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
               `counter` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
               `value` decimal(8,2) DEFAULT '0.00',
               PRIMARY KEY (`id`),
               KEY `timestamp` (`timestamp`),
               KEY `hostname` (`hostname`),
               KEY `service_counter` (`service`,`counter`)
            ) ENGINE=MyISAM AUTO_INCREMENT=1463 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
          $DB->query($query);
         if (self::logEnabled()) {
            Toolbox::logInFile("pk-cron", "created table '$hdc_table' ...\n");
         }
      }

         $table = "glpi_plugin_alignak_metrics_daily";
      if (! TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `day` date NOT NULL,
               `service` varchar(128) COLLATE utf8_unicode_ci DEFAULT '',
               `counter` varchar(128) COLLATE utf8_unicode_ci DEFAULT '',
               `value` DECIMAL(12,2) DEFAULT 0,
               PRIMARY KEY (`id`),
               UNIQUE KEY `hostname` (`service`,`counter`),
               KEY `day` (`day`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query);
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "created table '$hdc_table' ...\n");
         }
      }

         // All components with active counters
         $sql = "SELECT DISTINCT(`mc`.`description`) as service FROM `glpi_plugin_alignak_counters` AS kc INNER JOIN `glpi_plugin_monitoring_components` AS mc ON `mc`.`id` = `kc`.`plugin_monitoring_components_id` WHERE `kc`.`is_active` = 1;";
         $resultServices = $DB->query($sql);
      while ($services = $DB->fetch_array($resultServices)) {
         $service = $services['service'];
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "crongenericDailyCounters, component = $service\n");
         }

         /*
         * Specific component Counters
         */
         $hdc_table = "glpi_plugin_alignak_metrics_service_".str_replace(" ", "", $service);
         if (! TableExists($hdc_table)) {
             $query = "CREATE TABLE IF NOT EXISTS `$hdc_table` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `entities_id` int(11) DEFAULT 0,
               `hostname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `day` date NOT NULL,
               PRIMARY KEY (`id`),
               UNIQUE KEY `hostname` (`hostname`,`day`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
             $DB->query($query);
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "created table '$hdc_table' ...\n");
            }
         }

         // Get interesting counters for this component ... columns which are known in the counters table !
         $columns = $DB->list_fields($hdc_table);
         if (count($columns) > 0) {
             // Store currently existing counters ...
             $counters = [ ];
            foreach ($columns as $column) {
                // Ignore non counters fields ...
               if (in_array($column['Field'], ['id','hostname','entities_id','day'])) {
                     continue;
               }
                  $counters[] = $column['Field'];
            }
            if (self::logEnabled()) {
                   Toolbox::logInFile("pk-cron", "currently stored counters: ". implode(",", $counters) ."\n");
            }
         }

            // All counters defined for the service
            $sql = "SELECT * FROM `glpi_plugin_alignak_counters` AS kc INNER JOIN `glpi_plugin_monitoring_components` AS mc ON `mc`.`id` = `kc`.`plugin_monitoring_components_id` WHERE `mc`.`description` = '$service' AND `kc`.`is_active` = 1;";
            $resultCounters = $DB->query($sql);
         while ($counters = $DB->fetch_array($resultCounters)) {
             $counter = $counters['counter_name'];
             $aggregation = $counters['aggregation'];
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "starting '$service' service daily counter '$counter' update, aggregation with $aggregation ...\n");
            }

             // Récupération des données du compteur ... avec calcul d'agrégation sur la journée pour tous les hosts.
             $query = "SELECT service, counter, DATE(FROM_UNIXTIME(timestamp)) AS day, $aggregation(value) AS value
               FROM `glpi_plugin_alignak_metrics` AS km
               INNER JOIN `glpi_plugin_alignak_counters` AS kc ON (`km`.`counter` = `kc`.`counter_name` AND `kc`.`is_active` = 1)
               LEFT JOIN `glpi_computers` AS gc ON (`km`.`hostname` = `gc`.`name` AND `gc`.`is_deleted` = '0')
               WHERE
               `service` = '$service' AND `counter` = '$counter'
               AND
               DATE(FROM_UNIXTIME(timestamp)) BETWEEN DATE_ADD(CURDATE(), INTERVAL -$interval) AND CURDATE()
               GROUP BY DATE(FROM_UNIXTIME(timestamp));";

             $result = $DB->query($query);
            while ($row = $DB->fetch_array($result)) {
                $day = $row['day'];
                $value = $row['value'];

                // New table name format ...
                $insert = "INSERT INTO `glpi_plugin_alignak_metrics_daily` SET `hostname` = '*', `service` = '$service', `counter` = '$counter', `day`='".$day."', `value` = '".$value."' ON DUPLICATE KEY UPDATE `".$counter."` = '".$value."'";
                $resultInsert = $DB->query($insert);
            }

             // Récupération des données du compteur ... avec calcul d'agrégation sur la journée par host.
             $query = "SELECT `gc`.`entities_id`, hostname, service, counter, DATE(FROM_UNIXTIME(timestamp)) AS day, $aggregation(value) AS value
               FROM `glpi_plugin_alignak_metrics` AS km
               INNER JOIN `glpi_plugin_alignak_counters` AS kc ON (`km`.`counter` = `kc`.`counter_name` AND `kc`.`is_active` = 1)
               LEFT JOIN `glpi_computers` AS gc ON (`km`.`hostname` = `gc`.`name` AND `gc`.`is_deleted` = '0')
               WHERE
               `service` = '$service' AND `counter` = '$counter'
               AND
               DATE(FROM_UNIXTIME(timestamp)) BETWEEN DATE_ADD(CURDATE(), INTERVAL -$interval) AND CURDATE()
               GROUP BY hostname, DATE(FROM_UNIXTIME(timestamp));";

             $result = $DB->query($query);
            while ($row = $DB->fetch_array($result)) {
                $hostname = $row['hostname'];
                $entities_id = $row['entities_id'];
                $day = $row['day'];
                $counter = $row['counter'];
                $value = $row['value'];

                // Update table structure if counter column does not exist ...
               if (! in_array($counter, $counters)) {
                  if (self::logEnabled()) {
                     Toolbox::logInFile("pk-cron", "$service, unknown column '$counter' in table '$hdc_table'\n");
                  }

                      $queryAlter = "ALTER TABLE `$hdc_table` ADD COLUMN `$counter` DECIMAL(8,2) DEFAULT 0;";
                      $resultAlter = $DB->query($queryAlter);
                  if (self::logEnabled()) {
                     Toolbox::logInFile("pk-cron", "$hdc_table altered: ". serialize($result). "\n");
                  }
                        $counters[] = $counter;
               }

                  // New table name format ...
                  $insert = "INSERT INTO `".$hdc_table."` SET `hostname` = '$hostname', `entities_id` = '$entities_id', `day`='".$day."', `".$counter."` = '".$value."' ON DUPLICATE KEY UPDATE `".$counter."` = '".$value."'";
                  $resultInsert = $DB->query($insert);
            }
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "end '$service' service daily counter '$counter' update\n");
            }
         }
      }

         return true;
   }

    // Compute generic daily counters
   static function crongenericDailyCounters() {
      global $DB;

      // Interval period ... default is 1 week.
      $interval="1 WEEK";

      // All components with active counters
      $sql = "SELECT DISTINCT(`mc`.`description`) as component FROM `glpi_plugin_alignak_counters` AS kc INNER JOIN `glpi_plugin_monitoring_components` AS mc ON `mc`.`id` = `kc`.`plugin_monitoring_components_id` WHERE `kc`.`is_active` = 1";
      $result = $DB->query($sql);
      while ($data = $DB->fetch_array($result)) {
         if (self::logEnabled()) {
            Toolbox::logInFile("pk-cron", "crongenericDailyCounters, component = ". $data['component'] ."\n");
         }
         PluginKiosksCrontasks::componentDailyCounters($data['component'], $interval);
      }

      return true;
   }

    // Compute specific daily counters (CNAM)
   static function cronspecificDailyCounters() {
       // CNAM
       PluginKiosksCrontasks::cnamDailyCounters();

       // Payment Records
       PluginKiosksCrontasks::paymentRecordCounters("payment_record");
       // Game Records
       PluginKiosksCrontasks::gameRecordCounters("game_record");

       // Monitoring CNAM
       PluginKiosksCrontasks::cronMonitoringCnam();

       return true;
   }

    // Build daily counters CSV mails
   static function crongetCSVDailyCounters($notification = 0) {
       // Get current date
       $today = getdate();

       // Get mail notifications list
       $pkNotifications = new PluginKiosksMailNotification();
      if (! is_object($notification)) {
         if (self::logEnabled()) {
            Toolbox::logInFile("pk-cron", "crongetCSVDailyCounters, notification = ". $notification ."\n");
         }
            $notifications = $pkNotifications->find("`is_active`='1' AND `id`='$notification'");
      } else {
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "crongetCSVDailyCounters, notification = all\n");
         }
         $notifications = $pkNotifications->find("`is_active`='1'");
      }
      if (count($notifications) <= 0) {
         return false;
      }

      foreach ($notifications as $notification) {
         // User information
         $user = new User();
         $user->getFromDB($notification['user_to_id']);
         $notification['to'] = [];
         $notification['to'][] =  [$user->getDefaultEmail(), $user->getName()];

         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "Mail notification for ". $user->getName() ."\n");
         }

         // Entities
         $entity = new Entity();
         $entity->getFromDB($user->fields['entities_id']);
         $notification['entity'] = $entity->getID();
         $notification['entities'] = importArrayFromDB($entity->fields['sons_cache']);

         // Mail copies
         $notification['cc'] = [];
         if ($user->getFromDB($notification['user_cc_1_id'])) {
             $notification['cc'][] =  [$user->getDefaultEmail(), $user->getName()];
         }
         if ($user->getFromDB($notification['user_cc_2_id'])) {
             $notification['cc'][] =  [$user->getDefaultEmail(), $user->getName()];
         }
         if ($user->getFromDB($notification['user_cc_3_id'])) {
             $notification['cc'][] =  [$user->getDefaultEmail(), $user->getName()];
         }
         $notification['bcc'] = [];
         if ($user->getFromDB($notification['user_bcc_id'])) {
             $notification['bcc'][] =  [$user->getDefaultEmail(), $user->getName()];
         }

         // Components (up to 5 components maximum ...)
         $notification['components'] = [];
         $pmComponent = new PluginMonitoringComponent();
         if ($pmComponent->getFromDB($notification['component_1'])) {
             // $notification['components'][] = $pmComponent->getName();
             $notification['components'][] = $pmComponent->fields['description'];
         }
         if ($pmComponent->getFromDB($notification['component_2'])) {
             // $notification['components'][] = $pmComponent->getName();
             $notification['components'][] = $pmComponent->fields['description'];
         }
         if ($pmComponent->getFromDB($notification['component_3'])) {
             // $notification['components'][] = $pmComponent->getName();
             $notification['components'][] = $pmComponent->fields['description'];
         }
         if ($pmComponent->getFromDB($notification['component_4'])) {
             // $notification['components'][] = $pmComponent->getName();
             $notification['components'][] = $pmComponent->fields['description'];
         }
         if ($pmComponent->getFromDB($notification['component_5'])) {
             // $notification['components'][] = $pmComponent->getName();
             $notification['components'][] = $pmComponent->fields['description'];
         }

         // Daily notification
         if (isset($notification['daily_mail']) && $notification['daily_mail']=='1') {
             $date = new DateTime(date('Y-m-d H:i:s', strtotime("yesterday")));
            if (! empty($notification['name'])) {
                $notification['subject'] = $notification['name'] . " - ";
            } else {
                $notification['subject'] = "";
            }
             $notification['subject'] .= str_replace("#date#", $date->format('d/m/Y'), $notification['daily_subject_template']);
             PluginKiosksCrontasks::getCSVDailyCounters("yesterday", $notification);
         }

         // Weekly notification (on configured day)
         if ($today['wday']==(int)$notification['weekly_mail_day'] && isset($notification['weekly_mail']) && $notification['weekly_mail']=='1') {
             $date = new DateTime(date('Y-m-d H:i:s', strtotime("yesterday")));
            if (! empty($notification['name'])) {
                $notification['subject'] = $notification['name'] . " - ";
            } else {
                $notification['subject'] = "";
            }
             $notification['subject'] .= str_replace("#date#", $date->format('W'), $notification['weekly_subject_template']);
             PluginKiosksCrontasks::getCSVDailyCounters("previous_week", $notification);
         }

         // Monthly notification (on configured day)
         if ($today['mday']==(int)$notification['monthly_mail_day'] && isset($notification['monthly_mail']) && $notification['monthly_mail']=='1') {
             $date = new DateTime(date('Y-m-d H:i:s', strtotime("yesterday")));
            if (! empty($notification['name'])) {
                $notification['subject'] = $notification['name'] . " - ";
            } else {
                $notification['subject'] = "";
            }
             $notification['subject'] .= str_replace("#date#", $date->format('m'), $notification['monthly_subject_template']);
             PluginKiosksCrontasks::getCSVDailyCounters("previous_month", $notification);
         }

         // Yearly counters
         // $date = new DateTime(date('Y-m-d H:i:s', strtotime("yesterday")));
         // $notification['subject'] = str_replace("#date#", $date->format('y'), $notification['monthly_subject_template']);
         // PluginKiosksCrontasks::getCSVDailyCounters("previous_year", $notification);
      }

         return true;
   }

    // CNAM daily counters
   static function cnamDailyCounters() {

      $DB = new DB();
      if (!$DB->connected) {
          die("No DB connection\n");
      }

      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "start, cnamDailyCounters\n");
      }
         // $date = new DateTime();
         $query = "SELECT `hostname`,
            `glpi_plugin_alignak_hostcounters_daily`.`entities_id`,
            `glpi_entities`.`name` as entity_name, `day`,
            `glpi_plugin_alignak_counters`.`counter_name`,
            `value` FROM `glpi_plugin_alignak_hostcounters_daily`
         LEFT JOIN `glpi_entities` ON `glpi_entities`.`id` = `glpi_plugin_alignak_hostcounters_daily`.`entities_id`
         LEFT JOIN `glpi_plugin_alignak_counters` ON `glpi_plugin_alignak_counters`.`id` = `glpi_plugin_alignak_hostcounters_daily`.`plugin_alignak_counters_id`
         WHERE `glpi_plugin_alignak_counters`.`counter_name`
            IN (
               'cPrintedPages',
               'cRetractedPages',
               'cPaperReams',
               'cReplacedPrinters',
               'cCardsInsertedKo',
               'cCardsRemoved',
               'cCardsInsertedOk',
               'cBinEmptied'
            )
            AND `glpi_plugin_alignak_hostcounters_daily`.`day` BETWEEN DATE_ADD(CURDATE(), INTERVAL -7 DAY)
            AND CURDATE();";
          // AND `glpi_plugin_alignak_hostcounters_daily`.`day` = '".$date->format('Y-m-d')."'";

         $result = $DB->query($query);
      while ($row = $DB->fetch_array($result)) {
         // Fred -> Jérôme : pourquoi réinitialiser ces variables ? Elles ne servent pas dans la boucle ...
         $cPrintedPages = 0;
         $cRetractedPages = 0;
         $cPaperReams = 0;
         $cReplacedPrinters = 0;
         $cCardsInsertedKo = 0;
         $cCardsRemoved = 0;
         $cCardsInsertedOk = 0;
         $cBinEmptied = 0;

         $hostname = $row['hostname'];
         $entities_id = $row['entities_id'];
         $entity_name = $row['entity_name'];
         $day = $row['day'];
         $counter_name = $row['counter_name'];
         $$counter_name = $row['value'];

         $insert = "INSERT INTO `knm_cnam_hostcounters_daily` SET `kiosk_name` = '$hostname', `entities_id` = '$entities_id', `entity_name` = '". $DB->escape($entity_name) ."', `day`='".$day."', `".$counter_name."` = '".$$counter_name."' ON DUPLICATE KEY UPDATE `".$counter_name."` = '".$$counter_name."'";
         $resultInsert = $DB->query($insert);
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "cnam, $hostname/$day, update: ".$counter_name."=".$$counter_name."\n");
         }
      }

      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "end, cnamDailyCounters\n");
      }
   }

    /*
    * Update daily counters table for a specific component :
    * - eLiberty, Rungis, ...
    */
   static function componentDailyCounters($component, $interval = "3 DAY", $addCounter = false) {
       $DB = new DB();

       $return = "";

      if (!$DB->connected) {
          die("No DB connection\n");
      }

         // Manage conditional counters ...
         /*
         // Get all conditional counters ...
         $pkCondCounters = new PluginKiosksCondCounter();
         $condCounters = $pkCondCounters->find("`is_active`='1'");
         */

         /*
         * Specific component Counters
         */
         $hdc_table = "glpi_plugin_alignak_hdc_".str_replace(" ", "", $component);
      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "starting '$component' daily counters update ...\n");
      }

      if (!TableExists($hdc_table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$hdc_table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `kiosk_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL,
            `entity_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `day` date NOT NULL,
             PRIMARY KEY (`id`),
             UNIQUE KEY `hostname` (`kiosk_name`,`day`)
          ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query);
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "created table '$hdc_table' ...\n");
         }
      }

         // Get interesting counters for this component ... columns which are known in the counters table !
         $columns = $DB->list_fields($hdc_table);
      if (count($columns) > 0) {
         // Store currently existing counters ...
         $counters = [ ];
         foreach ($columns as $column) {
             // Ignore non counters fields ...
            if (in_array($column['Field'], ['id','kiosk_name','entities_id','entity_name','day'])) {
                continue;
            }
             $counters[] = $column['Field'];
         }
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "currently stored counters: ". implode(",", $counters) ."\n");
         }
      }

         // Jointure sur le composant dont on veut les compteurs ...
         $query = "SELECT
            `glpi_plugin_alignak_hostcounters_daily`.`hostname`,
            `glpi_plugin_alignak_hostcounters_daily`.`entities_id`,
            `glpi_entities`.`name` AS `entity_name`,
            `glpi_plugin_alignak_hostcounters_daily`.`day`,
            `glpi_plugin_alignak_counters`.`counter_name`,
            `glpi_plugin_alignak_hostcounters_daily`.`value`
         FROM `glpi_plugin_alignak_hostcounters_daily`
         LEFT JOIN `glpi_entities`
            ON (`glpi_plugin_alignak_hostcounters_daily`.`entities_id` = `glpi_entities`.`id`)
         LEFT JOIN `glpi_plugin_alignak_counters`
            ON (`glpi_plugin_alignak_hostcounters_daily`.`plugin_alignak_counters_id` = `glpi_plugin_alignak_counters`.`id`)
         LEFT JOIN `glpi_plugin_monitoring_components`
            ON (`glpi_plugin_alignak_counters`.`plugin_monitoring_components_id` = `glpi_plugin_monitoring_components`.`id`)
         WHERE (`glpi_plugin_alignak_hostcounters_daily`.`day` BETWEEN DATE_ADD(CURDATE(), INTERVAL -$interval) AND CURDATE()
            AND `glpi_plugin_monitoring_components`.`description` ='$component'
            AND `glpi_plugin_alignak_counters`.`is_active` ='1');";
         $return .= $query."\n";

         $result = $DB->query($query);
      while ($row = $DB->fetch_array($result)) {
         $hostname = $row['hostname'];
         $entities_id = $row['entities_id'];
         $entity_name = $row['entity_name'];
         $day = $row['day'];
         $counter_name = $row['counter_name'];
         $$counter_name = $row['value'];

         // Update table structure if counter column does not exist ...
         if (! in_array($counter_name, $counters)) {
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "$component, unknown column '$counter_name' in table '$hdc_table'\n");
            }

             $queryAlter = "ALTER TABLE `$hdc_table` ADD COLUMN `$counter_name` INT(11) DEFAULT 0 NULL;";
             $resultAlter = $DB->query($queryAlter);
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "$hdc_table altered: ". serialize($result). "\n");
            }
             $counters[] = $counter_name;
         }

         // New table name format ...
         $insert = "INSERT INTO `".$hdc_table."` SET `kiosk_name` = '$hostname', `entities_id` = '$entities_id', `entity_name` = '". $DB->escape($entity_name) ."', `day`='".$day."', `".$counter_name."` = '".$$counter_name."' ON DUPLICATE KEY UPDATE `".$counter_name."` = '".$$counter_name."'";
         $resultInsert = $DB->query($insert);
         $return .= $insert."\n";

         // if (self::logEnabled()) Toolbox::logInFile("pk-cron", "$component, $hostname/$day, update: ".$counter_name."=".$$counter_name."\n");

         // Manage conditional counters ...
         /*
         foreach ($condCounters as $condCounter) {

         }
         */
      }
      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "end '$component' daily counters update\n");
      }
         return($return);
   }

   static function paymentRecordCounters($plugin_alignak_counters_name = "payment_record") {
       global $DB;

       // Search payment_record counter id ...
       $sql = "SELECT `id` FROM `glpi_plugin_alignak_counters` WHERE `counter_name` = '$plugin_alignak_counters_name'";
       $result = $DB->query($sql);
       $data = $DB->fetch_array($result);
      if ($data['id'] == null) {
          return;
      } else {
         $plugin_alignak_counters_id = $data['id'];
      }

         $sql = "SELECT MAX(`date`) as max_date FROM `glpi_plugin_alignak_$plugin_alignak_counters_name`";
         $result = $DB->query($sql);
         $data = $DB->fetch_array($result);
      if ($data['max_date'] == null) {
         $where_date = "";
      } else {
         $where_date = " AND `date` > '".$data['max_date']."'";
      }

         $sql = "SELECT
            `glpi_entities`.`id` AS `entities_id`,
            `glpi_entities`.`completename` AS `entity_completename`,
            `glpi_entities`.`name` AS `entity_name`,
            `glpi_computers`.`name` AS `host_name`,
            `glpi_plugin_alignak_records`.`date`,
            `glpi_plugin_alignak_records`.`value`
         FROM `glpi_plugin_alignak_records`
         LEFT JOIN `glpi_computers` ON (`glpi_computers`.`name` = `glpi_plugin_alignak_records`.`hostname`)
         LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
         WHERE `plugin_alignak_counters_id` = '".$plugin_alignak_counters_id."'". $where_date."
         ORDER BY `glpi_plugin_alignak_records`.`date` ASC";

      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "[function paymentRecordCounters] sql : ".$sql."\n");
      }
         $result = $DB->query($sql);

      while ($data = $DB->fetch_array($result)) {

         list($borne_infos, $transaction, $code_application, $montant, $texte_retour) = explode(";", $data['value']);
         list($nom_ilot, $numero_borne) = explode(" - ", $borne_infos);

         $sql_insert = "INSERT INTO `glpi_plugin_alignak_$plugin_alignak_counters_name` "
            . " SET`entities_id` = '".$data['entities_id']."',"
            . " `entity_completename` = '".$data['entity_completename']."',"
            . " `entity_name` = '".$data['entity_name']."',"
            . " `host_name` = '".$data['host_name']."',"
            . " `date` = '".$data['date']."', "
            . " `nom_ilot` = '".$nom_ilot."',"
            . " `numero_borne` = '".$numero_borne."',"
            . " `transaction` = '".$transaction."', "
            . " `code_application` = '".$code_application."',"
            . " `montant` = ".$montant.","
            . " `texte_retour` = '".$texte_retour."'
                  ON DUPLICATE KEY UPDATE `date` = `date`";
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "[function paymentRecordCounters] sql_insert : ".$sql_insert."\n");
         }
         $result_insert = $DB->query($sql_insert);
      }
         return (true);
   }

   static function gameRecordCounters($plugin_alignak_counters_name = "game_record") {
       global $DB;

       // Search game_record counter id ...
       $sql = "SELECT `id` FROM `glpi_plugin_alignak_counters` WHERE `counter_name` = '$plugin_alignak_counters_name'";
       $result = $DB->query($sql);
       $data = $DB->fetch_array($result);
      if ($data['id'] == null) {
          return;
      } else {
         $plugin_alignak_counters_id = $data['id'];
      }

         $sql = "SELECT MAX(`date`) as max_date FROM `glpi_plugin_alignak_$plugin_alignak_counters_name`";
         $result = $DB->query($sql);
         $data = $DB->fetch_array($result);
      if ($data['max_date'] == null) {
         $where_date = "";
      } else {
         $where_date = " AND `date` > '".$data['max_date']."'";
      }

         $sql = "SELECT
            `glpi_entities`.`id` AS `entities_id`,
            `glpi_entities`.`completename` AS `entity_completename`,
            `glpi_entities`.`name` AS `entity_name`,
            `glpi_computers`.`name` AS `host_name`,
            `glpi_plugin_alignak_records`.`date`,
            `glpi_plugin_alignak_records`.`value`
         FROM `glpi_plugin_alignak_records`
         LEFT JOIN `glpi_computers` ON (`glpi_computers`.`name` = `glpi_plugin_alignak_records`.`hostname`)
         LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
         WHERE `plugin_alignak_counters_id` = '".$plugin_alignak_counters_id."'". $where_date."
         ORDER BY `glpi_plugin_alignak_records`.`date` ASC";

      if (self::logEnabled()) {
         Toolbox::logInFile("pk-cron", "[function gameRecordCounters] sql : ".$sql."\n");
      }
         $result = $DB->query($sql);

      while ($data = $DB->fetch_array($result)) {
         list(
         $user_id, $game_id,
         $choice_1, $choice_2, $choice_3, $choice_4, $choice_5, $choice_6,
         $montant, $message
          ) = explode(";", $data['value']);

          $sql_insert = "INSERT INTO `glpi_plugin_alignak_$plugin_alignak_counters_name` "
            . " SET`entities_id` = '".$data['entities_id']."',"
            . " `entity_completename` = '".$data['entity_completename']."',"
            . " `entity_name` = '".$data['entity_name']."',"
            . " `host_name` = '".$data['host_name']."',"
            . " `date` = '".$data['date']."', "
            . " `user_id` = '".$user_id."',"
            . " `game_id` = '".$game_id."',"
            . " `choice_1` = '".$choice_1."',"
            . " `choice_2` = '".$choice_2."',"
            . " `choice_3` = '".$choice_3."',"
            . " `choice_4` = '".$choice_4."',"
            . " `choice_5` = '".$choice_5."',"
            . " `choice_6` = '".$choice_6."',"
            . " `montant` = ".$montant.","
            . " `message` = '".$message."'
                  ON DUPLICATE KEY UPDATE `date` = `date`";
         if (self::logEnabled()) {
            Toolbox::logInFile("pk-cron", "[function gameRecordCounters] sql_insert : ".$sql_insert."\n");
         }
          $result_insert = $DB->query($sql_insert);
      }
         return (true);
   }

   static function getCSVDailyCounters($when = "previous_week", $notification = null) {
       global $DB;

      if (! isset($notification)) {
          return false;
      }

         // Time period (default is current day)
         $interval = "0 DAY";
      switch (strtolower($when)) {

         case 'yesterday':
               $interval = "1 DAY";
          break;

         case 'previous_week':
               $interval = "1 WEEK";
          break;

         case 'previous_month':
               $interval = "1 MONTH";
          break;

         case 'previous_year':
               $interval = "1 YEAR";
      }

         /* Build CSV ... */
         $entities_id = $notification['entities'];
         $components = $notification['components'];

         $sep = ';';
         $enclose = '"';
         $eol = "\r\n";
         $html_eol = "<br/>";

         $headerTitle =  [$enclose.'Jour'.$enclose, $enclose.'Borne'.$enclose];
         $header = [];
         $output = [];
         $counters_total = [];
      foreach ($components as $component) {
         $table = "glpi_plugin_alignak_hdc_".str_replace(" ", "", $component);

         // Get interesting counters for this component ... columns which are known in the counters table !
         $columns = $DB->list_fields($table);
         if (count($columns) <= 0) {
             continue;
         }

         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "getCSVDailyCounters, component: $component, table: $table, get counters ...\n");
         }
         // Get counters for the component ...
         $sql = "SELECT `glpi_plugin_alignak_counters`.`counter_name` AS 'counter_name',
               `glpi_plugin_alignak_counters`.`name` AS 'name',
               `glpi_plugin_alignak_counters`.`ratio` AS 'ratio',
               `glpi_plugin_alignak_counters`.`decimals` AS 'decimals'
            FROM `glpi_plugin_alignak_counters`
            INNER JOIN `glpi_plugin_monitoring_components`
               ON (`glpi_plugin_alignak_counters`.`plugin_monitoring_components_id` = `glpi_plugin_monitoring_components`.`id`)
            WHERE ((`glpi_plugin_alignak_counters`.`is_active`= '1')
               AND (`glpi_plugin_monitoring_components`.`description` = '$component'));";
         // echo "Get counters: ".$sql." \n";

         $result = $DB->query($sql);
         $counters = [ ];
         $counters_ratio = [ ];
         $counters_decimals = [ ];
         while ($data = $DB->fetch_array($result)) {
            foreach ($columns as $column) {
               if ($data['counter_name'] == $column['Field']) {
                     $counters[] = $data['counter_name'];
                     $counters_ratio[] = $data['ratio'];
                     $counters_decimals[] = $data['decimals'];
                     $counters_total[$data['counter_name']] =  ["alias" => $data['counter_name'], "title" => $data['name'], "value" => 0, "ratio" => $data['ratio'], "decimals" => $data['decimals']];
                     $header[] = $data['counter_name'];
                     $headerTitle[] = $enclose.$data['name'].$enclose;
               }
            }
         }

         if (count($counters) > 0) {
             // Get counters values ...
             $sql = "SELECT  `c`.`name`, `day`, `" . implode("`, `", $counters) . "`
               FROM `glpi_computers` AS c
               LEFT JOIN (
                  SELECT `kiosk_name`, `day`, ";

             $first = true;
            foreach ($counters as $counter) {
               if (! $first) {
                     $sql .= ", ";
               }
                  $first = false;
                  $sql .= "SUM(`$counter`) AS '$counter'";
            }

             $sql .= " FROM `$table` AS eh  WHERE `eh`.`day` BETWEEN DATE(DATE_SUB(NOW(), INTERVAL $interval)) AND DATE(DATE_SUB(NOW(), INTERVAL 1 DAY)) GROUP BY `kiosk_name`,`day`
                   ) AS h ON `h`.`kiosk_name` = `c`.`name` WHERE `c`.`entities_id` IN ('" . implode("','", $entities_id) . "') ORDER BY `day`,`name` ASC;";
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "getCSVDailyCounters, query counters: $sql\n");
            }

             // $date = $date->format('d/m/Y');
             $result = $DB->query($sql);
            while ($data = $DB->fetch_array($result)) {
               if (isset($data['day'])) {
                     $date = new DateTime($data['day']);
                     $date = $date->format('d/m/Y');
               } else {
                  continue;
               }

               if (! isset($output[ $date ])) {
                  $output[ $date ] = [];
               }

               if (! isset($output[ $date ][ $data['name'] ])) {
                  $output[ $date ][ $data['name'] ] = [];
               }

                  $idx=0;
               foreach ($counters as $counter) {
                  $output[ $date ][ $data['name'] ][$counter] = number_format($data[$counter] * $counters_ratio[$idx], $counters_decimals[$idx], ",", "");
                  $counters_total[$counter]['value'] += $data[$counter] * $counters_total[$counter]['ratio'];
                  $idx++;
               }
            }
         }
      }

         // Some counters have been fetched ... let's mail !
      if (count($header) > 2) {
         $csv = implode($sep, $headerTitle) . $eol;
         foreach ($output as $date => $array) {
            foreach ($array as $kiosk => $array) {
                $csv .= $enclose.$date.$enclose.$sep;
                $csv .= $enclose.$kiosk.$enclose.$sep;
                $first = true;
               foreach ($header as $column) {
                  if (! isset($array[$column])) {
                     if (! $first) {
                              $csv .= $sep;
                     }
                     $first = false;
                     $csv .= $enclose.'0'.$enclose;
                  } else {
                     if (! $first) {
                          $csv .= $sep;
                     }
                      $first = false;
                      $csv .= $enclose.$array[$column].$enclose;
                  }
               }
                  $csv .= $eol;
            }
         }

         // Create temporay file to store Csv result
         $tempfile = tempnam(sys_get_temp_dir(), 'Hdc');
         if ($tempfile === false) {
             return (false);
         }
         $handle = fopen($tempfile, "w");
         fwrite($handle, $csv);

         // Directly use phpmailer class ...
         $mmail = new NotificationMail();
         $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
         // For exchange
         $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");

         // From ...
         $from = "dashkiosk@knm.ipmfrance.com";
         $fromname = "KNM DashKiosk";
         $mmail->SetFrom($from, $fromname, false);
         $mmail->AddReplyTo($from, $fromname);

         // To ...
         $toMail = '';
         foreach ($notification['to'] as $to) {
             $toMail =$to[0];
             $mmail->AddAddress($to[0], $to[1]);
         }

         // Cc ...
         foreach ($notification['cc'] as $cc) {
             $mmail->AddCC($cc[0], $cc[1]);
         }

         // BCc ...
         foreach ($notification['bcc'] as $bcc) {
             $mmail->AddBCC($bcc[0], $bcc[1]);
         }

         // Subject
         $subject = $notification['subject'];
         $mmail->Subject  = $subject;

         $mmail->addAttachment($tempfile, $subject.".csv");

         $mmail->isHTML(true);
         $mmail->Body    = $subject.$html_eol;
         $mmail->AltBody = $subject.$eol;

         if (count($counters_total) > 0) {
             $mmail->Body    .= $html_eol;
             $mmail->AltBody .= $eol;
            foreach ($counters_total as $counter) {
               if (self::logEnabled()) {
                     Toolbox::logInFile("pk-cron", "getCSVDailyCounters, total {$counter['title']} = {$counter['value']}\n");
               }
                  $mmail->Body    .= $counter['title']. " : " .number_format($counter['value'], $counters_total[$counter['alias']]['decimals'], ",", ""). $html_eol;
                  $mmail->AltBody .= $counter['title']. " : " .number_format($counter['value'], $counters_total[$counter['alias']]['decimals'], ",", ""). $eol;
            }
             $mmail->Body    .= "\n";
             $mmail->AltBody .= "\n";
         }

         if (!$mmail->Send()) {
             $sent = false;
            if (self::logEnabled()) {
                Toolbox::logInFile("pk-cron", "getCSVDailyCounters, mail sending error: ". $mmail->ErrorInfo ."\n");
            }
         } else {
             $sent = true;
            if (self::logEnabled()) {
               Toolbox::logInFile("pk-cron", "getCSVDailyCounters, mail sent to $toMail : $subject\n");
               Toolbox::logInFile(
                   "mail", sprintf(
                       __('%1$s: %2$s'),
                       sprintf(__('An email was sent to %s'), $toMail),
                       $subject."\n"
                   )
               );
            }
         }
            $mmail->ClearAddresses();

            return ($sent);
      } else {
         if (self::logEnabled()) {
             Toolbox::logInFile("pk-cron", "getCSVDailyCounters, no counters to send.\n");
         }
         return (false);
      }
   }

   static function cronMonitoringCnam($interval_days = 7) {
       global $DB;

       $DTinterval_days = new DateInterval('P'.$interval_days.'D');
       $DTfrom_date = new DateTime();
       $DTfrom_date->setTime(23, 59, 59);
       $DTto_date   = new DateTime();
       $DTto_date->sub($DTinterval_days);
       $DTto_date->setTime(0, 0, 0);

       $from_date   = $DTfrom_date->format("Y-m-d");
       $to_date     = $DTto_date->format("Y-m-d");

       $daterange   = new DatePeriod($DTto_date, new DateInterval('P1D'), $DTfrom_date);

       $counter_list = [
        'cPrintedPages',
        'cPagesRemaining',
        'cRetractedPages',
        'cPaperReams',
        'cCardsInsertedOk',
        'cCardsInsertedKo',
        'sk_Remaining_Days',
        'sk_PrintedPages_PE',
        'sk_PrintedPages_NPE',
       ];

       $counter_list_all = [
        'cPrintedPages' => 'cAllPrintedPages',
        'cRetractedPages' => 'cAllRetractedPages',
        'cCardsInsertedOk' => 'cAllCardsInsertedOk',
        'cCardsInsertedKo' => 'cAllCardsInsertedKo'
       ];
       $counters = [];
       $counters_id = [];
       $entities = [];
       $monitoring = [];
       $hostnames = [];

       $sql = "SELECT `id`, `counter_name`,`counter_type` FROM `glpi_plugin_alignak_counters` WHERE `is_active` = 1";
       $result = $DB->query($sql);

       while ($data = $DB->fetch_array($result)) {
           $counters_id[$data['id']] = $data['counter_name'];
           $counters[$data['counter_name']] = $data['id'];
          if ($data['counter_type'] == "storing") {
             $counters_storing[$data['counter_name']] = $data['id'];
            }
         }

         $sql = "SELECT `id`, `name` FROM `glpi_entities`";
         $result = $DB->query($sql);

         while ($data = $DB->fetch_array($result)) {
            $entities[$data['id']] = $data['name'];
         }

         foreach ($counter_list as $counter_list_element) {
            $counter_list_id[] = $counters[$counter_list_element];
         }

         $sql = "SELECT `hostname`, `entities_id`, `plugin_alignak_counters_id`, `day`, `value` FROM `glpi_plugin_alignak_hostcounters_daily` WHERE `plugin_alignak_counters_id` IN ('".join("','", $counter_list_id)."') AND `day` BETWEEN DATE_ADD(CURDATE(), INTERVAL - ".$interval_days." DAY) AND CURDATE() ORDER BY `day` ASC";
         $result = $DB->query($sql);
         // echo "select : $sql\n";

         while ($row = $DB->fetch_array($result)) {
            $hostname = $row['hostname'];
            $entities_id = $row['entities_id'];
            $entity_name = $entities[$row['entities_id']];
            $day = $row['day'];
            $plugin_alignak_counter_id = $row['plugin_alignak_counters_id'];
            $counter_name = $counters_id[$plugin_alignak_counter_id];
            if ($counter_name == "") {
                echo "===> id = $plugin_alignak_counter_id\n";
            }
            $$counter_name = $row['value'];

            if (!isset($monitoring[$hostname][$day])) {
               foreach ($counter_list as $counter_list_element) {
                   $monitoring[$hostname][$day][$counter_list_element] = 0;
               }
            }

            $monitoring[$hostname][$day][$counter_name] = $$counter_name;
            $monitoring[$hostname][$day]['entities_id'] = $entities_id;
            $monitoring[$hostname][$day]['entity_name'] = $entity_name;

            $hostnames[$hostname]['entities_id'] = $entities_id;
            $hostnames[$hostname]['entity_name'] = $entity_name;
         }

         // Remplissage du tableau pour les dates qui manquent par la derniere valeur connue
         foreach ($daterange as $date_row) {
            $new_date = $date_row->format('Y-m-d');
            foreach ($monitoring AS $hostname => $days) {

                $entities_id = $hostnames[$hostname]['entities_id'];
                $entity_name = $hostnames[$hostname]['entity_name'];

                // Init new date with empty values
               if (!isset($monitoring[$hostname][$new_date])) {
                   $monitoring[$hostname][$new_date]['entities_id'] = $entities_id;
                   $monitoring[$hostname][$new_date]['entity_name'] = $entity_name;
                  foreach ($counter_list as $counter_list_element) {
                      $monitoring[$hostname][$new_date][$counter_list_element] = 0;
                  }
               }

                // Recover "storing" type counter value
               foreach ($counter_list as $counter_list_element) {
                  if (isset($counters_storing[$counter_list_element])) {
                      $sql_lastvalue = "SELECT `value` FROM `glpi_plugin_alignak_hostcounters_lastvalues` WHERE `hostname` = '".$hostname."' AND `date` <= '".$new_date."' AND `plugin_alignak_counters_id` = ".$counters_storing[$counter_list_element]." ORDER BY `date` DESC LIMIT 1";
                      $result_lastvalue = $DB->query($sql_lastvalue);
                     if ($result_lastvalue->num_rows != 0) {
                        $row_lastvalue = $DB->fetch_array($result_lastvalue);
                        $monitoring[$hostname][$new_date][$counter_list_element] = $row_lastvalue['value'];
                     }
                  }
               }
            }
         }

         foreach ($monitoring AS $hostname => $days) {
            $sql = "SELECT `plugin_alignak_counters_id` AS 'counter_id', `value` FROM `glpi_plugin_alignak_hostcounters_all` WHERE `plugin_alignak_counters_id` IN ('".join("','", $counter_list_id)."') AND `hostname` = '".$hostname."'";
            $result = $DB->query($sql);

            $monitoring_all = [];
            while ($row = $DB->fetch_array($result)) {
               if (isset($counter_list_all[$counters_id[$row['counter_id']]])) {
                   $monitoring[$hostname][$from_date][$counter_list_all[$counters_id[$row['counter_id']]]] = $row['value'];
               }
            }
         }

         foreach ($monitoring AS $hostname => $days) {
            foreach ($days AS $day => $values) {
                $sql = "SELECT * FROM `glpi_plugin_alignak_monitoring_cnam` "
                  . " WHERE `hostname` = '".$hostname."' AND `day` = '".$day."'";
                $result = $DB->query($sql);
                $first = 1;
                $monitoring_values = "";
               foreach ($values AS $counter_name => $counter_value) {
                   // $counter_value = preg_replace("/'/", "\'\'", $counter_value);
                   // $counter_value = mysql_real_escape_string($counter_value);
                  if ($counter_name == "") {
                      echo "===> $hostname/$day : $counter_name => $counter_value\n";
                      print_r($values);
                      exit;
                      continue;
                  }
                  if ($first) {
                      $first = 0;
                  } else {
                      $monitoring_values .= ", ";
                  }
                     $monitoring_values .= "`".$counter_name."` = \"".$counter_value."\"";
               }
               if ($result->num_rows == 0) {
                   $sql_monitoring = "INSERT INTO `glpi_plugin_alignak_monitoring_cnam` SET `hostname` = '".$hostname."', `day` = '".$day."', ".$monitoring_values;
               } else {
                   $sql_monitoring = "UPDATE `glpi_plugin_alignak_monitoring_cnam` SET ".$monitoring_values." WHERE `hostname` = '".$hostname."' AND `day` = '".$day."'";
               }
                // echo "Insert :$sql_monitoring\n";
                $result_monitoring = $DB->query($sql_monitoring);
            }
         }
   }
}