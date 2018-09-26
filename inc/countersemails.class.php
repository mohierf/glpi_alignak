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

class PluginAlignakCountersEmails extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_alignak_counters_emails';
   
   private $initEmailerParams = false;
   private $templateId;
   private $urlEmailer;

   const IP_SERVER = '192.168.1.27';   // todo: read from config ....
   const URL_SERVER = '/render?target=';
   
   static function getTypeName($nb = 0) {
      return _n('Counters Emails', 'Counters emails', $nb, 'alignak');
   }

   /**
   * Send graphite counters
   *
   * @param $template_id integer ID of the template
   *
   *@return bool true if read is ok
   *
   **/
   function sendCounters( $template_id) {
      if( !$this->initEmailerParams) {
         $this->initEmailerParams = $this->initEmailer();
      }
      
      $this->templateId = $template_id;
      $ret = $this->readMetrics();
      
      die;
      return $ret;
   }
   
   
   /**
   * Init emailer
   *
   *@return bool true if read is ok
   *
   **/   
   function initEmailer() {
      $this->urlEmailer = 'http://'.$this::IP_SERVER. $this::URL_SERVER;
      return true;
   }
   
   /**
   * readMetrics
   *
   *@return bool true if read is ok
   *
   **/   
   function readMetrics() {
      $counters = [];
      $counters = $this->getCountersFromTemplate();
      $computersName = $this->getComputersNameWithThatTemplate();
      
      foreach( $counters as $counter) {
         $counterGraphiteName = $counter['graphite_name'];
         foreach( $computersName as $computerName) {
            $counterValues = $this->readCounters($counterGraphiteName, $computerName);
            PluginAlignakToolbox::log("ReadC: ". count( $counterValues)." counters for ".$computerName.$counterGraphiteName);
            var_dump( $counterValues);
         }
      }
      return $counterValues;
   }
   
   /**
   * getCountersFromTemplate
   *
   *@return array list of counters for the templateId
   *
   **/   
   function getCountersFromTemplate() {
      $countersTemplate = new PluginAlignakCountersTemplate();
      return $countersTemplate->getCounters( $this->templateId);
   }
   
   /**
   * getComputersNameWithThatTemplate
   *
   *@return array list of computers name for the templateId
   *
   **/   
   function getComputersNameWithThatTemplate() {
      $computerCountersTemplate = new PluginAlignakComputerCountersTemplate();
      return $computerCountersTemplate->getComputersName( $this->templateId);
   }
   

   /**
   * readCounters read the counters for a computer in database
   *
   * @param $counterName string Name of the counter to query
   * @param $computerName string Name of the computer to query
   *
   *@return $counterValues array of pairs (value, timestamp) for that counter
   *
   **/   
   function readCounters($counterGraphiteName, $computerName){
      global $DB;
      
      $table = "glpi_plugin_alignak_graphite_".$counterGraphiteName;
      if (!$DB->tableExists($table)) {
         return null;
      }
      $data = [];
      $querySelect = "SELECT * FROM `".$table."` WHERE `computer_name` = '".$computerName."'";
      echo '<br>QUERY SELECT : '.$querySelect;
      if ($result = $DB->query($querySelect)) {
         if ($DB->numrows($result)) {
            while ($line = $DB->fetch_assoc($result)) {
               $data[$line['timestamp']] = $line;
            }
         }
      }
      return $data;
   }
}

