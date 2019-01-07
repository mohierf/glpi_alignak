<?php

/*
   ------------------------------------------------------------------------
   Plugin Monitoring for GLPI
   Copyright (C) 2011-2016 by the Plugin Monitoring for GLPI Development Team.

   https://forge.indepnet.net/projects/monitoring/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Plugin Monitoring project.

   Plugin Monitoring for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Plugin Monitoring for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Monitoring. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Monitoring for GLPI
   @author    David Durieux
   @co-author
   @comment
   @copyright Copyright (c) 2011-2016 Plugin Monitoring for GLPI team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/monitoring/
   @since     2011

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAlignaksWebservice {

   /*
      Plugin Alignak WS return error message
    */
   static function Error($message='') {

      if (! empty($message)) {
         return array("error"   => $message);
      } else {
         return array("error"   => "Plugin Alignak web service unknown error.");
      }
   }

   /*
      methodGetGeoloc : Get computers host around a GPS point
      For CNAM app
    */
   static function methodGetGeoloc($params, $protocol) {

      $xml   = simplexml_load_string(html_entity_decode($params['xml']));
      $action = key((array)$xml);
      $latu   = $xml->$action->latitudeUtilisateur;
      $longu  = $xml->$action->longitudeUtilisateur;
      $latr   = $xml->$action->latitudeRecherche;
      $longr  = $xml->$action->longitudeRecherche;
      $dist   = $xml->$action->rayon;
      $limit  = $xml->$action->nbrMax;
      $idGaam = $xml->$action->identifiants;

      $bornesup = $xml->$action->borneSup;
      $borneinf = $xml->$action->borneInf;
      $borne    = $dist;

      $result = "";
      Toolbox::logInFile("pk-geoloc",
         "getGeoloc, action: $action, parameters: $latu, $longu, $latr, $longr, $idGaam\n");

      switch ($action) {
         case 'rechercherListe':
            while ($borne <= $bornesup) {
               $result = self::searchGaam($action, $latr, $longr, $dist, $limit);
               // $result .= "($latr, $longr, $dist, $limit)";
               $borne += $borneinf;
            }
            break;

         case 'recupererListe':
            $result = self::searchGaam($action);
            break;

         case 'detailsGaam':
            $result = self::searchGaam($action, 0, 0, 0, 0, $idGaam);
            break;
      }
      return $result;
   }

   /*
      searchGaam : Search list of hosts, a host
      return a XML file
    */
   static private function searchGaam($action, $latr = 0, $longr = 0, $dist = 0, $limit = 0, $id = 0) {
      global $DB;
      $dbu = new DbUtils();
      $earth = 6378;

      $calendars = self::getGeolocXmlCalendars();

      // Get the sub-entities of the root entity
      $a_sons = $dbu->getSonsOf("glpi_entities", 1);

      switch ($action) {

         case 'rechercherListe':
            $query = "SELECT 
              `glpi_locations`.`id`,
              `glpi_locations`.`name` as locationName,
              `glpi_locations`.`completename`,
              `glpi_locations`.`building` as locationLatLong,
              `glpi_computers`.`name` as computerName,
              `glpi_computers`.`entities_id` as entityId,
              `glpi_computermodels`.`name` as computerModel,
              `glpi_entities`.`name` as entityName,
              `glpi_entities`.`entities_id` as entityParent,
              `glpi_entities`.`address` as entityAddress,
              `glpi_entities`.`postcode` as entityPostcode,
              `glpi_entities`.`town` as entityTown,
              `glpi_entities`.`calendars_id` as calendarsId,
              `glpi_calendars`.`comment` as calendarComment,
              ACOS(
                  SIN( RADIANS( SUBSTRING_INDEX(`building`, ',', 1) ) )
                *SIN( RADIANS( $latr                               ) )
               +
                  COS( RADIANS( SUBSTRING_INDEX(`building`, ',', 1) ) )
                *COS( RADIANS( $latr                               ) )
                *COS( RADIANS( SUBSTRING_INDEX(SUBSTRING_INDEX(`building`, ',', 2), ',', -1) - $longr ) )
                )*$earth AS dist
            FROM 
              `glpi_locations`, `glpi_computers`, `glpi_plugin_monitoring_hosts`, 
              `glpi_entities`, `glpi_computermodels`, `glpi_calendars`
            WHERE
            ACOS(
                  SIN( RADIANS( SUBSTRING_INDEX(`building`, ',', 1) ) )
                *SIN( RADIANS( $latr                               ) )
               +
                  COS( RADIANS( SUBSTRING_INDEX(`building`, ',', 1) ) )
                *COS( RADIANS( $latr                               ) )
                *COS( RADIANS( SUBSTRING_INDEX(SUBSTRING_INDEX(`building`, ',', 2), ',', -1) - $longr ) )
                )*$earth
            <= $dist
            AND
              `glpi_locations`.`id` = `glpi_computers`.`locations_id`
            AND
              `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_computers`.`id`
            AND
              `glpi_computers`.`entities_id` = `glpi_entities`.`id`
            AND
              `glpi_computers`.`entities_id` = `glpi_calendars`.`entities_id`
            AND
              `glpi_computers`.`computermodels_id` = `glpi_computermodels`.`id`
            AND
              `glpi_plugin_monitoring_hosts`.`state` = 'UP'
            AND
              `glpi_entities`.`id` IN ('".implode("','", $a_sons)."')
            ORDER BY dist
            LIMIT $limit";
            break;

         case 'recupererListe':
            $query = "SELECT 
              `glpi_locations`.`id`,
              `glpi_locations`.`name` as locationName,
              `glpi_locations`.`completename`,
              `glpi_locations`.`building` as locationLatLong,
              `glpi_computers`.`name` as computerName,
              `glpi_computers`.`entities_id` as entityId,
              `glpi_computermodels`.`name` as computerModel,
              `glpi_entities`.`name` as entityName,
              `glpi_entities`.`entities_id` as entityParent,
              `glpi_entities`.`address` as entityAddress,
              `glpi_entities`.`postcode` as entityPostcode,
              `glpi_entities`.`town` as entityTown,
              `glpi_entities`.`calendars_id` as calendarsId
              `glpi_calendars`.`comment` as calendarComment,
            FROM 
              `glpi_locations`, `glpi_computers`, `glpi_plugin_monitoring_hosts`, 
              `glpi_entities`, `glpi_computermodels`, `glpi_calendars`
            WHERE
              `glpi_locations`.`id` = `glpi_computers`.`locations_id`
            AND
              `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_computers`.`id`
            AND
              `glpi_computers`.`entities_id` = `glpi_entities`.`id`
            AND
              `glpi_computers`.`entities_id` = `glpi_calendars`.`entities_id`
            AND
              `glpi_computers`.`computermodels_id` = `glpi_computermodels`.`id`
            AND
              `glpi_plugin_monitoring_hosts`.`state` = 'UP'
            AND
              `glpi_entities`.`id` IN ('".implode("','", $a_sons)."')";
            break;

         case 'detailsGaam':
            $id_list = explode(',', $id);
            foreach ($id_list as $host) {
               $id_all[] = "'".$host."'";
            }
            $id = join(",", $id_all);
            $query = "
            SELECT
              `glpi_locations`.`id`,
              `glpi_locations`.`name` as locationName,
              `glpi_computers`.`name` as computerName,
              `glpi_computermodels`.`name` as computerModel,
              `glpi_computers`.`entities_id` as entityId,
              `glpi_locations`.`completename`,
              `glpi_locations`.`building` as locationLatLong,
              `glpi_entities`.`name` as entityName,
              `glpi_entities`.`entities_id` as entityParent,
              `glpi_entities`.`address` as entityAddress,
              `glpi_entities`.`postcode` as entityPostcode,
              `glpi_entities`.`town` as entityTown,
              `glpi_calendars`.`comment` as calendarComment,
              `glpi_entities`.`calendars_id` as calendarsId
            FROM 
              `glpi_locations`, `glpi_computers`, `glpi_plugin_monitoring_hosts`, 
              `glpi_entities`, `glpi_computermodels`, `glpi_calendars`
            WHERE
              `glpi_locations`.`id` = `glpi_computers`.`locations_id`
            AND
              `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_computers`.`id`
            AND
              `glpi_plugin_monitoring_hosts`.`state` = 'UP'
            AND
              `glpi_computers`.`entities_id` = `glpi_entities`.`id`
            AND
              `glpi_computers`.`entities_id` = `glpi_calendars`.`entities_id`
            AND
              `glpi_computers`.`computermodels_id` = `glpi_computermodels`.`id`
            AND
              `glpi_computers`.`name` IN ( $id )
            AND
              `glpi_entities`.`id` IN ('".implode("','", $a_sons)."')";
            break;

         default:
            return "";
      }

      // Toolbox::logInFile("pk-geoloc", "getGeoloc, $action, query: $query\n");

      // return $query; exit;
      $result = $DB->query($query);

      $xml_answer = '<?xml version="1.0" encoding="UTF-8" standalone="no" ?><root>';
      switch ($action) {
         case 'rechercherListe':
            while($data=$DB->fetch_array($result)) {
               Toolbox::logInFile("pk-geoloc", "getGeoloc, $action, data: ".serialize($data)."\n");
               $address = $data['entityAddress'].' '.$data['entityPostcode'].' '.$data['entityTown'];

               // Remove accents
               $address = htmlentities($address, ENT_NOQUOTES, 'utf-8');
               $address = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $address);
               $address = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $address);
               $address = preg_replace('#&[^;]+;#', '', $address);

               $address = strtoupper(str_replace(",", "", $address));

               $parent_entity = new Entity();
               $parent_entity->getFromDB($data['entityParent']);
               $parent_name = strtoupper($parent_entity->fields['name']);
               $calendars_id = self::getGeolocCalendarId($data['entityId'], $data['calendarsId']);
               if (! array_key_exists($calendars_id, $calendars)) {
                  Toolbox::logInFile("pk-geoloc", "ERROR: $calendars_id do not have declared segments !\n");
                  $calendars[$calendars_id] = "Horaires non renseignes.";
               } else {
                  Toolbox::logInFile("pk-geoloc", "decode calendar: ".htmlspecialchars_decode($calendars[$calendars_id])."\n");
               }
               try {
                  $xml_answer .= '<rechercherListeResponse><code>'.$data['computerName'].'</code><libelle><![CDATA['.$data['locationName'].']]></libelle><listeGaam><identifiant>'.$data['computerName'].'</identifiant><codeCaisse>'.$data['entityName'].'</codeCaisse><libelleCaisse>'.$parent_name.'</libelleCaisse><adresse>'.$address.'</adresse><info></info><infosSupl>'.$data['calendarComment'].'</infosSupl><geocode>'.$data['locationLatLong'].'</geocode><typeBorne>'.$data['computerModel'].'</typeBorne>'.htmlspecialchars_decode($calendars[$calendars_id]).'<distance>'.$data['dist'].'</distance></listeGaam></rechercherListeResponse>';
               } catch (Exception $e) {
                  Toolbox::logInFile("pk-geoloc", "rechercherListe, exception: ".$e->getMessage()."\n");
               }
            }
            break;

         case 'recupererListe':
            while($data=$DB->fetch_array($result)) {
               Toolbox::logInFile("pk-geoloc", "getGeoloc, $action, data: ".serialize($data)."\n");
               $address = $data['entityAddress'].' '.$data['entityPostcode'].' '.$data['entityTown'];

               // Remove accents
               $address = htmlentities($address, ENT_NOQUOTES, 'utf-8');
               $address = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $address);
               $address = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $address);
               $address = preg_replace('#&[^;]+;#', '', $address);

               $address = strtoupper(str_replace(",", "", $address));

               $parent_entity = new Entity();
               $parent_entity->getFromDB($data['entityParent']);
               $parent_name = strtoupper($parent_entity->fields['name']);
               $calendars_id = self::getGeolocCalendarId($data['entityId'], $data['calendarsId']);
               if (! array_key_exists($calendars_id, $calendars)) {
                  Toolbox::logInFile("pk-geoloc", "ERROR: $calendars_id do not have declared segments !\n");
                  $calendars[$calendars_id] = "Horaires non renseignes.";
               } else {
                  Toolbox::logInFile("pk-geoloc", "decode calendar: ".htmlspecialchars_decode($calendars[$calendars_id])."\n");
               }
               try {
                  $xml_answer .= '<recupererListeResponse><code>'.$data['computerName'].'</code><libelle><![CDATA['.$data['locationName'].']]></libelle><listeGaam><identifiant>'.$data['computerName'].'</identifiant><codeCaisse>'.$data['entityName'].'</codeCaisse><libelleCaisse>'.$parent_name.'</libelleCaisse><adresse>'.$address.'</adresse><info></info><infosSupl>'.$data['calendarComment'].'</infosSupl><geocode>'.$data['locationLatLong'].'</geocode><typeBorne>'.$data['computerModel'].'</typeBorne>'.htmlspecialchars_decode($calendars[$calendars_id]).'<distance></distance></listeGaam></recupererListeResponse>';
               } catch (Exception $e) {
                  Toolbox::logInFile("pk-geoloc", "recupererListe, exception: ".$e->getMessage()."\n");
               }
            }
            break;

         case 'detailsGaam':
            while($data=$DB->fetch_array($result)) {
               Toolbox::logInFile("pk-geoloc", "getGeoloc, $action, data: ".serialize($data)."\n");
               $address = $data['entityAddress'].' '.$data['entityPostcode'].' '.$data['entityTown'];

               // Remove accents
               $address = htmlentities($address, ENT_NOQUOTES, 'utf-8');
               $address = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $address);
               $address = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $address);
               $address = preg_replace('#&[^;]+;#', '', $address);

               $address = strtoupper(str_replace(",", "", $address));

               $parent_entity = new Entity();
               $parent_entity->getFromDB($data['entityParent']);
               $parent_name = strtoupper($parent_entity->fields['name']);
               $calendars_id = self::getGeolocCalendarId($data['entityId'], $data['calendarsId']);
               if (! array_key_exists($calendars_id, $calendars)){
                  Toolbox::logInFile("pk-geoloc", "ERROR: $calendars_id do not have declared segments !\n");
                  $calendars[$calendars_id] = "Horaires non renseignes.";
               } else {
                  Toolbox::logInFile("pk-geoloc", "decode calendar: ".htmlspecialchars_decode($calendars[$calendars_id])."\n");
               }
               try {
                  $xml_answer .= '<detailsGaamResponse><code>'.$data['computerName'].'</code><libelle><![CDATA['.$data['locationName'].']]></libelle><listeGaam><identifiant>'.$data['computerName'].'</identifiant><codeCaisse>'.$data['entityName'].'</codeCaisse><libelleCaisse>'.$parent_name.'</libelleCaisse><adresse>'.$address.'</adresse><info></info><infosSupl>'.$data['calendarComment'].'</infosSupl><geocode>'.$data['locationLatLong'].'</geocode><typeBorne>'.$data['computerModel'].'</typeBorne>'.htmlspecialchars_decode($calendars[$calendars_id]).'<distance></distance></listeGaam></detailsGaamResponse>';
               } catch (Exception $e) {
                  Toolbox::logInFile("pk-geoloc", "detailsGaam, exception: ".$e->getMessage()."\n");
               }
            }
            break;

         default:
            Toolbox::logInFile("pk-geoloc", "getGeoloc, $action, unknown action!\n");
            return "";
      }

      $xml_answer .= '</root>';
      return $xml_answer;
   }

   static private function getGeolocXmlCalendars() {
      global $DB;

      $calendars_data = [];
      $calendars = [];
      $calendars[0] = "<horaires></horaires>";

      $daysofweek = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

      $query = "SELECT * FROM `glpi_calendarsegments` ORDER BY `day`, `begin`, `end`";
      $result = $DB->query($query);

      $horaires = 0;
      $previous_day = -1;

      while ($data = $DB->fetch_array($result)) {
         list($bh, $bm, $bs) = explode(":", $data['begin']);
         list($eh, $em, $es) = explode(":", $data['end']);
         $liste_creneau = "<listeCreneau>$bh:$bm - $eh:$em</listeCreneau>";
         if (isset($calendars_data[$data['calendars_id']][$data['day']])) {
            $calendars_data[$data['calendars_id']][$data['day']] .= $liste_creneau;
         } else {
            $calendars_data[$data['calendars_id']][$data['day']]  = $liste_creneau;
         }
      }

      foreach ($calendars_data as $id_calendars => $datas) {
         foreach ($datas as $id_day => $creneaux) {
            $horaire_day = "<horaires><jour>".$daysofweek[$id_day]."</jour>".$creneaux."</horaires>";
            if (isset($calendars[$id_calendars])) {
               $calendars[$id_calendars] .= $horaire_day;
            } else {
               $calendars[$id_calendars]  = $horaire_day;
            }
         }
      }
      return ($calendars);
   }

   static private function getGeolocCalendarId($entity_id, $calendar_id) {
      $dbu = new DbUtils();
      if ($calendar_id == -2) {
         $entities = array_reverse($dbu->getAncestorsOf("glpi_entities", $entity_id));
         foreach ($entities as $entity) {
            $item = new Entity();
            $item->getFromDB($entity);
            if ($item->fields['calendars_id'] != -2) {
               $calendar_id = $item->fields['calendars_id'];
               break;
            }
         }
      }
      return ($calendar_id);
   }

   private function to_seconds($date) {
      // $date is a php DateTime object
      return ( $date->y * 365 * 24 * 60 * 60) +
              ($date->m * 30 * 24 * 60 * 60) +
              ($date->d * 24 * 60 * 60) +
              ($date->h * 60 * 60) +
              ($date->i * 60) +
               $date->s;
   }

   /*
    * WS getDashboard :
    *  Récupération de la configuration du dashboard
    *
    * - debug : mode debug
    *   Renvoie un objet debugWs et un objet debugSsp contenant les informations sur la requête et son traitement.
    *
    * - name :
    *   Permet de définir le nom de la configuration à récupérer dans la base de données ou dans un fichier "/plugins/kiosks/conf/*.json"
    *   Défaut: obligatoire
    *
    * - template :
    *   Permet de définir le nom du template à compléter quand on récupère la configuration dans la base de données (fichier "/plugins/kiosks/conf/*.json")
    *   Défaut: obligatoire
    *
    * WS output :
    *
         result=false en cas de problème, error contient un message explicatif (erreur ou détail de l'erreur Json)
         result=true, configuration contient l'objet dc du Dashboard
    {
         "result":true,
         "error":"",
         "configuration":{
             "debugJs":true,
             "debugDialog":false,
             "debugMaps":false,
             "countersPanels":{
                  "main":{
                      "present":true,
                      "collapsed":false,
                      "refresh":300
                      },
                      ...
             }
         }
    }
    *
    */
   static function methodGetDashboard($params, $protocol) {
      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
      }
      if ($debug) {
         $debugFields = array();
         $debugFields['ws'] = "kiosks.getDashboard";
      }

      $templateRoot = "default";
      if ( isset($params['template']) && (! empty($params['template'])) ) {
         $templateRoot = strtolower($params['template']);
      }
      if ($debug) {
         $debugFields['requiredTemplate'] = $templateRoot;
      }

      if ( isset($params['name']) && (! empty($params['name'])) ) {
         $name = strtolower($params['name']);
      } else {
         return (array( 'error' => "Missing parameter 'name'." ));
      }
      if ($debug) {
         $debugFields['requiredName'] = $name;
      }

      // Connected user
      $user = new User();
      $user->getFromDB(Session::getLoginUserID());
      $entity = new Entity();
      $entity->getFromDB($user->fields['entities_id']);
      if ($debug) {
         $debugFields['connectedUser'] = $user->fields['name']." (".$user->getName(1)."), entité: ".$entity->getID()." (".$entity->getName().")";
      }

      // Connected user has a defined dashboard ?
      $pkDashboard = new PluginAlignaksDashboard();
      // Search active dashboard where user profile is Dashboard name ...
      $a_dashboards = $pkDashboard->find("`name` LIKE '".$params['name']."' AND `is_active`='1'", "", 1);
      if (count($a_dashboards) == 0) {
         if ($debug) {
            $debugFields['DashboardDB1'] = "Dashboard not found in DB with name LIKE " . $params['name'];
         }

         // Search active dashboard where clients_id is logged in user entities_id.
         $a_dashboards = $pkDashboard->find("`clients_id` = '".$entity->getID()."' AND `is_active`='1'", "", 1);
         if (count($a_dashboards) > 0) {
            $debugFields['DashboardDB2'] = "Dashboard found in DB with clients_id = " . $entity->getID();
         }
      }

      if (count($a_dashboards) > 0) {
         // Found a dashboard in DB ...
         $a_found = current($a_dashboards);
         $pkDashboard->getFromDB($a_found['id']);

         if ($debug) {
            $debugFields['DashboardDB'] = "Dashboard found in DB with name LIKE " . $pkDashboard->getField('name');
         }

         // Build a dashboard configuration file from template ...
         $file = GLPI_ROOT . "/plugins/kiosks/conf/dashboard.$templateRoot.json";
         $result = false;
         $configuration = '';
         $json_error='';

         if (is_file($file)) {
            if ($debug) {
               $debugFields['templateDashboard'] = $file." was found";
            }
            // Main dashboard template
            $template = file_get_contents($file);
            $replacements = [];
            foreach ($pkDashboard->fields as $key=>$value) {
               $template = str_replace("#$key#", $value, $template);
               $replacements[] = "$key = $value";
            }

            // Counters panel template
            $c_result="";
            $c_file = GLPI_ROOT . "/plugins/kiosks/conf/dashboard.$templateRoot-counters.json";
            if (is_file($c_file)) {
               if ($debug) {
                  $debugFields['templateCounters'] = $c_file." was found";
               }
               // Default counters template
               $c_template = file_get_contents($c_file);
               if ($debug) {
                  $debugFields['c_template'] = $c_template;
               }

               $pkCounter = new PluginAlignaksDashboardCounter();
               $counters = $pkCounter->find("`plugin_kiosks_dashboard_id` = '". $pkDashboard->fields['id'] ."'", "id");
               if ($debug) {
                  $debugFields['c_count'] = count($counters)." counters";
               }
               $i=0;
               foreach ($counters as $counter) {
                  // if ($debug) {
                     // $debugFields['counter_'.$counter['id']] = serialize($counter);
                  // }
                  $cCounter=$c_template;
                  $pkCounter = new PluginAlignaksCounter();
                  $pkCounter->getFromDB($counter['plugin_kiosks_counter_id']);
                  if ($debug) {
                     $debugFields['counter_'.$counter['id']] = serialize($pkCounter->fields);
                  }
                  foreach ($pkCounter->fields as $key=>$value) {
                     // if ($debug) {
                        // $debugFields['counter_'.$counter['id'].'_'.$key] = $value;
                     // }
                     $cCounter = str_replace("#$key#", $value, $cCounter);
                  }
                  if ($i > 0) {
                     $c_result .= ",";
                  }
                  $i++;
                  $c_result .= $cCounter;
                  if ($debug) {
                     $debugFields['counter_'.$counter['id'].'_result'] = $cCounter;
                  }
               }
               // $replacements[] = "$key = $value";
            } else {
               if ($debug) {
                  $debugFields['templateCounters'] = $c_file." was not found!";
               }
               return array('result'         => false,
                            'error'          => "Missing template file: $c_file",
                            'configuration'  => null
               );
            }
            $template = str_replace("#template.main_counters#", $c_result, $template);
            // if ($debug) {
               // $debugFields['builtTemplate'] = $template;
            // }
            // if ($debug) {
               // $debugFields['replacements'] = $replacements;
            // }

            $configuration = json_decode($template, true);
            switch (json_last_error()) {

               case JSON_ERROR_NONE:
                  $json_error = '';
                  break;

               case JSON_ERROR_DEPTH:
                  $json_error = ' - Profondeur maximale atteinte';
                  break;

               case JSON_ERROR_STATE_MISMATCH:
                  $json_error = ' - Inadéquation des modes ou underflow';
                  break;

               case JSON_ERROR_CTRL_CHAR:
                  $json_error = ' - Erreur lors du contrôle des caractères';
                  break;

               case JSON_ERROR_SYNTAX:
                  $json_error = ' - Erreur de syntaxe ; JSON malformé';
                  break;

               case JSON_ERROR_UTF8:
                  $json_error = ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage';
                  break;

               default:
                  $json_error = ' - Erreur inconnue';
                  break;
            }
            $result = ($configuration != null);
         } else {
            if ($debug) {
               $debugFields['templateDashboard'] = $file." was not found!";
            }
            return array('result'         => false,
                         'error'          => "Missing template file: $file",
                         'configuration'  => null
            );
         }
      } else {
         if ($debug) {
            $debugFields['DashboardDB2'] = "Dashboard found in DB with clients_id = " . $entity->getID();
         }

         // Dashboard not found in DB ...

         // Try to get dashboard configuration file
         $file = GLPI_ROOT . "/plugins/kiosks/conf/dashboard.$name.json";
         $result = false;
         $configuration = '';
         $json_error='';

         if ( is_file( $file ) ) {
            if ($debug) {
               $debugFields['DashboardDB2'] = "Dashboard found in file = " . $file;
            }
            $configuration = file_get_contents( $file );
            $configuration = json_decode($configuration, true);
            switch (json_last_error()) {

               case JSON_ERROR_NONE:
                  $json_error = '';
                  break;

               case JSON_ERROR_DEPTH:
                  $json_error = ' - Profondeur maximale atteinte';
                  break;

               case JSON_ERROR_STATE_MISMATCH:
                  $json_error = ' - Inadéquation des modes ou underflow';
                  break;

               case JSON_ERROR_CTRL_CHAR:
                  $json_error = ' - Erreur lors du contrôle des caractères';
                  break;

               case JSON_ERROR_SYNTAX:
                  $json_error = ' - Erreur de syntaxe ; JSON malformé';
                  break;

               case JSON_ERROR_UTF8:
                  $json_error = ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage';
                  break;

               default:
                  $json_error = ' - Erreur inconnue';
                  break;
            }
            $result = ($configuration != null);
         } else {
            if ($debug) {
               $debugFields['requiredFilename'] = $file . " not found!";
            }
         }
      }
      if ($debug) {
         return array('result'         => $result,
                      'error'          => $json_error,
                      'configuration'  => $configuration,
                      'debug'          => $debugFields
         );
      }
      return array('result'         => $result,
                   'error'          => $json_error,
                   'configuration'  => $configuration
      );
   }

   /*
    * WS getDatatable :
    *  Récupération de données pour le plugin jQuery DataTables
    *
    * - debug : mode debug
    *   Renvoie un objet debugWs et un objet debugSsp contenant les informations sur la requête et son traitement.
    *
    * - table :
    *   Permet de récupérer la configuration de la table dans un fichier "/plugins/kiosks/conf/table.knm_*.php" (eg. table.knm_kiosks.php pour table=kiosks)
    *   Ce fichier contient le nom de la table dans la base Glpi, la clef primaire et la déclaration des colonnes.
    *   ! Ce fichier peut également contenir une variable $entitiesTable qui donne le nom de la table à utiliser pour filtrer les entités recherchées !
    *   Défaut: obligatoire
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    * - hostsFilter : filtre sur une liste d'ordinateurs
    *   Filtre les résultats pour la liste d'ordinateurs demandée.
    *   exemple: ek3000-0001 ou ek3000-0001,ek3000-0002
    *   Défaut: pas de filtre
    *
    * - order : ordre du résultat
    *   Défaut: pas de tri
    *
    * - groupBy : regroupement du résultat
    *   Défaut: pas de regroupement
    *
    * - distinct : valeurs les valeurs distinctes d'une colonne
    *   Seules les valeurs distinctes d'une colonne dont on passe le numéro sont renvoyées dans 'data'.
    *   Défaut: -1, inactif
    *
    *
    * WS output :
    *
    * A compléter ...
    *
    */
   static function methodGetDatatable($params, $protocol) {
      if (!Session::getLoginUserID()) {
          return (array( 'error' => "User is not authenticated!" ));
      }

      $debug = false;
      if (isset($params['debug'])) {
          $debug=true;
      }
      if ($debug) {
         $debugFields = array();
         $debugFields['ws'] = "kiosks.getDatatable";
      }

      if ( isset($params['table']) && ($params['table']) ) {
         $table = $params['table'];
      } else {
         return (array( 'error' => "Missing parameter 'table'." ));
      }
      if ($debug) {
         $debugFields['requiredTable'] = $table;
      }

      // Get data table configuration file
      $file = GLPI_ROOT . "/plugins/kiosks/conf/table.$table.php";
      if ( is_file( $file ) ) {
         require_once( $file );
      } else {
         return (array( 'error' => "File $file.php not found!" ));
      }
      if ($debug) {
         $debugFields['databaseTable'] = $table;
      }

         // Table's primary key
         if (! isset($primaryKey)) {
            $primaryKey = 'id';
            if ( isset($params['primaryKey']) && ($params['primaryKey']) ) {
                 $primaryKey = $params['primaryKey'];
            }
         }
         if ($debug) {
            $debugFields['primaryKey'] = $primaryKey;
         }

         if (! isset($columns)) {
            return (array( 'error' => "Missing configuration for 'table'. Columns declaration not found!" ));
         }
         if ($debug) {
            $debugFields['columns'] = $columns;
         }

         $extraCondition = $order = $groupBy = '';

         // Entities
         if (isset($params['entitiesList'])) {
             $row['entitiesList']=$params['entitiesList'];
             if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
                return (array( 'error' => "Access to all required entities is not allowed!" ));
             }
             $extraCondition = getEntitiesRestrictRequest("", isset($entitiesTable) ? "$entitiesTable" : "$table", '', $params['entitiesList']) .
                               $extraCondition;
         } else {
             $extraCondition = getEntitiesRestrictRequest("", isset($entitiesTable) ? "$entitiesTable" : "$table") .
                               $extraCondition;
         }
         if ($debug) {
            $debugFields['whereEntities'] = $extraCondition;
         }

         // Hosts filter
         if (isset($params['hostsFilter']) && ! empty($params['hostsFilter'])) {
            $row['hostsFilter']=$params['hostsFilter'];
            if (is_array($params['hostsFilter'])) {
               $extraCondition .= " AND `$table`.`hostname` IN ('" . implode("','",$params['hostsFilter']) . "')";
            } else {
               $extraCondition .= " AND `$table`.`hostname` = '" . $params['hostsFilter'] . "'";
            }
         }
         if ($debug) {
            $debugFields['whereHosts'] = $extraCondition;
         }

         // Services filter
         if (isset($params['servicesFilter']) && ! empty($params['servicesFilter'])) {
            $row['servicesFilter']=$params['servicesFilter'];
            if (is_array($params['servicesFilter'])) {
               $extraCondition .= " AND `$table`.`service` IN ('" . implode("','",$params['servicesFilter']) . "')";
            } else {
               $extraCondition .= " AND `$table`.`service` = '" . $params['servicesFilter'] . "'";
            }
         }
         if ($debug) {
            $debugFields['whereServices'] = $extraCondition;
         }

         // Group by
         if ( isset($params['groupBy']) && ($params['groupBy']) ) {
            $groupBy = $params['groupBy'];
         }
         if ($debug) {
            $debugFields['groupBy'] = $groupBy;
         }

         $distinct = -1;
         if ( isset($params['distinct']) && ($params['distinct']) ) {
            $distinct = $params['distinct'];
         }
         if ($debug) $debugFields['distinct'] = $distinct;

         // DB connection
         $db = new DB();
         $sql_details = array(
               'host' => $db->dbhost,
               'db'   => $db->dbdefault,
               'user' => $db->dbuser,
               'pass' => $db->dbpassword
               );
         // Get SSP library for DataTables
         require_once (GLPI_ROOT . "/plugins/kiosks/lib/ssp.php");

         $return = SSP::simple( $params, $sql_details, $table, $primaryKey, $columns, $joinQuery, $extraCondition, $groupBy, $distinct );

         // if ($debug and is_array($return)) $return["debugWS"][] = $debugFields;

         return $return;
   }

   /*
    * WS getHosts :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - count :
    *   Renvoie un compteur du nombre de bornes
    *   Défaut: false
    *
    * - limit : nombre d'enregistrements demandés
    *   exemple: 1000
    *   Défaut: limite définie dans la personnalisation du compte utilisateur et/ou dans la configuration générale
    *
    * - start : numéro du premier enregistrement demandé
    *   exemple: 0
    *   Défaut: 0
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    * - locationsFilter : filtre sur une liste de lieux
    *   Filtre les résultats pour la liste des lieux demandée.
    *   exemple: 1470 ou 1470,1471
    *   Défaut: pas de filtre
    *
    * - hostsFilter : filtre sur une liste d'ordinateurs
    *   Filtre les résultats pour la liste d'ordinateurs demandée.
    *   exemple: ek3000-0001 ou ek3000-0001,ek3000-0002
    *   Défaut: pas de filtre
    *
    * - hostsStateFilter : filtre sur l'état des bornes
    *   Filtre les résultats pour sortir la liste des ordinateurs dans l'état demandé.
    *   exemple: ['DOWN', 'UP', 'UNREACHABLE']
    *   Défaut: pas de filtre
    *
    * - servicesFilter : filtre sur une liste de services
    *   Filtre les services associés a un host. Ce paramètre est * pour la liste de tous les services supervisés.
    *   La liste des services d'un ordinateur n'est pas renvoyée si ce paramètre est absent ou si l'host n'est pas supervisé (pas de champ state)
    *   exemple: CPU ou CPU,Disque
    *   Défaut: pas de filtre
    *
    * - servicesStateFilter : filtre sur l'état des services
    *   Filtre les résultats pour sortir la liste des services dans l'état demandé.
    *   exemple: ['WARNING', 'CRITICAL']
    *   Défaut: pas de filtre
    *
    * WS output :
    * - count=true
         [ {"kiosks_counter":"3"} ]
    *
    * - count=false
         [
         {"id":"570","name":"cham-0003","serial":"","inventory":"","comment":"","entity_id":"98","entity_name":"Argenti\u00e8re","entity_completename":"Entit\u00e9 racine > eLiberty > Chamonix > Argenti\u00e8re","model":null,"type":null,"short_location":"Argenti\u00e8re","location":"Argenti\u00e8re","monitoring_id":null,"state":null,"state_type":null,"event":null,"last_check":null,"perf_data":null,"is_acknowledged":null,"is_acknowledgeconfirmed":null,"acknowledge_comment":null,"lat":"45.978751","lng":" 6.926532"}
         ,
         {"id":"566","name":"cham-0002","serial":null,"inventory":null,"comment":null,"entity_id":"97","entity_name":"Office du tourisme de Chamonix","entity_completename":"Entit\u00e9 racine > eLiberty > Chamonix > Office du tourisme de Chamonix","model":"VM-Kiosk","type":"VM-Kiosk-Fred-2","short_location":"Hall gauche 2","location":"Odt-Chamonix > Hall gauche 2","monitoring_id":"590","state":"DOWN","state_type":"HARD","event":"CRITICAL: Host is not alive \n ","last_check":"2014-11-23 07:00:11","perf_data":"","is_acknowledged":"0","is_acknowledgeconfirmed":"0","acknowledge_comment":null,"lat":"45.923566","lng":" 6.868300",
         "services":[
            {"host_name":"cham-0002","name":"Borne","description":"nsca_kiosk","state":"WARNING","state_type":"HARD","event":null,"last_check":null,"is_acknowledged":"0","acknowledge_comment":null}
            ,
            {"host_name":"cham-0002","name":"CPU","description":"nsca_cpu","state":"WARNING","state_type":"HARD","event":null,"last_check":null,"is_acknowledged":"0","acknowledge_comment":null}
            ,
            ...
         ]
         ,
         ...
         }
         ]
    */
   static function methodGetHosts($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getHosts";
      }

      $count = false;
      if (isset($params['count'])) {
         $count=true;
      }

      $where = $join = $fields = '';

      // Start / limit
      $start = 0;
      $limit = $CFG_GLPI["list_limit_max"];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      $row['limit']=$limit;
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      $row['start']=$start;

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_computers", '', $params['entitiesList']) .
                           $where;
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_computers").$where;
      }

      // Hosts filter
      if (isset($params['hostsFilter'])) {
         $row['hostsFilter']=$params['hostsFilter'];
         if (is_array($params['hostsFilter'])) {
            if (count($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_computers`.`name` IN ('" . implode("','",$params['hostsFilter']) . "')";
            }
         } else {
            if (! empty($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_computers`.`name` = '" . $params['hostsFilter'] . "'";
            }
         }
      }

      // Services filter
      if (isset($params['servicesFilter'])) {
         $row['servicesFilter']=$params['servicesFilter'];

         if (! isset($params['servicesStateFilter'])) {
            $params['servicesStateFilter'] = '*';
            $row['servicesStateFilter']=$params['servicesStateFilter'];
         }
      }

      // Locations filter
      if (isset($params['locationsFilter'])) {
         $row['locationsFilter']=$params['locationsFilter'];
         if (is_array($params['locationsFilter'])) {
            $where .= " AND `glpi_locations`.`id` IN ('" . implode("','",$params['locationsFilter']) . "')";
         } else {
            $where .= " AND `glpi_locations`.`id` = " . $params['locationsFilter'];
         }
      }

      // States filter
      if (! isset($params['hostsStateFilter'])) {
         $params['hostsStateFilter'] = array ('DOWN','PENDING','UNKNOWN','UNREACHABLE','UP');
      }
      if (isset($params['hostsStateFilter'])) {
         $row['hostsStateFilter']=$params['hostsStateFilter'];
         if (is_array($params['hostsStateFilter'])) {
            $where .= " AND `glpi_plugin_monitoring_hosts`.`state` IN ('" . implode("','",$params['hostsStateFilter']) . "')";
         } else {
            $where .= " AND `glpi_plugin_monitoring_hosts`.`state` = '" . $params['hostsStateFilter'] . "'";
         }
      }

      // Order
      $order = "entity_name ASC, location ASC, name ASC, FIELD(`glpi_plugin_monitoring_hosts`.`state`,'DOWN','PENDING','UNKNOWN','UNREACHABLE','UP')";
      if (isset($params['order'])) {
         $order = $params['order'];
      }
      $row['order']=$order;

      $join .= "
          LEFT JOIN `glpi_plugin_monitoring_hosts`
               ON `glpi_plugin_monitoring_hosts`.`items_id` = `glpi_computers`.`id` AND `glpi_plugin_monitoring_hosts`.`itemtype`='Computer'
          LEFT JOIN `glpi_entities`
               ON `glpi_computers`.`entities_id` = `glpi_entities`.`id`
          LEFT JOIN `glpi_locations`
               ON `glpi_locations`.`id` = `glpi_computers`.`locations_id`
          LEFT JOIN `glpi_computertypes`
               ON `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`
          LEFT JOIN `glpi_computermodels`
               ON `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`
          ";

      if (! $count) {
          $query = "
               SELECT
                   `glpi_computers`.`id` AS id,
                   `glpi_computers`.`name` AS name,
                   `glpi_computers`.`serial` AS serial,
                   `glpi_computers`.`otherserial` AS inventory,
                   `glpi_computers`.`comment` AS comment,
                   `glpi_entities`.`id` AS entity_id,
                   `glpi_entities`.`name` AS entity_name,
                   `glpi_computermodels`.`name` AS model,
                   `glpi_computertypes`.`name` AS type,
                   `glpi_locations`.`building` AS gps,
                   `glpi_locations`.`name` AS short_location,
                   `glpi_locations`.`completename` AS location,
                   `glpi_plugin_monitoring_hosts`.`id` as pm_host_id,
                   `glpi_plugin_monitoring_hosts`.`state` AS `state`,
                   `glpi_plugin_monitoring_hosts`.`state_type`,
                   `glpi_plugin_monitoring_hosts`.`event`,
                   `glpi_plugin_monitoring_hosts`.`last_check`,
                   `glpi_plugin_monitoring_hosts`.`perf_data`,
                   `glpi_plugin_monitoring_hosts`.`is_acknowledged`,
                   `glpi_plugin_monitoring_hosts`.`is_acknowledgeconfirmed`,
                   `glpi_plugin_monitoring_hosts`.`acknowledge_comment`
               FROM `glpi_computers`
               $join
               $where
               ORDER BY $order
               LIMIT $start,$limit;
          ";
      } else {
         $query = "
                  SELECT
                      COUNT(`glpi_computers`.`id`) AS kiosks_counter
                  FROM `glpi_computers`
                  $join
                  $where
                  ;
             ";
      }
      $row['query'] = $query;
      if ($debug) {
         $rows[] = $row;
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $row = array();
         foreach ($data as $key=>$value) {
            if (is_string($key)) {
               $row[$key] = trim($value);
            }
         }
         if ($count) {
            $rows[] = $row;
            continue;
         }

         // Default GPS coordinates ...
         $row['lat'] = '45.054485';
         $row['lng'] = '5.081413';
         if (! empty($row['gps'])) {
            $split = explode(',', $row['gps']);
            if (count($split) > 1) {
               // At least 2 elements, let us consider as GPS coordinates ...
               $row['lat'] = $split[0];
               $row['lng'] = $split[1];
            }
            unset ($row['gps']);
         }

         // If host is monitored ...
         if (! empty($data['state']) && isset($params['servicesFilter'])) {
            // Fetch host services
            $services = self::methodGetServices(
                  array(
                     'debug'               => isset($params['debug']) ? true : null,
                     'start'               => 0,
                     'limit'               => 100,
                     'entitiesList'        => isset($params['entitiesList']) ? $params['entitiesList'] : null,
                     'hostsFilter'         => isset($row['name']) ? $row['name'] : null,
                     'servicesFilter'      => $params['servicesFilter'] == '*' ? '' : $params['servicesFilter'],
                     'servicesStateFilter' => $params['servicesStateFilter'] == '*' ? null : $params['servicesStateFilter']
               )
               ,
               $protocol
            );
            if (isset($services['host_name'])) {
               unset($services['host_name']);
            }
            $row['services'] = $services;
         }
         $rows[] = $row;
      }
      return $rows;
   }

   /*
    * WS getServices :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - count :
    *   Renvoie un compteur du nombre de services
    *   Défaut: false
    *
    * - limit : nombre d'enregistrements demandés
    *   exemple: 1000
    *   Défaut: limite définie dans la personnalisation du compte utilisateur et/ou dans la configuration générale
    *
    * - start : numéro du premier enregistrement demandé
    *   exemple: 0
    *   Défaut: 0
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    * - locationsFilter : filtre sur une liste de lieux
    *   Filtre les résultats pour la liste des lieux demandée.
    *   exemple: 1470 ou 1470,1471
    *   Défaut: pas de filtre
    *
    * - hostsFilter : filtre sur une liste d'ordinateurs
    *   Filtre les résultats pour la liste d'ordinateurs demandée.
    *   exemple: ek3000-0001 ou ek3000-0001,ek3000-0002
    *   Défaut: pas de filtre
    *
    * - servicesFilter : filtre sur une liste de services
    *   Filtre les services associés a un host. Ce paramètre est * pour la liste de tous les services supervisés.
    *   La liste des services d'un ordinateur n'est pas renvoyée si ce paramètre est absent ou si l'host n'est pas supervisé (pas de champ state)
    *   exemple: CPU ou CPU,Disque
    *   Défaut: pas de filtre
    *
    * - servicesStateFilter : filtre sur l'état des services
    *   Filtre les résultats pour sortir la liste des services dans l'état demandé.
    *   exemple: ['WARNING', 'CRITICAL']
    *   Défaut: pas de filtre
    *
    * WS output :
    * - count=true
         [ {"services_counter":"3"} ]
    *
    * - count=false
         [
         ]
    */
   static function methodGetServices($params, $protocol) {
      global $DB, $CFG_GLPI;
      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getServices";
      }

      $count = false;
      if (isset($params['count'])) {
         $count=true;
      }

      $where = $join = $fields = '';

      // Start / limit
      $start = 0;
      $limit = $CFG_GLPI["list_limit_max"];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      $row['limit']=$limit;
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      $row['start']=$start;

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_computers", '', $params['entitiesList']) .
                           $where;
         } else {
            $where = getEntitiesRestrictRequest("WHERE", "glpi_computers") .
                           $where;
         }

         // Hosts filter
         if (isset($params['hostsFilter'])) {
            $row['hostsFilter']=$params['hostsFilter'];
            if (is_array($params['hostsFilter'])) {
               if (count($params['hostsFilter']) != 0) {
                  $where .= " AND `glpi_computers`.`name` IN ('" . implode("','",$params['hostsFilter']) . "')";
               }
            } else {
               if (! empty($params['hostsFilter']) != 0) {
                  $where .= " AND `glpi_computers`.`name` = '" . $params['hostsFilter'] . "'";
               }
            }
         }

         // Services filter
         if (isset($params['servicesFilter'])) {
            $row['servicesFilter']=$params['servicesFilter'];
            if (is_array($params['servicesFilter'])) {
               if (count($params['servicesFilter']) != 0) {
                  $where .= " AND (`glpi_plugin_monitoring_components`.`name` IN ('" . implode("','",$params['servicesFilter']) . "')" . " OR `glpi_plugin_monitoring_components`.`description` IN ('" . implode("','",$params['servicesFilter']) . "') )";
               }
            } else {
               if (! empty($params['servicesFilter']) != 0) {
                  $where .= " AND (`glpi_plugin_monitoring_components`.`name` = '" . $params['servicesFilter'] . "' OR `glpi_plugin_monitoring_components`.`description` = '" . $params['servicesFilter'] . "')";
               }
            }
         }

         // Locations filter
         if (isset($params['locationsFilter'])) {
            $row['locationsFilter']=$params['locationsFilter'];
            if (is_array($params['locationsFilter'])) {
               $where .= " AND `glpi_locations`.`id` IN ('" . implode("','",$params['locationsFilter']) . "')";
            } else {
               $where .= " AND `glpi_locations`.`id` = " . $params['locationsFilter'];
            }
         }

         // States filter
         if (isset($params['servicesStateFilter'])) {
            $row['servicesStateFilter']=$params['servicesStateFilter'];
            if (is_array($params['servicesStateFilter'])) {
               $where .= " AND `glpi_plugin_monitoring_services`.`state` IN ('" . implode("','",$params['servicesStateFilter']) . "')";
            } else {
               $where .= " AND `glpi_plugin_monitoring_services`.`state` = '" . $params['servicesStateFilter'] . "'";
            }
         }

         // Order
         $order = "host_name ASC, name ASC, FIELD(`glpi_plugin_monitoring_services`.`state`,'CRITICAL','PENDING','UNKNOWN','WARNING','OK')";
         if (isset($params['order'])) {
            $order = $params['order'];
         }
         $row['order']=$order;

         $join .= "
             INNER JOIN `glpi_plugin_monitoring_services`
                  ON (`glpi_plugin_monitoring_services`.`plugin_monitoring_componentscatalogs_hosts_id` = `glpi_plugin_monitoring_componentscatalogs_hosts`.`id`)
             INNER JOIN `glpi_plugin_monitoring_hosts`
                  ON `glpi_plugin_monitoring_componentscatalogs_hosts`.`items_id` = `glpi_plugin_monitoring_hosts`.`items_id` AND `glpi_plugin_monitoring_componentscatalogs_hosts`.`itemtype` = `glpi_plugin_monitoring_hosts`.`itemtype`
             INNER JOIN `glpi_plugin_monitoring_componentscatalogs`
                  ON `plugin_monitoring_componentscalalog_id` = `glpi_plugin_monitoring_componentscatalogs`.`id`
             INNER JOIN `glpi_plugin_monitoring_components`
                  ON (`glpi_plugin_monitoring_services`.`plugin_monitoring_components_id` = `glpi_plugin_monitoring_components`.`id`)
             LEFT JOIN `glpi_computers`
                  ON `glpi_plugin_monitoring_componentscatalogs_hosts`.`items_id` = `glpi_computers`.`id` AND `glpi_plugin_monitoring_componentscatalogs_hosts`.`itemtype`='Computer'
             ";

         if (! $count) {
            $query = "
                 SELECT
                    `glpi_computers`.`name` AS host_name,
                    `glpi_plugin_monitoring_components`.`name`,
                    `glpi_plugin_monitoring_components`.`description`,
                    `glpi_plugin_monitoring_services`.`id` as pm_service_id,
                    `glpi_plugin_monitoring_services`.`state`,
                    `glpi_plugin_monitoring_services`.`state_type`,
                    `glpi_plugin_monitoring_services`.`event`,
                    `glpi_plugin_monitoring_services`.`last_check`,
                    `glpi_plugin_monitoring_services`.`is_acknowledged`,
                    `glpi_plugin_monitoring_services`.`acknowledge_comment`
                 FROM `glpi_plugin_monitoring_componentscatalogs_hosts`
                 $join
                 $where
                 ORDER BY $order
                 LIMIT $start,$limit;
            ";
         } else {
            $query = "
                 SELECT
                     COUNT(`glpi_plugin_monitoring_components`.`name`) AS services_counter
                 FROM `glpi_plugin_monitoring_componentscatalogs_hosts`
                 $join
                 $where
                 ;
            ";
         }
         $row['query'] = $query;
         if ($debug) {
            $rows[] = $row;
         }
         // print_r($row);

         $result = $DB->query($query);
         while ($data=$DB->fetch_array($result)) {
            $row = array();
            foreach ($data as $key=>$value) {
               if (is_string($key)) {
                  $row[$key] = trim($value);
               }
            }
            if ($count) {
               $rows[] = $row;
               continue;
            }

            $rows[] = $row;
         }
         return $rows;
    }

   /*
    * WS getLocations :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - count :
    *   Renvoie un compteur du nombre de sites
    *   Défaut: false
    *
    * - limit : nombre d'enregistrements demandés
    *   exemple: 1000
    *   Défaut: limite définie dans la personnalisation du compte utilisateur et/ou dans la configuration générale
    *
    * - start : numéro du premier enregistrement demandé
    *   exemple: 0
    *   Défaut: 0
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    * WS output :
         [
         {"entity_id":"96","entity_name":"Chamonix","entity_completename":"Entit\u00e9 racine > eLiberty > Chamonix","id":"1470","short_location":"Argenti\u00e8re","location":"Argenti\u00e8re","comment":"","level":"1","room":"","lat":"45.978751","lng":" 6.926532"}
         ,
         {"entity_id":"96","entity_name":"Chamonix","entity_completename":"Entit\u00e9 racine > eLiberty > Chamonix","id":"1471","short_location":"Odt-Chamonix","location":"Odt-Chamonix","comment":"","level":"1","gps":"","room":"","lat":"45.054485","lng":"5.081413"}
         ,
         {"entity_id":"97","entity_name":"Office du tourisme de Chamonix","entity_completename":"Entit\u00e9 racine > eLiberty > Chamonix > Office du tourisme de Chamonix","id":"1472","short_location":"Hall gauche 1","location":"Odt-Chamonix > Hall gauche 1","comment":"","level":"2","room":"","lat":"45.923576","lng":" 6.868310"}
         ,
         ...
         ]
    */
   static function methodGetLocations($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getLocations";
      }

      $count = false;
      if (isset($params['count'])) {
         $count=true;
      }

      $where = $join = $fields = '';

      // Start / limit
      $start = 0;
      $limit = $CFG_GLPI["list_limit_max"];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      $row['limit']=$limit;
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      $row['start']=$start;

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_locations", '', $params['entitiesList']) .
                           $where;
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_locations") .
                           $where;
      }

      // Order
      $order = "entity_name ASC, location ASC";
      if (isset($params['order'])) {
         $order = $params['order'];
      }
      $row['order']=$order;

      $join .= "
         LEFT JOIN `glpi_entities`
              ON `glpi_locations`.`entities_id` = `glpi_entities`.`id`
         ";

      if (! $count) {
         $query = "
              SELECT
                  `glpi_entities`.`id` AS entity_id,
                  `glpi_entities`.`name` AS entity_name,
                  `glpi_locations`.`id` AS id,
                  `glpi_locations`.`name` AS short_location,
                  `glpi_locations`.`completename` AS location,
                  `glpi_locations`.`comment` AS comment,
                  `glpi_locations`.`level` AS level,
                  `glpi_locations`.`building` AS gps,
                  `glpi_locations`.`room` AS room
              FROM `glpi_locations`
              $join
              $where
              ORDER BY $order
              LIMIT $start,$limit;
         ";
      } else {
         $query = "
              SELECT
                  COUNT(`glpi_locations`.`id`) AS locations_counter
              FROM `glpi_locations`
              $where
              ;
         ";
      }
      $row['query'] = $query;
      if ($debug) {
         $rows[] = $row;
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $row = array();
         foreach ($data as $key=>$value) {
            if (is_string($key)) {
               $row[$key] = $value;
            }
         }
         if ($count) {
            $rows[] = $row;
            continue;
         }

         // Default GPS coordinates ...
         $row['lat'] = '45.054485';
         $row['lng'] = '5.081413';
         if (! empty($row['gps'])) {
            $split = explode(',', $row['gps']);
            if (count($split) > 1) {
               // At least 2 elements, let us consider as GPS coordinates ...
               $row['lat'] = $split[0];
               $row['lng'] = $split[1];
            }
            unset ($row['gps']);
         }
         $rows[] = $row;
      }
      return $rows;
   }

   /*
    * WS getCounters:
    *
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = debug pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - counter : identifiant du compteur
    *   Défaut: obligatoire
    *
    * - hostname : identifiant du compteur
    *   Défaut: obligatoire
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé
    *   doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    */
   static function methodGetCounters($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getCountersNew";
      }

      $where = $join = $groupBy = '';

      $groupBy = "GROUP BY `day`,`counter_name` ";

      // Per entity ...
      if (isset($params['perEntities'])) {
         $groupBy = "GROUP BY `entityId`, `counter_name`";
      }

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_daily", '', $params['entitiesList']);
         $whereAll = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_all", '', $params['entitiesList']);
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_daily");
         $whereAll = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_all");
      }
      $whereEntities = $where;
      $row['whereEntities']=$whereEntities;

      // Time period ...
      // P1D, P1M, P2M, P1Y, ...
      $period = 'P0D';
      if (isset($params['period'])) {
         $period = $params['period'];
      }
      $row['period']=$period;

      // Start and end date
      $end_date = new DateTime();
      $end_date->setTime(0, 0, 0);
      $end_date = $end_date->format('Y-m-d');
      if (isset($params['end_date'])) {
         $end_date = $params['end_date'];
      }
      $eDate = new DateTime($end_date);
      $row['end_date']=$end_date;

      // ... from past.
      $start_date = new DateTime();
      $start_date->sub(new DateInterval($period));
      $start_date->setTime(0, 0, 0);
      $start_date = $start_date->format('Y-m-d');
      if (isset($params['start_date'])) {
         $start_date = $params['start_date'];
      }
      $sDate = new DateTime($start_date);
      $row['start_date']=$start_date;

      $interval = $eDate->diff($sDate);
      $row['countDays']=$interval->days;

      if ($start_date == $end_date) {
         $whereDay = " `day` = '$start_date'";
      } else {
         $whereDay = " `day` BETWEEN '$start_date' AND '$end_date'";
      }
      $where = " AND " . $whereDay;
      $row['whereDay']=$whereDay;

      // Counters filter
      if (isset($params['counters'])) {
         $row['counters']=$params['counters'];
         if (! is_array($params['counters'])) {
            $params['counters'] = array ($params['counters']);
         }
         if (count($params['counters']) != 0) {
            $where .= " AND `glpi_plugin_kiosks_counters`.`counter_name` IN ('" . implode("','",$params['counters']) . "')";
            $whereAll .= " AND `glpi_plugin_kiosks_counters`.`counter_name` IN ('" . implode("','",$params['counters']) . "')";
         } else {
            return (array( 'error' => "Missing counters parameter!" ));
         }
         $where .= " AND `glpi_plugin_kiosks_counters`.`is_active` = '1'";
         $whereAll .= " AND `glpi_plugin_kiosks_counters`.`is_active` = '1'";
         $row['whereCounters']=$where;
         $row['whereAll']=$where;
      } else {
         return (array( 'error' => "Missing counters parameter!" ));
      }

      // Hosts filter
      $whereKiosk = " `kiosk_name` LIKE '%'";
      if (isset($params['hostsFilter'])) {
         $row['hostsFilter']=$params['hostsFilter'];
         if (is_array($params['hostsFilter'])) {
            if (count($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_plugin_kiosks_hostcounters_daily`.`hostname` IN ('" . implode("','",$params['hostsFilter']) . "')";
               $whereAll .= " AND `glpi_plugin_kiosks_hostcounters_all`.`hostname` IN ('" . implode("','",$params['hostsFilter']) . "')";
               $whereKiosk = " `kiosk_name` IN ('" . implode("','",$params['hostsFilter']) . "')";
            }
         } else {
            if (! empty($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_plugin_kiosks_hostcounters_daily`.`hostname` = '" . $params['hostsFilter'] . "'";
               $whereAll .= " AND `glpi_plugin_kiosks_hostcounters_all`.`hostname` = '" . $params['hostsFilter'] . "'";
               $whereKiosk = " `kiosk_name` = '" . $params['hostsFilter'] . "'";
            }
         }
         $row['countHosts']=count($params['hostsFilter']);
         $row['whereHosts']=$where;
         $row['whereKiosk']=$whereKiosk;
      }

      // Prepare dates check array ...
      $countersDate = array();
      $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
      // Add one day ...
      $end_date->add(new DateInterval('P1D'));
      $start_date = new DateTime($start_date, new DateTimeZone('UTC'));
      $interval = new DateInterval('P1D');
      $daterange = new DatePeriod($start_date, $interval ,$end_date);
      foreach ($daterange as $date){
         $day = $date->format("Y-m-d");
         $ts = $date->format('U')."000";
         $countersDate[$ts] = array();
         foreach ($params['counters'] as $counter) {
            $countersDate[$ts][$counter] = false;
         }
      }

      // Get components tables for counters ...
      $query = "
         SELECT `mc`.`description` AS component,`kc`.`counter_name` AS counter,`kc`.`name` AS label,`kc`.`ratio` AS ratio
         FROM `glpi_plugin_kiosks_counters` AS kc
         JOIN `glpi_plugin_monitoring_components` AS mc ON `mc`.`id` = `kc`.`plugin_monitoring_components_id`
         WHERE `kc`.`is_active` = 1 AND `kc`.`counter_name` IN ('" . implode("','",$params['counters']) . "')";
      $row['queryComponents'] = $query;

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         // Get eternal value ...
         $query = "
            SELECT
              '". $data['counter'] ."' AS counter_id,
              SUM(`". $data['counter'] ."`) AS value
            FROM `glpi_plugin_kiosks_hdc_". $data['component'] ."`"
         ;
         $query .= " " . getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hdc_". $data['component']);
         $query .= " AND " . $whereKiosk;
         // $query .= " AND " . $whereDay;

         $row['query_' . $data['component'] . "_" . $data['counter'] . "_eternal"] = $query;
         $result2 = $DB->query($query);
         $row['query_' . $data['component'] . "_" . $data['counter'] . "_eternal_result"] = $result2;
         if ($result2) {
            while ($data2=$DB->fetch_array($result2)) {
               // Set eternal value for the counter ...
               $rows['countersSerie'][$data2['counter_id']]['name'] = $data2['counter_id'];
               $rows['countersSerie'][$data2['counter_id']]['eternal'] = floatval($data2['value'] * $data['ratio']);
               $rows['countersSerie'][$data2['counter_id']]['label'] = $data['label'];
            }
         }

         // Get daily values ...
         if (isset($params['perEntities'])) {
            $query = "
               SELECT
                  '". $data['counter'] ."' AS counter_id,
                  SUM(`". $data['counter'] ."`) AS value
               FROM `glpi_plugin_kiosks_hdc_". $data['component'] ."`"
            ;
            $query .= " " . getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hdc_". $data['component']);
            $query .= " AND " . $whereDay;
            $query .= " AND " . $whereKiosk;
            $query .= " GROUP BY `entities_id`, `counter_id`";
            $query .= " ORDER BY `day` ASC";
         } else {
            $query = "
               SELECT
                  '". $data['counter'] ."' AS counter_id,
                  1000*UNIX_TIMESTAMP(`day`) AS timestamp,
                  SUM(`". $data['counter'] ."`) AS value
               FROM `glpi_plugin_kiosks_hdc_". $data['component'] ."`"
            ;
            $query .= " " . getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hdc_". $data['component']);
            $query .= " AND " . $whereDay;
            $query .= " AND " . $whereKiosk;
            $query .= " GROUP BY `day`, `counter_id`";
            $query .= " ORDER BY `day` ASC";
         }

         $row['query_' . $data['component'] . "_" . $data['counter'] . "_daily"] = $query;
         $result2 = $DB->query($query);
         if ($result2) {
            while ($data2=$DB->fetch_array($result2)) {
               $row['query_' . $data['component'] . "_" . $data['counter'] . "_daily_result"] = $data2;
               if (! isset($data2['timestamp'])) {
                  continue;
               }

               if (isset($params['perEntities'])) {
                  // Group by entity,name ...
                  if (! array_key_exists('entities', $rows['countersSerie'][$data2['counter_id']])) {
                     $rows['countersSerie'][$data2['counter_id']]['entities'] = array();
                  }
                  if (! array_key_exists($data2['entities_id'], $rows['countersSerie'][$data2['counter_id']]['entities'])) {
                     $rows['countersSerie'][$data2['counter_id']]['entities'][ $data2['entities_id'] ] = array(
                        "id" => $data2['entities_id'],
                        "name" => $data2['entityName'],
                        "data" => array ()
                     );
                  }
                  $dp = array (intval($data2['timestamp']), floatval($data2['value'] * $data['ratio']));
                  $rows['countersSerie'][$data['counter_id']]['entities'][ $data2['entities_id'] ]['data'][] = $dp;
               } else {
                  // Group by day,name ...
                  $dp = array (intval($data2['timestamp']), floatval($data2['value'] * $data['ratio']));
                  if (! array_key_exists('data', $rows['countersSerie'][$data2['counter_id']])) {
                     $rows['countersSerie'][$data2['counter_id']]['data'] = array ($dp);
                  } else {
                     $rows['countersSerie'][$data2['counter_id']]['data'][] = $dp;
                  }
                  // $countersDate[ $data['timestamp'] ][ $data['counter_id'] ] = true;
               }
            }
         }
      }

      if ($debug) {
         $rows['debug'] = $row;
      }
      $daterange = new DatePeriod($start_date, $interval ,$end_date);
      foreach ($daterange as $date){
         $day = $date->format("Y-m-d");
         $ts = $date->format('U')."000";
         foreach ($params['counters'] as $counter) {
            if (! $countersDate[ $ts ][ $counter ]) {

               if (! array_key_exists($counter, $rows['countersSerie'])) {
                  $rows['countersSerie'][$counter] = array (
                     "name" => $counter,
                     "eternal" => isset($row[$counter."_all"]) ? $row[$counter."_all"] : 0
                  );
               }
               $dp = array (intval($ts), 0);
               if (! array_key_exists('data', $rows['countersSerie'][$counter])) {
                  $rows['countersSerie'][$counter]['data'] = array ($dp);
               } else {
                  $rows['countersSerie'][$counter]['data'][] = $dp;
               }
               $countersDate[ $ts ][ $counter ] = true;
            }
         }
      }

      return $rows;
   }

   /*
    * WS getCountersOld :
    *
    *
    *
    * Utilisation à confirmer !!!!!
    * -------------------------------------------------
    * On utilise plutôt getCounter (sans S)
    *
    *
    */
   static function methodGetCountersOld($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getCounters";
      }

      $where = $join = $groupBy = '';

      $groupBy = "GROUP BY `day`,`counter_name` ";

      // Per entity ...
      if (isset($params['perEntities'])) {
         $groupBy = "GROUP BY `entityId`, `counter_name`";
      }

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_daily", '', $params['entitiesList']) .
                           $where;
         $whereAll = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_all", '', $params['entitiesList']);
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_daily") .
                           $where;
         $whereAll = getEntitiesRestrictRequest("WHERE", "glpi_plugin_kiosks_hostcounters_all");
      }
      $whereEntities = $where;

      // Time period ...
      // P1D, P1M, P2M, P1Y, ...
      $period = 'P0D';
      if (isset($params['period'])) {
         $period = $params['period'];
      }
      $row['period']=$period;

      // Start and end date
      $end_date = new DateTime();
      $end_date->setTime(0, 0, 0);
      $end_date = $end_date->format('Y-m-d');
      if (isset($params['end_date'])) {
         $end_date = $params['end_date'];
      }
      $eDate = new DateTime($end_date);
      $row['end_date']=$end_date;

      // ... from past.
      $start_date = new DateTime();
      $start_date->sub(new DateInterval($period));
      $start_date->setTime(0, 0, 0);
      $start_date = $start_date->format('Y-m-d');
      if (isset($params['start_date'])) {
         $start_date = $params['start_date'];
      }
      $sDate = new DateTime($start_date);
      $row['start_date']=$start_date;

      $interval = $eDate->diff($sDate);
      $row['countDays']=$interval->days;

      if ($start_date == $end_date) {
         $where .= " AND `day` = '$start_date'";
      } else {
         $where .= " AND `day` BETWEEN '$start_date' AND '$end_date'";
      }

      // Counters filter
      if (isset($params['counters'])) {
         $row['counters']=$params['counters'];
         if (! is_array($params['counters'])) {
            $params['counters'] = array ($params['counters']);
         }
         if (count($params['counters']) != 0) {
            $where .= " AND `glpi_plugin_kiosks_counters`.`counter_name` IN ('" . implode("','",$params['counters']) . "')";
            $whereAll .= " AND `glpi_plugin_kiosks_counters`.`counter_name` IN ('" . implode("','",$params['counters']) . "')";
         }
         $where .= " AND `glpi_plugin_kiosks_counters`.`is_active` = '1'";
         $whereAll .= " AND `glpi_plugin_kiosks_counters`.`is_active` = '1'";
         $row['whereCounters']=$where;
      } else {
         $params['counters']=array();
      }

      // Hosts filter
      if (isset($params['hostsFilter'])) {
         $row['hostsFilter']=$params['hostsFilter'];
         if (is_array($params['hostsFilter'])) {
            if (count($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_plugin_kiosks_hostcounters_daily`.`hostname` IN ('" . implode("','",$params['hostsFilter']) . "')";
               $whereAll .= " AND `glpi_plugin_kiosks_hostcounters_all`.`hostname` IN ('" . implode("','",$params['hostsFilter']) . "')";
            }
         } else {
            if (! empty($params['hostsFilter']) != 0) {
               $where .= " AND `glpi_plugin_kiosks_hostcounters_daily`.`hostname` = '" . $params['hostsFilter'] . "'";
               $whereAll .= " AND `glpi_plugin_kiosks_hostcounters_all`.`hostname` = '" . $params['hostsFilter'] . "'";
            }
         }
         $row['countHosts']=count($params['hostsFilter']);
         $row['whereHosts']=$where;
      }

      $join .= "
         LEFT JOIN `glpi_plugin_kiosks_hostcounters_daily`
               ON (`glpi_plugin_kiosks_counters`.`id` = `glpi_plugin_kiosks_hostcounters_daily`.`plugin_kiosks_counters_id`)
         LEFT JOIN `glpi_entities`
               ON (`glpi_plugin_kiosks_hostcounters_daily`.`entities_id` = `glpi_entities`.`id`)
         ";

      // Prepare dates check array ...
      $countersDate = array();
      $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
      // Add one day ...
      $end_date->add(new DateInterval('P1D'));
      $start_date = new DateTime($start_date, new DateTimeZone('UTC'));
      $interval = new DateInterval('P1D');
      $daterange = new DatePeriod($start_date, $interval ,$end_date);
      foreach ($daterange as $date){
         $day = $date->format("Y-m-d");
         $ts = $date->format('U')."000";
         $countersDate[$ts] = array();
         foreach ($params['counters'] as $counter) {
            $countersDate[$ts][$counter] = false;
         }
      }

      // Get eternal value for counters ...
      $query = "SELECT
            `glpi_plugin_kiosks_counters`.`counter_name` AS counter_id
         , `glpi_plugin_kiosks_counters`.`name` AS label
         , `glpi_plugin_kiosks_counters`.`unit` AS unit
         , `glpi_plugin_kiosks_counters`.`ratio` AS ratio
         , `glpi_plugin_kiosks_counters`.`decimals` AS decimals
         , SUM(`glpi_plugin_kiosks_hostcounters_all`.`value`) * `glpi_plugin_kiosks_counters`.`ratio` AS value
         FROM `glpi_plugin_kiosks_counters`
         LEFT JOIN `glpi_plugin_kiosks_hostcounters_all`
            ON (`glpi_plugin_kiosks_counters`.`id` = `glpi_plugin_kiosks_hostcounters_all`.`plugin_kiosks_counters_id`)
         LEFT JOIN `glpi_entities`
            ON (`glpi_plugin_kiosks_hostcounters_all`.`entities_id` = `glpi_entities`.`id`)
         $whereAll
         GROUP BY `counter_name`
      ";
      $row['queryAll'] = $query;

      $rows['countersSerie'] = array();
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         if (isset($data['counter_id'])) {
            $row[$data['counter_id'].'_all']=$data['value'];

            $rows['countersSerie'][$data['counter_id']]['name'] = $data['counter_id'];
            $rows['countersSerie'][$data['counter_id']]['label'] = $data['label'];
            $rows['countersSerie'][$data['counter_id']]['eternal'] = isset($row[$data['counter_id'].'_all']) ? $row[$data['counter_id'].'_all'] : 0;
            // $rows['countersSerie'][$data['counter_id']]['hosts'] = isset($row['countHosts']) ? $row['countHosts'] : 0;
            if (isset($data['unit'])) {
               $rows['countersSerie'][$data['counter_id']]['unit'] = isset($data['unit']) ? $data['unit'] : '';
               $rows['countersSerie'][$data['counter_id']]['ratio'] = isset($data['ratio']) ? $data['ratio'] : 1;
               $rows['countersSerie'][$data['counter_id']]['color'] = isset($data['color']) ? $data['color'] : null;
               $rows['countersSerie'][$data['counter_id']]['decimals'] = isset($data['decimals']) ? $data['decimals'] : 0;
            }
         }
      }

      // print_r($rows['countersSerie']);

      if (isset($params['perEntities'])) {
         $query = "
            SELECT
               `glpi_entities`.`id` AS entityId
            , `glpi_entities`.`name` AS entityName
            , `glpi_plugin_kiosks_counters`.`counter_name` AS counter_id
            , `glpi_plugin_kiosks_counters`.`name` as label
            , `glpi_plugin_kiosks_counters`.`unit` as unit
            , `glpi_plugin_kiosks_counters`.`ratio` as ratio
            , `glpi_plugin_kiosks_counters`.`decimals` as decimals
            , 1000*UNIX_TIMESTAMP(`glpi_plugin_kiosks_hostcounters_daily`.`day`) AS timestamp
            , SUM(`glpi_plugin_kiosks_hostcounters_daily`.`value`) * `glpi_plugin_kiosks_counters`.`ratio` AS value
            FROM `glpi_plugin_kiosks_counters`
            $join $where
            $groupBy
            ORDER BY `glpi_plugin_kiosks_hostcounters_daily`.`day` ASC
         ";
      } else {
         $query = "
            SELECT
               `glpi_plugin_kiosks_counters`.`counter_name` AS counter_id
            , `glpi_plugin_kiosks_counters`.`name` as label
            , `glpi_plugin_kiosks_counters`.`unit` as unit
            , `glpi_plugin_kiosks_counters`.`ratio` as ratio
            , `glpi_plugin_kiosks_counters`.`decimals` as decimals
            , 1000*UNIX_TIMESTAMP(`glpi_plugin_kiosks_hostcounters_daily`.`day`) AS timestamp
            , SUM(`glpi_plugin_kiosks_hostcounters_daily`.`value`) * `glpi_plugin_kiosks_counters`.`ratio` AS value
            FROM `glpi_plugin_kiosks_counters`
            $join $where
            $groupBy
            ORDER BY `glpi_plugin_kiosks_hostcounters_daily`.`day` ASC
         ";
      }
      $row['query'] = $query;
      if ($debug) {
         $rows[] = $row;
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         if (! isset($data['timestamp'])) {
            continue;
         }
         // $rows['countersSerie'][$data['counter_id']]['name'] = $data['counter_id'];
         // $rows['countersSerie'][$data['counter_id']]['label'] = $data['label'];
         // $rows['countersSerie'][$data['counter_id']]['eternal'] = isset($row[$data['counter_id'].'_all']) ? $row[$data['counter_id'].'_all'] : 0;
         // if (isset($data['unit'])) {
            // $rows['countersSerie'][$data['counter_id']]['unit'] = isset($data['unit']) ? $data['unit'] : '';
            // $rows['countersSerie'][$data['counter_id']]['ratio'] = isset($data['ratio']) ? $data['ratio'] : 1;
            // $rows['countersSerie'][$data['counter_id']]['color'] = isset($data['color']) ? $data['color'] : null;
            // $rows['countersSerie'][$data['counter_id']]['decimals'] = isset($data['decimals']) ? $data['decimals'] : 0;
         // }

         if (isset($params['perEntities'])) {
            // print ("Per entity: ".$data['entityId']."\n");
            // Group by entity,name ...
            if (! array_key_exists('entities', $rows['countersSerie'][$data['counter_id']])) {
               $rows['countersSerie'][$data['counter_id']]['entities'] = array();
            }
            if (! array_key_exists($data['entityId'], $rows['countersSerie'][$data['counter_id']]['entities'])) {
               $rows['countersSerie'][$data['counter_id']]['entities'][ $data['entityId'] ] = array( "id" => $data['entityId'], "name" => $data['entityName'], "data" => array ());
            }
            $dp = array (intval($data['timestamp']), floatval($data['value']));
            $rows['countersSerie'][$data['counter_id']]['entities'][ $data['entityId'] ]['data'][] = $dp;

            // if (! array_key_exists('data', $rows['countersSerie'][$data['counter_id']]['entities'][ $data['entityId'] ])) {
               // $rows['countersSerie'][$data['counter_id']]['entities'][ $data['entityId'] ]['data'] = $dp;
            // } else {
               // $rows['countersSerie'][$data['counter_id']]['data'][] = $dp;
            // }
         } else {
            // print ("Per day ...\n");
            // Group by day,name ...
            $dp = array (intval($data['timestamp']), floatval($data['value']));
            if (! array_key_exists('data', $rows['countersSerie'][$data['counter_id']])) {
               $rows['countersSerie'][$data['counter_id']]['data'] = array ($dp);
            } else {
               $rows['countersSerie'][$data['counter_id']]['data'][] = $dp;
            }
            $countersDate[ $data['timestamp'] ][ $data['counter_id'] ] = true;
         }
      }

      $daterange = new DatePeriod($start_date, $interval ,$end_date);
      foreach ($daterange as $date){
         $day = $date->format("Y-m-d");
         $ts = $date->format('U')."000";
         foreach ($params['counters'] as $counter) {
            // if ($debug) echo "$day -> $ts: $counter: ";
            if (! $countersDate[ $ts ][ $counter ]) {
               // if ($debug) echo "does not exist\n";

               if (! array_key_exists($counter, $rows['countersSerie'])) {
                  $rows['countersSerie'][$counter] = array ( "name" => $counter, "ratio" => 1, "unit" => "", "color" => null, "decimals" => 0, "eternal" => isset($row[$counter."_all"]) ? $row[$counter."_all"] : 0 );
               }
               $dp = array (intval($ts), 0);
               if (! array_key_exists('data', $rows['countersSerie'][$counter])) {
                  // if ($debug) echo "$day -> $ts: $counter first\n";
                  $rows['countersSerie'][$counter]['data'] = array ($dp);
                  // $rows['countersSerie'][$counter]['data'][] = $dp;
               } else {
                  // if ($debug) echo "$day -> $ts: $counter next\n";
                  $rows['countersSerie'][$counter]['data'][] = $dp;
               }
               $countersDate[ $ts ][ $counter ] = true;
            }
            // if ($debug) echo "\n";
         }
      }
      return $rows;
   }

   /*
    * WS getHelpdeskConfiguration :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - entitiesList : entité ou liste d'entités
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    *
    * WS output :
         {
             "categories":[
                  {"entity_id":"0","entity_name":"Entit\u00e9 racine","entity_completename":"Entit\u00e9 racine","id":"29","name":"D\u00e9m\u00e9nagement","completename":"D\u00e9m\u00e9nagement","comment":"","parent_id":"0","is_incident":"0","id_template_incident":"0","is_request":"1","id_template_request":"1"}
                  ,
                  {"entity_id":"0","entity_name":"Entit\u00e9 racine","entity_completename":"Entit\u00e9 racine","id":"28","name":"D\u00e9pose","completename":"D\u00e9pose","comment":"","parent_id":"0","is_incident":"0","id_template_incident":"0","is_request":"1","id_template_request":"1"}
                  ,
                  {"entity_id":"0","entity_name":"Entit\u00e9 racine","entity_completename":"Entit\u00e9 racine","id":"30","name":"Livraison et mise en place","completename":"Livraison et mise en place","comment":"Demande de livraison et mise en place d'une borne","parent_id":"0","is_incident":"0","id_template_incident":"0","is_request":"1","id_template_request":"10"}
                  ,
                  ...
             ]
             ,
             "templates":[
                  {"id":"10","template_type":"request","_users_id_assign":"97","name":"Demande de livraison et mise en place","content":"Livraison et mise en place d'une borne","requesttypes_id":"1","slas_id":"3"}
                  ,
                  {"id":"2","template_type":"request","itilcategories_id":"1","requesttypes_id":"1","_users_id_assign":"39","slas_id":"3","name":"Mise en service d'une borne","content":"Mise en service d'une borne : \r\n- identit\u00e9 de la borne\r\n- configuration r\u00e9seau\r\n- v\u00e9rification de bon fonctionnement","type":"2"}
                  ,
                  {"id":"4","template_type":"request","name":"Rechargement papier","content":"Rechargement de papier sur la borne. Prise de rendez-vous avec le contact et v\u00e9rification de la disponibilit\u00e9 du papier en agence lors du phoning.","itilcategories_id":"18","slas_id":"5","itemtype":"Computer","type":"2","_users_id_assign":"39"}
                  ,
                  {"id":"3","template_type":"incident","type":"1","itemtype":"Computer","slas_id":"4","status":"1"}
             ]
         }
    */
   static function methodGetHelpdeskConfiguration($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }


      $rows = array();
      $row = array();
      $row['id']=-1;

      $debug = false;
      if (isset($params['debug'])) {
          $debug=true;
         $row['ws']="kiosks.getHelpdeskConfiguration";
      }

      $where = $join = $fields = '';

      // Entities
      if (isset($params['entitiesList'])) {
         $row['entitiesList']=$params['entitiesList'];
         if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            return (array( 'error' => "Access to all required entities is not allowed!" ));
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_itilcategories", '', $params['entitiesList'], true) .
                           $where;
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_itilcategories", '', '', true) .
                           $where;
      }

      // Order
      $order = "entity_name ASC, completename ASC";
      if (isset($params['order'])) {
         $order = $params['order'];
      }
      $row['order']=$order;

      $join .= "
         LEFT JOIN `glpi_entities`
              ON `glpi_itilcategories`.`entities_id` = `glpi_entities`.`id`
         ";

      $query = "
          SELECT
               `glpi_entities`.`id` AS entity_id,
               `glpi_entities`.`name` AS entity_name,
               `glpi_itilcategories`.`id` AS id,
               `glpi_itilcategories`.`name` AS name,
               `glpi_itilcategories`.`completename` AS completename,
               `glpi_itilcategories`.`comment` AS comment,
               `glpi_itilcategories`.`itilcategories_id` AS parent_id,
               `glpi_itilcategories`.`is_incident` AS is_incident,
               `glpi_itilcategories`.`tickettemplates_id_incident` AS id_template_incident,
               `glpi_itilcategories`.`is_request` AS is_request,
               `glpi_itilcategories`.`tickettemplates_id_demand` AS id_template_request
          FROM `glpi_itilcategories`
          $join
          $where
          ORDER BY $order;
      ";
      $row['query'] = $query;

      if ($debug) {
         $rows[] = $row;
      }

      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $row = array();
         foreach ($data as $key=>$value) {
            if (is_string($key)) {
               $row[$key] = $value;
            }
            if ($key =='id_template_request' and $value!='0') {
               $exists=false;
               foreach ($rows['templates'] as $index => $template) {
                  if ($template['id'] == $value) $exists=true;
               }

               if (! $exists) {
                  // Find ticket template if available ...
                  $rowTemplate = array();
                  $track = new Ticket();
                  // $tt = $track->getTicketTemplateToUse(0, Ticket::DEMAND_TYPE, $value, 0);
                  $tt = $track->getTicketTemplateToUse($value);

                  if (isset($tt->predefined) && count($tt->predefined)) {
                     $rowTemplate['id'] = $value;
                     $rowTemplate['template_type'] = 'request';
                     foreach ($tt->predefined as $predeffield => $predefvalue) {
                        $rowTemplate[$predeffield] = $predefvalue;
                     }
                     $rows['templates'][] = $rowTemplate;
                  }
               }
            }
            if ($key =='id_template_incident' and $value!='0') {
               $exists=false;
               foreach ($rows['templates'] as $index => $template) {
                  if ($template['id'] == $value) $exists=true;
               }

               if (! $exists) {
                  // Find ticket template if available ...
                  $rowTemplate = array();
                  $track = new Ticket();
                  // $tt = $track->getTicketTemplateToUse(0, Ticket::INCIDENT_TYPE, $value, 0);
                  $tt = $track->getTicketTemplateToUse($value);

                  if (isset($tt->predefined) && count($tt->predefined)) {
                     $rowTemplate['id'] = $value;
                     $rowTemplate['template_type'] = 'incident';
                     foreach ($tt->predefined as $predeffield => $predefvalue) {
                        $rowTemplate[$predeffield] = $predefvalue;
                     }
                     $rows['templates'][] = $rowTemplate;
                  }
               }
            }
         }

         $rows['categories'][] = $row;
      }
      return $rows;
   }

    /**
      * List the tickets for an authenticated user
      *
      * @param $params    array of options (author, group, category, status, startdate, enddate, itemtype)
      * @param $protocol        the communication protocol used
      *
      * @return array of hashtable
      *
      * With counters option, returns :
      {
         "general":{"tickets":{"value":3,"counter":"tickets"},"sla_ok":{"value":3,"counter":"SLA Ok"}},
         "status":{"status_2":{"value":2,"counter":"En cours (Attribu\u00e9)"},"status_5":{"value":1,"counter":"R\u00e9solu"}},
         "urgency":{"urgency_3":{"value":3,"counter":"Moyenne"}},
         "impact":{"impact_3":{"value":3,"counter":"Moyen"}},
         "itilcategories_id":{"itilcategories_id_25":{"value":2,"counter":"Bourrage imprimante"},"itilcategories_id_10":{"value":1,"counter":"Ticket d'incident > Imprimante"}},
         "requesttypes_id":{"requesttypes_id_1":{"value":3,"counter":"Helpdesk"}},
         "solutiontypes_id":{"solutiontypes_id_0":{"value":3,"counter":""}},
         "slas_id":{"slas_id_4":{"value":3,"counter":"Maintenance curative"}},
         "slalevels_id":{"slalevels_id_0":{"value":3,"counter":""}},
         "entities_id":{"entities_id_12":{"value":3,"counter":"Entit\u00e9 racine > CNAMTS > CPAM du VAL-D'OISE"}}
      }

    **/
   static function methodListTickets($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array('count'     => 'bool,optional',
                             'start'     => 'integer,optional',
                             'limit'     => 'integer,optional',
                             'user'      => 'integer,optional',
                             'recipient' => 'integer,optional',
                             'mine'      => 'bool,optional',
                             'group'     => 'integer,optional',
                             'mygroups'  => 'bool,optional',
                             'category'  => 'integer,optional',
                             'status'    => 'integer,optional',
                             'startdate' => 'datetime,optional',
                             'enddate'   => 'datetime,optional',
                             'itemtype'  => 'string,optional',
                             'item'      => 'integer,optional',
                             'entity'    => 'integer,optional',
                             'satisfaction'
                                               => 'integer,optional',
                             'approval'  => 'text,optional',
                             'approver'  => 'integer,optional',
                             'id2name'   => 'bool,optional',
                             'order'     => 'array,optional',
                             'counters'  => 'bool,optional',
                             'help'      => 'bool,optional');
      }

      if (!Session::getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $resp  = array();
      $start = 0;
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      $limit = $_SESSION['glpilist_limit'];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }

      $where = $join = '';

      // User (victim)
      if (isset($params['user'])) {
         if (!is_numeric($params['user']) || ($params['user'] < 0)) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'user');
         }
         if (Session::haveRight('show_all_ticket', '1')
               || Session::haveRight('show_group_ticket', '1')
               || ($params['user'] == Session::getLoginUserID())) {
            // restrict to author parameter
            $where = " AND `glpi_tickets_users_request`.`users_id` = '" . $params['user'] . "'";
         } else {
            return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
         }

      } else {
         if (Session::haveRight('show_all_ticket', '1')
               || Session::haveRight('show_group_ticket', '1')) {
            $where = ''; // Restrict will come from group (if needed)
         } else {
            // Only connected user's tickets'
            $where = " AND `glpi_tickets_users_request`.`users_id`
                                  = '" . Session::getLoginUserID() . "'";
         }
      }

      // Group
      if (isset($params['group'])) {
         if (!is_numeric($params['group']) || ($params['group'] < 0)) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'group');
         }

         if (Session::haveRight('show_all_ticket', '1')
               || (Session::haveRight('show_group_ticket', '1')
                     && in_array($params['group'], $_SESSION['glpigroups']))) {
            // restrict to group parameter
            $where = " AND `glpi_groups_tickets_request`.`groups_id` = '" . $params['group'] . "'";
         } else {
            return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
         }

      } else {
         if (Session::haveRight('show_group_ticket', '1')
               && !Session::haveRight('show_all_ticket', '1')) {

            // Connected user's group'
            if (count($_SESSION['glpigroups']) > 0) {
               $where = " AND `glpi_groups_tickets_request`.`groups_id`
                                      IN (" . implode(',', $_SESSION['glpigroups']) . ")";
            } else {
               $where = " AND `glpi_tickets_users_request`.`users_id`
                                      = '".Session::getLoginUserID()."'";
            }
         }
      }

      // Security
      if (empty($where) && !Session::haveRight('show_all_ticket', '1')) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '', 'profil');
      }

      // Recipient (person creating the ticket)
      if (isset($params['recipient'])) {
         if (!is_numeric($params['recipient']) || $params['recipient'] < 0) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'recipient');
         }
         // restrict to recipient parameter
         $where = " AND `users_id_recipient`='" . $params['recipient'] . "'";
      }

      // Mine (user or recipient for the ticket)
      if (isset($params['mine'])) {
         $where = " AND (`glpi_tickets_users_request`.`users_id` = '".Session::getLoginUserID()."'
                                 OR `users_id_recipient` = '" . Session::getLoginUserID() . "')";
      }

      // Mygroups
      if (isset($param['mygroups'])) {
         $where = " AND `glpi_groups_tickets`.`groups_id`
                                IN (" . implode(',', $_SESSION['glpigroups']) . ")";
      }

      // Entity
      if (isset($params['entity'])) {
         if (!Session::haveAccessToEntity($params['entity'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '', 'entity');
         }
         $where = getEntitiesRestrictRequest("WHERE", "glpi_tickets", '', $params['entity']) .
                           $where;
      } else {
         $where = getEntitiesRestrictRequest("WHERE", "glpi_tickets") .
                           $where;
      }

      // Category
      if (isset($params['category'])) {
         if (!is_numeric($params['category']) || ($params['category'] <= 0)) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'category');
         }
         $where .= " AND " . getRealQueryForTreeItem("glpi_itilcategories", $params['category'],
                                                                             "glpi_tickets.itilcategories_id");
      }

      if (isset($params['approval']) || isset($params['approver'])) {
         $join .= "INNER JOIN `glpi_ticketvalidations`
                                 ON (`glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id` ) ";

         if (isset($params['approver']) && is_numeric($params['approver'])) {
            $where .= " AND `glpi_ticketvalidations`.`users_id_validate`=".$params['approver'];
         }
         $tabstatus = TicketValidation::getAllStatusArray();
         if (isset($params['approval']) && isset($tabstatus[$params['approval']])) {
            $where .= " AND `glpi_ticketvalidations`.`status`='".$params['approval']."'";
         }
      }

      if (isset($params['satisfaction'])) {
         $join .= "INNER JOIN `glpi_ticketsatisfactions`
                                ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` ) ";
         switch ($params['satisfaction']) {

            case 1:
               $where .= " AND `glpi_ticketsatisfactions`.`date_answered` IS NULL";
               break;

            case 2:
               $where .= " AND `glpi_ticketsatisfactions`.`date_answered` IS NOT NULL";
               break;

            default:
               // survey exists (by Inner Join)
         }
         $params['status'] = Ticket::CLOSED;
      }

      // Status
      if (isset($params['status'])) {
         if (!in_array($params['status'], Ticket::getAllowedStatusArray(true))) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'status');
         }
         switch ($params['status']) {

            case 'all':
               // No restriction
               break;

            case 'notclosed' :
               $status = Ticket::getAllStatusArray();
               unset($status[CLOSED]);
               $where .= " AND `glpi_tickets`.`status` IN ('".implode("','",$status)."') ";
               break;

            case 'notold' :
               $status = Ticket::getAllStatusArray();
               unset($status[SOLVED], $status[CLOSED]);
               $where .= " AND `glpi_tickets`.`status` IN ('".implode("','",$status)."') ";
               break;

            case 'old' :
               $status = array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray());
               $where .= " AND `glpi_tickets`.`status` IN ('".implode("','",$status)."') ";
               break;

            case 'process' :
               $status = Ticket::getProcessStatusArray();
               $where .= " AND `glpi_tickets`.`status` IN ('".implode("','",$status)."') ";
               break;

            default :
               $where .= " AND `glpi_tickets`.`status` = '" . $params['status'] . "' ";
         }
      }

      // Dates
      if (isset($params["startdate"])) {
         if (preg_match(WEBSERVICES_REGEX_DATETIME, $params["startdate"])
               || preg_match(WEBSERVICES_REGEX_DATE, $params["startdate"])) {

            $where .= " AND `glpi_tickets`.`date` >= '" . $params['startdate'] . "' ";
         } else {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'date');
         }
      }

      if (isset($params["enddate"])) {
         if (preg_match(WEBSERVICES_REGEX_DATETIME, $params["enddate"])
               || preg_match(WEBSERVICES_REGEX_DATE, $params["enddate"])) {

            $where .= " AND `glpi_tickets`.`date` <= '" . $params['enddate'] . "' ";
         } else {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'date');
         }
      }

      if (isset($params['itemtype'])) {
         if (!empty($params['itemtype']) && !class_exists($params['itemtype'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'itemtype');
         }
         $where .= " AND `glpi_tickets`.`itemtype`='" . $params['itemtype'] . "'";
      }

      if (isset($params['item'])) {
         if (!isset($params['itemtype'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '','itemtype');
         }
         if (!is_numeric($params['item']) || $params['item'] <= 0) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'item');
         }
         $where .= " AND `glpi_tickets`.`items_id`='" . $params['item'] . "'";
      }

      $orders = array();
      if (isset($params['order'])) {
         if (is_array($params['order'])) {
            $tab = $params['order'];
         } else {
            $tab = array($params['order']=>'DESC');
         }
         foreach ($tab as $key => $val) {
            if ($val != 'ASC') {
               $val = 'DESC';
            }
            $sqlkey = array('id'           => '`glpi_tickets`.`id`',
                                    'date'         => '`glpi_tickets`.`date`',
                                    'closedate'    => '`glpi_tickets`.`closedate`',
                                    'date_mod'     => '`glpi_tickets`.`date_mod`',
                                    'status'       => '`glpi_tickets`.`status`',
                                    'entities_id'  => '`glpi_tickets`.`entities_id`',
                                    'priority'     => '`glpi_tickets`.`priority`');
            if (isset($sqlkey[$key])) {
               $orders[] = $sqlkey[$key]." $val";
            } else {
               return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '','order=$key');
            }
         }
      }

      if (count($orders)) {
         $order = implode(',',$orders);
      } else {
         $order = "`glpi_tickets`.`date_mod` DESC";
      }

      $resp = array ();
      if (isset($params['count'])) {
         $query = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS count
                        FROM `glpi_tickets`
                        $join
                        LEFT JOIN `glpi_tickets_users` AS glpi_tickets_users_request
                                ON (`glpi_tickets`.`id` = `glpi_tickets_users_request`.`tickets_id`
                                      AND `glpi_tickets_users_request`.`type` = 1)
                        LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_request
                                ON (`glpi_tickets`.`id` = `glpi_groups_tickets_request`.`tickets_id`
                                      AND `glpi_groups_tickets_request`.`type` = 1 )
                        $where";

         $resp = $DB->request($query)->next();
      } else {
         $query = "SELECT `glpi_tickets`.*,
                                   GROUP_CONCAT(DISTINCT `glpi_tickets_users_request`.`users_id` SEPARATOR ',')
                                             AS users_id_request,
                                   GROUP_CONCAT(DISTINCT `glpi_tickets_users_observer`.`users_id` SEPARATOR ',')
                                             AS users_id_observer,
                                   GROUP_CONCAT(DISTINCT `glpi_tickets_users_assign`.`users_id` SEPARATOR ',')
                                             AS users_id_assign,
                                   GROUP_CONCAT(DISTINCT `glpi_groups_tickets_request`.`groups_id` SEPARATOR ',')
                                             AS groups_id_request,
                                   GROUP_CONCAT(DISTINCT `glpi_groups_tickets_observer`.`groups_id` SEPARATOR ',')
                                             AS groups_id_observer,
                                   GROUP_CONCAT(DISTINCT `glpi_groups_tickets_assign`.`groups_id` SEPARATOR ',')
                                             AS groups_id_assign
                        FROM `glpi_tickets`
                        $join
                        LEFT JOIN `glpi_tickets_users` AS glpi_tickets_users_request
                                ON (`glpi_tickets`.`id` = `glpi_tickets_users_request`.`tickets_id`
                                      AND `glpi_tickets_users_request`.`type` = 1)
                        LEFT JOIN `glpi_tickets_users` AS glpi_tickets_users_assign
                                ON (`glpi_tickets`.`id` = `glpi_tickets_users_assign`.`tickets_id`
                                      AND `glpi_tickets_users_assign`.`type` = 2)
                        LEFT JOIN `glpi_tickets_users` AS glpi_tickets_users_observer
                                ON (`glpi_tickets`.`id` = `glpi_tickets_users_observer`.`tickets_id`
                                      AND `glpi_tickets_users_observer`.`type` = 3)
                        LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_request
                                ON (`glpi_tickets`.`id` = `glpi_groups_tickets_request`.`tickets_id`
                                      AND `glpi_groups_tickets_request`.`type` = 1)
                        LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_assign
                                ON (`glpi_tickets`.`id` = `glpi_groups_tickets_assign`.`tickets_id`
                                      AND `glpi_groups_tickets_assign`.`type` = 2)
                        LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_observer
                                ON (`glpi_tickets`.`id` = `glpi_groups_tickets_observer`.`tickets_id`
                                      AND `glpi_groups_tickets_observer`.`type` = 3)
                        $where
                        GROUP BY `glpi_tickets`.`id`
                        ORDER BY $order
                        LIMIT $start,$limit";

         // echo $query;
         if (isset($params['counters'])) {
            $counters = array();
            foreach ($DB->request($query) as $data) {
               // General counters
               $counter_type = 'general';
               if (isset($counters[$counter_type]['tickets'])) {
                  $counters[$counter_type]['tickets']['value'] ++;
               } else {
                  $counters[$counter_type]['tickets']['value'] = 1;
                  $counters[$counter_type]['tickets']['counter'] = 'tickets';
               }

               if (! empty($data['solvedate'])) {
                  if (isset($counters[$counter_type]['solved'])) {
                     $counters[$counter_type]['solved']['value'] ++;
                  } else {
                     $counters[$counter_type]['solved']['value'] = 1;
                     $counters[$counter_type]['solved']['counter'] = 'Solved';
                  }
                  if ($data['slas_id'] != '0') {
                     $due_date = new DateTime($data['due_date']);
                     $solved_date = new DateTime($data['solvedate']);
                     if ($solved_date <= $due_date) {
                        if (isset($counters[$counter_type]['sla_ok'])) {
                           $counters[$counter_type]['sla_ok']['value'] ++;
                        } else {
                           $counters[$counter_type]['sla_ok']['value'] = 1;
                           $counters[$counter_type]['sla_ok']['counter'] = 'SLA Ok';
                        }
                     } else {
                        if (isset($counters[$counter_type]['sla_ko'])) {
                           $counters[$counter_type]['sla_ko']['value'] ++;
                        } else {
                           $counters[$counter_type]['sla_ko']['value'] = 1;
                           $counters[$counter_type]['sla_ko']['counter'] = 'SLA Ko';
                        }
                     }
                  } else {
                     if (isset($counters[$counter_type]['sla_no'])) {
                        $counters[$counter_type]['sla_no']['value'] ++;
                     } else {
                        $counters[$counter_type]['sla_no']['value'] = 1;
                        $counters[$counter_type]['sla_no']['counter'] = 'No SLA';
                     }
                  }
               } else {
                  if (isset($counters[$counter_type]['unsolved'])) {
                     $counters[$counter_type]['unsolved']['value'] ++;
                  } else {
                     $counters[$counter_type]['unsolved']['value'] = 1;
                     $counters[$counter_type]['unsolved']['counter'] = 'Unsolved';
                  }
               }


               // Status counters
               $counter_type = 'status';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Ticket::getStatus($data['status']));
               }

               // Urgency counters
               $counter_type = 'urgency';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Ticket::getUrgencyName($data['urgency']);
               }

               // Impact counters
               $counter_type = 'impact';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Ticket::getImpactName($data['impact']);
               }

               // Category counters
               $counter_type = 'itilcategories_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_itilcategories', $data['itilcategories_id']));
               }

               // Request type counters
               $counter_type = 'requesttypes_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_requesttypes', $data['requesttypes_id']));
               }

               // Solution type counters
               $counter_type = 'solutiontypes_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes', $data['solutiontypes_id']));
               }

               // SLA counters
               $counter_type = 'slas_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_slas', $data['slas_id']));
               }

               // SLA level counters
               $counter_type = 'slalevels_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_slalevels', $data['slalevels_id']));
               }

               // Entities counters
               $counter_type = 'entities_id';
               if (isset($counters[$counter_type][$counter_type.'_'.$data[$counter_type]])) {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] ++;
               } else {
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['value'] = 1;
                  $counters[$counter_type][$counter_type.'_'.$data[$counter_type]]['counter'] = Html::clean(Dropdown::getDropdownName('glpi_entities', $data['entities_id']));
               }
            }
            $resp = $counters;
         } else {
            foreach ($DB->request($query) as $data) {
               $tmp                        = explode(',', $data['users_id_request']);
               $data['users']['requester'] = array();
               foreach($tmp as $id) {
                  $data['users']['requester'][]['id'] = $id;
               }

               $tmp                       = explode(',', $data['users_id_observer']);
               $data['users']['observer'] = array();
               foreach($tmp as $id) {
                  $data['users']['observer'][]['id'] = $id;
               }

               $tmp                     = explode(',', $data['users_id_assign']);
               $data['users']['assign'] = array();
               foreach($tmp as $id) {
                  $data['users']['assign'][]['id'] = $id;
               }

               $tmp                         = explode(',', $data['groups_id_request']);
               $data['groups']['requester'] = array();
               foreach($tmp as $id) {
                  $data['groups']['requester'][]['id'] = $id;
               }

               $tmp                        = explode(',', $data['groups_id_observer']);
               $data['groups']['observer'] = array();
               foreach($tmp as $id) {
                  $data['groups']['observer'][]['id'] = $id;
               }

               $tmp                      = explode(',', $data['groups_id_assign']);
               $data['groups']['assign'] = array();
               foreach($tmp as $id) {
                  $data['groups']['assign'][]['id'] = $id;
               }

               unset($data['groups_id_request'], $data['groups_id_observer'], $data['groups_id_assign'],
                        $data['users_id_request'], $data['users_id_observer'], $data['users_id_assign']);

               $data['solution']
                        = Html::clean(Toolbox::unclean_cross_side_scripting_deep($data['solution']));

               // TODO ...
               if (Session::haveRight('observe_ticket', '1')) {
                  // Followups
                  $query = "SELECT *
                                  FROM `glpi_ticketfollowups`
                                  WHERE `tickets_id` = '" . $data['id'] . "' ";

                  if (!Session::haveRight("show_full_ticket", "1")) {
                     $query .= " AND (`is_private`='0'
                                                 OR `users_id` ='" . Session::getLoginUserID() . "' ) ";
                  }
                  $query .= " ORDER BY `date` DESC";

                  foreach ($DB->request($query) as $data2) {
                     unset($data2['tickets_id']);

                     if (isset($params['id2name'])) {
                        $data2['users_name'] = Html::clean(getUserName($data2['users_id']));
                        $data2['requesttypes_name'] = Html::clean(Dropdown::getDropdownName('glpi_requesttypes', $data2['requesttypes_id']));
                     }
                     $data['followups'][] = $data2;
                  }

                  // Tasks
                  $query = "SELECT *
                                  FROM `glpi_tickettasks`
                                  WHERE `tickets_id` = '" . $data['id'] . "' ";

                  if (!Session::haveRight("show_full_ticket", "1")) {
                     $query .= " AND (`is_private`='0'
                                                 OR `users_id` ='" . Session::getLoginUserID() . "' ) ";
                  }
                  $query .= " ORDER BY `date` DESC";

                  foreach ($DB->request($query) as $data2) {
                     unset($data2['tickets_id']);

                     if (isset($params['id2name'])) {
                        $data2['users_name'] = Html::clean(getUserName($data2['users_id']));
                        $data2['taskcategories_name'] = Html::clean(Dropdown::getDropdownName('glpi_taskcategories', $data2['taskcategories_id']));
                     }
                     $data['tasks'][] = $data2;
                  }
               }

               if (isset($params['id2name'])) {
                  if ($data['itemtype'] && ($item = getItemForItemtype($data['itemtype']))) {
                     $data['itemtype_name']  = Html::clean($item->getTypeName());
                     if ($item->getFromDB($data['items_id'])) {
                        $data['items_name']  = Html::clean($item->getNameID());
                     } else {
                        $data['items_name']  = NOT_AVAILABLE;
                     }
                  }
                  foreach ($data['groups'] as $type => $tab) {
                     foreach ($tab as $key => $grp) {
                        $data['groups'][$type][$key]['name']
                                 =  Html::clean(Dropdown::getDropdownName('glpi_groups', $grp['id']));
                     }
                  }
                  foreach ($data['users'] as $type => $tab) {
                     foreach ($tab as $key => $usr) {
                        $data['users'][$type][$key]['name'] =  Html::clean(getUserName($usr['id']));
                     }
                  }

                  $data['status_name']
                           = Html::clean(Ticket::getStatus($data['status']));
                  $data['urgency_name']
                           = Ticket::getUrgencyName($data['urgency']);
                  $data['impact_name']
                           = Ticket::getImpactName($data['impact']);
                  $data['priority_name']
                           = Ticket::getPriorityName($data['priority']);
                  $data['users_name_recipient']
                           = Html::clean(getUserName($data['users_id_recipient']));
                  $data['entities_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_entities', $data['entities_id']));
                  /* Field does not exist ...
                  $data['suppliers_name_assign']
                           = Html::clean(Dropdown::getDropdownName('glpi_suppliers',
                                                                                       $data['suppliers_id_assign']));
                  */
                  $data['ticketcategories_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_itilcategories',
                                                                                       $data['itilcategories_id']));
                  $data['requesttypes_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                                                       $data['requesttypes_id']));
                  $data['solutiontypes_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                                       $data['solutiontypes_id']));
                  $data['slas_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_slas', $data['slas_id']));
                  $data['slalevels_name']
                           = Html::clean(Dropdown::getDropdownName('glpi_slalevels',
                                                                                       $data['slalevels_id']));
               }
               $resp[] = $data;
            }
         }
      }
      return $resp;
   }

   /*
    * WS createTicket :
    * - debug :
    *   renvoie les données fournies pour la création du ticket mais ne créé pas le ticket
    *
    *
    * - template : pour créer un ticket en utilisant les champs prédéfinis d'un gabarit
    *   type et category, définissent la catégorie pour trouver le gabarit
    *    type : Ticket::INCIDENT_TYPE (1), Ticket::DEMAND_TYPE (2)
    *   Les champs prédéfinis du gabarit définissent le contenu du ticket. LEs champs fournis par les paramètres viennent modifier ces champs par défaut ...
    *
    * - entity : entité du ticket (optionnel)
    *   Défaut: entité courante de l'utilisateur
    *
    * - content : description du ticket (obligatoire)
    *
    * - title : nom du ticket (optionnel)
    *   Défaut: début de la description
    *
    * - itemtype/item :
    *   L'objet concerné (Computer/54).
    *
    * TBC ....
    */
   static function methodCreateTicket($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
      }

      // Content : always mandatory
      if ((!isset($params['content']) || empty($params['content']))) {
         return ('{ "error": "Missing \'content\' parameter" }');
      }

      // Source of the ticket, dynamically created
      if (isset($params['source'])) {
         if (empty($params['source'])) {
            return ('{ "error": "Empty \'source\' parameter" }');
         }
         $source = Dropdown::importExternal('RequestType', $params['source']);
      } else {
         $source = Dropdown::importExternal('RequestType', 'Dashboard');
      }


      // ===== Build the Ticket =====
      // author is always the logged user
      $data = array(
         '_users_id_requester' => Session::getLoginUserID(), // Requester / Victime
         'users_id_recipient' => Session::getLoginUserID(), // Recorder
         'requesttypes_id' => $source,
         'status'     => Ticket::INCOMING,
         'content'    => addslashes(Toolbox::clean_cross_side_scripting_deep($params["content"])),
         'itemtype'   => '',
         'type'       => Ticket::INCIDENT_TYPE,
         'items_id'   => 0);

      if ($debug) {
         $data['params'] = $params;
      }

      // entity : optionnal, default = current one
      if (!isset($params['entity'])) {
         $data['entities_id'] = $_SESSION['glpiactive_entity'];
      } else {
         if (!is_numeric($params['entity'])) {
            return (array( 'error' => "Entity Id is not numeric!" ));
         }
         if (!in_array($params['entity'], $_SESSION['glpiactiveentities'])) {
            return (array( 'error' => "Entity Id is not in active entities: " . implode(', ', $_SESSION['glpiactiveentities']) ));
         }
         $data['entities_id'] = $params['entity'];
      }

      // Template : use template predefined fields
      if (isset($params['template']) && $params['template']) {
         // Type must be defined ...
         if (isset($params['type'])) {
            $types = Ticket::getTypes();
            if (!is_numeric($params['type']) || !isset($types[$params['type']])) {
               return (array( 'error' => "Type is not valid!" ));
            }
         } else {
            return (array( 'error' => "Type for template is not defined!" ));
         }
         // Category must be defined ...
         if (isset($params['category'])) {
            if (!is_numeric($params['category']) || ($params['category'] < 1)) {
               return (array( 'error' => "Category is not valid!" ));
            }
         } else {
            return (array( 'error' => "Category for template is not defined!" ));
         }

         // Find ticket template if available ...
         $track = new Ticket();
         $tt = $track->getTicketTemplateToUse(0, $params['type'], $params['category'], $data['entities_id']);

         if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {
               // Load template data
               $data[$predeffield] = $predefvalue;
            }
         }
      }

      // Title : optional (default = start of contents set by add method)
      if (isset($params['title'])) {
         $data['name'] = addslashes(Toolbox::clean_cross_side_scripting_deep($params['title']));
      }

      // user (author) : optionnal,  default = current one
      if (isset($params['user'])) {
         if (!is_numeric($params['user'])) {
            return (array( 'error' => "User Id is not numeric!" ));
         }
         $data['_users_id_requester'] = $params['user'];
      }

      // Email notification
      if (isset($params['user_email'])) {
         if (!NotificationMail::isUserAddressValid($params['user_email'])) {
            return (array( 'error' => "User email is not well formed!" ));
         }
         $data['_users_id_requester_notif']['alternative_email'] = $params['user_email'];
         $data['_users_id_requester_notif']['use_notification']  = 1;
      } else if (isset($params['use_email_notification']) && $params['use_email_notification']) {
         $data['_users_id_requester_notif']['use_notification']  = 1;
      } else if (isset($params['use_email_notification']) && !$params['use_email_notification']) {
         $data['_users_id_requester_notif']['use_notification']  = 0;
      }

      if (isset($params['requester'])) {
         if (is_array($params['requester'])) {
            foreach ($params['requester'] as $id) {
               if (is_numeric($id) && $id > 0) {
                  $data['_additional_requesters'][] = array(
                     'users_id'         => $id,
                     'use_notification' => true);
               } else {
                  return (array( 'error' => "Requester Id is not numeric!" ));
               }
            }
         } else if (is_numeric($params['requester']) && ($params['requester'] > 0)) {
            $data['_additional_requesters'][] = array(
               'users_id'         => $params['requester'],
               'use_notification' => true);
         } else {
            return (array( 'error' => "Requester is unknown!" ));
         }
      }

      if (isset($params['victim'])) {
         if (is_array($params['victim'])) {
            foreach ($params['victim'] as $id) {
               if (is_numeric($id) && ($id > 0)) {
                  $data['_additional_requesters'][] = array('users_id'         => $id,
                                                                                 'use_notification' => false);
               } else {
                  return (array( 'error' => "Victim Id is not numeric!" ));
               }
            }
         } else if (is_numeric($params['victim']) && ($params['victim'] > 0)) {
            $data['_additional_requesters'][] = array('users_id'         => $params['victim'],
                                                                           'use_notification' => false);
         } else {
            return (array( 'error' => "Victim is unknown!" ));
         }
      }

      if (isset($params['observer'])) {
         if (is_array($params['observer'])) {
            foreach ($params['observer'] as $id) {
               if (is_numeric($id) && ($id > 0)) {
                  $data['_additional_observers'][] = array(
                     'users_id'         => $id,
                     'use_notification' => true);
               } else {
                  return (array( 'error' => "Observer Id is not numeric!" ));
               }
            }
         } else if (is_numeric($params['observer']) && ($params['observer'] > 0)) {
            $data['_additional_observers'][] = array(
               'users_id'         => $params['observer'],
               'use_notification' => true);
         } else {
            return (array( 'error' => "Observer is unknown!" ));
         }
      }

      // group (author) : optionnal,  default = none
      if (!isset($params['group'])) {
         $data['_groups_id_requester'] = 0;
      } else {
         if (!is_numeric($params['group'])) {
            return (array( 'error' => "Group Id is not numeric!" ));
         }
         $data['_groups_id_requester'] = $params['group'];
      }

      // groupassign (technicians group) : optionnal,  default = none
      if (isset($params['_groups_id_assign'])) {
         if (!is_numeric($params['_groups_id_assign'])) {
            return (array( 'error' => "Group assign Id is not numeric!" ));
         }
         $data['_groups_id_assign'] = $params['_groups_id_assign'];
      }

      // userassign (technician) : optionnal,  default = none
      if (isset($params['_users_id_assign'])) {
         if (!is_numeric($params['_users_id_assign'])) {
            return (array( 'error' => "User assign Id is not numeric!" ));
         }
         $data['_users_id_assign'] = $params['_users_id_assign'];
      }

      // date (open) : optional, default set by add method
      if (isset($params['date'])) {
         if (preg_match(WEBSERVICES_REGEX_DATETIME, $params['date'])) {
            $data['date'] = $params['date'];
         } else {
            return (array( 'error' => "Date is not valid!" ));
         }
      }

      if (isset($params['itemtype']) && empty($params['itemtype'])) {
         unset($params['itemtype']);
      }
      if (isset($params['item']) && !$params['item']) {
         unset($params['item']);
      }
      // Item type + id
      if (isset($params['itemtype'])) {
         if (!class_exists($params['itemtype'])) {
            return (array( 'error' => "Itemtype is not valid!" ));
         }
         if (!isset($params['item'])) {
            unset($params['itemtype']);
         }
      }

      if (isset($params['item'])) {
         if (!isset($params['itemtype'])) {
            return (array( 'error' => "Itemtype is not defined!" ));
         }
         if (!is_numeric($params['item']) || $params['item'] <= 0) {
            return (array( 'error' => "Item id is not valid!" ));
         }

         // Both ok
         $data['itemtype'] = $params['itemtype'];
         $data['items_id'] = $params['item'];
      }

      // Location
      if (isset($params['location'])) {
         $data['locations_id'] = $params['location'];
      }

      // Urgency : optionnal,  default = 3
      if (!isset($params['urgency'])) {
         $data['urgency'] = 3;
      } else if ((!is_numeric($params['urgency'])
                        || ($params['urgency'] < 1)
                        || ($params['urgency'] > 5))
                      || (isset($params['urgency'])
                            && !($CFG_GLPI['urgency_mask']&(1<<$params["urgency"])))) {
         return (array( 'error' => "Urgency is not valid!" ));
      } else {
         $data['urgency'] = $params['urgency'];
      }

      if (isset($params['impact'])) {
         if ((!is_numeric($params['impact'])
               || ($params['impact'] < 1)
               || ($params['impact'] > 5))
             || (isset($params['impact'])
                   && !($CFG_GLPI['impact_mask']&(1<<$params["impact"])))) {
            return (array( 'error' => "Impact is not valid!" ));
         } else {
            $data['impact'] = $params['impact'];
         }
      }

      // category : optionnal
      if (isset($params['category'])) {
         if (!is_numeric($params['category']) || ($params['category'] < 1)) {
            return (array( 'error' => "Category is not valid!" ));
         }
         $data['itilcategories_id'] = $params['category'];
      }

      // type : optionnal (default = INCIDENT)
      if (isset($params['type'])) {
         $types = Ticket::getTypes();
         if (!is_numeric($params['type']) || !isset($types[$params['type']])) {
            return (array( 'error' => "Type is not valid!" ));
         }
         $data['type'] = $params['type'];
      }

      if (isset($params['slas_id'])) {
         $data['slas_id'] = $params['slas_id'];
      }

      if ($debug) {
         return $data;
      } else {
         $ticket = new Ticket();
         if ($newID = $ticket->add($data)) {
            return self::methodGetTicket(array('ticket' => $newID), $protocol);
         } else {
            return (array( 'error' => "Ticket creation failed: " . implode(', ', $data) ));
         }
      }

      return (array( 'error' => "Ticket creation failed: " . implode(', ', $data) ));
   }

   /**
    * Get a ticket information, with its followup
    * for an authenticated user
    *
    * @param $params    array of options (ticket, id2name)
    * @param $protocol        the communication protocol used
    *
    * @return array of hashtable
    **/
    static function methodGetTicket($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array('ticket'  => 'integer,mandatory',
                             'id2name' => 'bool,optional',
                             'help'    => 'bool,optional');
      }

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $ticket = new Ticket();

      if (!isset($params['ticket'])) {
         return (array( 'error' => "Missing parameter: ticket!" ));
      }

      if (!is_numeric($params['ticket'])) {
         return (array( 'error' => "Bad parameter: ticket!" ));
      }

      if (version_compare(GLPI_VERSION,'0.85','lt')) {
         if (!$ticket->can($params['ticket'], 'r')) {
            return (array( 'error' => "Required ticket (" . $params['ticket'] . ") not found! Ticket does not exist or current user is not allowed to see the required ticket." ));
         }
      } else {
         if (!$ticket->can($params['ticket'], READ)) {
            return (array( 'error' => "Required ticket (" . $params['ticket'] . ") not found! Ticket does not exist or current user is not allowed to see the required ticket." ));
         }
      }

      $resp = $ticket->fields;
      if ($resp['itemtype']) {
         $item = getItemForItemtype($resp['itemtype']);
      } else {
         $item = false;
      }
      $resp['solution'] = Html::clean(Toolbox::unclean_cross_side_scripting_deep($resp['solution']));

      $nextaction = new SlaLevel_Ticket();
      if ($ticket->fields['slas_id'] && $nextaction->getFromDBForTicket($ticket->fields['id'])) {
         $resp['slalevels_next_id']   = $nextaction->fields['slalevels_id'];
         $resp['slalevels_next_date'] = $nextaction->fields['date'];
      } else {
         $resp['slalevels_next_id']   = 0;
         $resp['slalevels_next_date'] = '';
      }

      if (isset($params['id2name'])) {
         $resp['users_name_recipient']
                  = Html::clean(getUserName($ticket->fields['users_id_recipient']));
         $resp['users_name_lastupdater']
                  = Html::clean(getUserName($ticket->fields['users_id_lastupdater']));
         $resp['ticketcategories_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_itilcategories',
                                                                              $ticket->fields['itilcategories_id']));
         $resp['entities_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_entities', $resp['entities_id']));
         $resp['status_name']
                  = Html::clean($ticket->getStatus($resp['status']));
         $resp['requesttypes_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                                              $resp['requesttypes_id']));
         $resp['solutiontypes_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                              $resp['solutiontypes_id']));
         $resp['slas_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_slas', $resp['slas_id']));
         $resp['slalevels_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_slalevels', $resp['slalevels_id']));
         $resp['slalevels_next_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_slalevels',
                                                                              $resp['slalevels_next_id']));
         $resp['urgency_name']
                  = Ticket::getUrgencyName($resp['urgency']);
         $resp['impact_name']
                  = Ticket::getImpactName($resp['impact']);
         $resp['priority_name']
                  = Ticket::getPriorityName($resp['priority']);
         $resp['type_name']
                  = Ticket::getTicketTypeName($resp['type']);
         $resp['global_validation_name']
                  = TicketValidation::getStatus($resp['global_validation']);
         $resp['locations_name']
                  = Html::clean(Dropdown::getDropdownName('glpi_locations', $resp['locations_id']));

         if ($item && $item->getFromDB($resp['items_id'])) {
            $resp['items_name']     = Html::clean($item->getNameID());
            $resp['itemtype_name']  = Html::clean($item->getTypeName());
         } else {
            $resp['items_name']     = __('General');
            $resp['itemtype_name']  = '';
         }
      }

      $resp['users']          = array();
      $resp['groups']         = array();
      $resp['followups']      = array ();
      $resp['tasks']          = array ();
      $resp['documents']      = array ();
      $resp['events']         = array ();
      $resp['validations']    = array ();
      $resp['satisfaction']   = array ();

      if (Session::haveRight('observe_ticket', '1')) {
         // Followups
         $query = "SELECT *
                        FROM `glpi_ticketfollowups`
                        WHERE `tickets_id` = '" . $params['ticket'] . "' ";

         if (!Session::haveRight("show_full_ticket", "1")) {
            $query .= " AND (`is_private`='0'
                                     OR `users_id` ='" . Session::getLoginUserID() . "' ) ";
         }
         $query .= " ORDER BY `date` DESC";

         foreach ($DB->request($query) as $data) {
            if (isset($params['id2name'])) {
               $data['users_name']
                        = Html::clean(getUserName($data['users_id']));
               $data['requesttypes_name']
                        = Html::clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                                                    $data['requesttypes_id']));
            }
            $resp['followups'][] = $data;
         }

         // Tasks
         $query = "SELECT *
                        FROM `glpi_tickettasks`
                        WHERE `tickets_id` = '" . $params['ticket'] . "' ";

         if (!Session::haveRight("show_full_ticket", "1")) {
            $query .= " AND (`is_private`='0'
                                     OR `users_id` ='" . Session::getLoginUserID() . "' ) ";
         }
         $query .= " ORDER BY `date` DESC";

         foreach ($DB->request($query) as $data) {
            if (isset($params['id2name'])) {
               $data['users_name']
                        = Html::clean(getUserName($data['users_id']));
               $data['taskcategories_name']
                        = Html::clean(Dropdown::getDropdownName(
                              'glpi_taskcategories',
                              $data['taskcategories_id']));
            }
            $resp['tasks'][] = $data;
         }

         // Documents
         $resp['documents'] = PluginWebservicesMethodTools::getDocForItem(
               $ticket,
               isset($params['id2name']));

         // History
         $resp['events'] = Log::getHistoryData($ticket, 0, $_SESSION['glpilist_limit']);
         foreach ($resp['events'] as $key => $val) {
            $resp['events'][$key]['change'] = Html::clean($resp['events'][$key]['change']);
         }
      }

      if (Session::haveRight('create_request_validation', 1)
                  || Session::haveRight('create_incident_validation', 1)
                  || Session::haveRight('validate_request', 1)
                  || Session::haveRight('validate_incident', 1)) {

          $query = "SELECT *
                         FROM `glpi_ticketvalidations`
                         WHERE `tickets_id` = '".$params['ticket']."' ";
          foreach ($DB->request($query) as $data) {
            if (isset($params['id2name'])) {
               $data['users_name']          = Html::clean(getUserName($data['users_id']));
               $data['users_name_validate'] = Html::clean(getUserName($data['users_id_validate']));
               $data['status_name']         = TicketValidation::getStatus($data['status']);
            }
            $resp['validations'][] = $data;
          }
      }

      // Users & Groups
      $tabtmp = array(CommonITILActor::REQUESTER => 'requester',
                              CommonITILActor::OBSERVER  => 'observer',
                              CommonITILActor::ASSIGN    => 'assign');
      foreach ($tabtmp as $num => $name) {
         $resp['users'][$name] = array();
         foreach ($ticket->getUsers($num) as $user) {
            if (isset($params['id2name'])) {
               if ($user['users_id']) {
                  $user['users_name'] = Html::clean(getUserName($user['users_id']));
               } else {
                  $user['users_name'] = $user['alternative_email'];
               }
            }
            unset($user['tickets_id']);
            unset($user['type']);
            $resp['users'][$name][] = $user;
         }
         $resp['groups'][$name] = array();
         foreach ($ticket->getGroups($num) as $group) {
            if (isset($params['id2name'])) {
               $group['groups_name'] = Html::clean(Dropdown::getDropdownName(
                     'glpi_groups',
                     $group['groups_id']));
            }
            unset($group['tickets_id']);
            unset($group['type']);
            $resp['groups'][$name][] = $group;
         }
      }
      // Suppliers
      $resp['suppliers']['assign'] = array();
      foreach ($ticket->getSuppliers(CommonITILActor::ASSIGN) as $supplier) {
         if (isset($params['id2name'])) {
            $supplier['suppliers_name']
                = Html::clean(Dropdown::getDropdownName(
                      'glpi_suppliers',
                      $supplier['suppliers_id']));
         }
         unset($supplier['tickets_id']);
         unset($supplier['type']);
         $resp['suppliers'][$name][] = $supplier;
      }


      // Satisfaction
      $satisfaction = new TicketSatisfaction();
      if ($satisfaction->getFromDB($params['ticket'])) {
         $resp['satisfaction'] = $satisfaction->fields;
      }
      return $resp;
   }

   /**
    * Add a followup to a existing ticket
    * for an authenticated user
    *
    * @param $params array of options (ticket, content)
    * @param $protocol
    *
    * @return array of hashtable
    **/
   static function methodAddTicketFollowup($params, $protocol) {

      if (isset($params['help'])) {
         return array('ticket'  => 'integer,mandatory',
                             'content' => 'string,mandatory',
                             'users_login' => 'string,optional',
                             'close'   => 'bool,optional',
                             'reopen'  => 'bool,optional',
                             'source'  => 'string,optional',
                             'private' => 'bool,optional',
                             'help'    => 'bool,optional');
      }
      if (!Session::getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }
      $ticket = new Ticket();

      if (isset($params['users_login']) && is_numeric($params['users_login'])) {
         return (array("error" => "users_login should be a string" ));
      }

      if (isset($params['users_login']) && is_string($params['users_login'])) {
         $user = new User();
         if(!$users_id = $user->getIdByName($params['users_login'])) {
            return (array("error" => "unknown user!" ));
         }
      }

      if (!isset($params['ticket'])) {
         return (array("error" => "missing parameter 'ticket'" ));
      }

      if (!is_numeric($params['ticket'])) {
         return (array("error" => "ticket parameter should be numeric" ));
      }

      if (!$ticket->can($params['ticket'],'r')) {
         return (array("error" => "ticket does not exist" ));
      }

      if (!$ticket->canAddFollowups()) {
         return (array("error" => "ticket add followup not allowed" ));
      }

      if (in_array($ticket->fields["status"], $ticket->getSolvedStatusArray())
               && !$ticket->canApprove()) {// Logged user not allowed
         if(isset($users_id)) {// If we get the users id
            $approbationSolution = self::checkApprobationSolution($users_id, $ticket);
            if(!$approbationSolution) {
               return (array("error" => "'ticket' approbation action not permitted"));
            }
         } else {
            return (array("error" => "'ticket' approbation action not permitted"));
         }
      }

      if (!isset($params['content'])) {
         return (array( "error" => "missing parameter 'content'"));
      }

      // Source of the ticket, dynamically created
      if (isset($params['source'])) {
         if (empty($params['source'])) {
            return (array( "error" => "empty parameter 'source'"));
         }
         $source = Dropdown::importExternal('RequestType', $params['source']);
      } else {
         $source = Dropdown::importExternal('RequestType', 'Dashboard');
      }

      $private = (isset($params['private']) && $params['private'] ? 1 : 0);

      $followup = new TicketFollowup();
      $user = 0;
      if (isset($users_id)) {
         $user = $users_id;
      }
      $data = array(
         'tickets_id' => $params['ticket'],
         'requesttypes_id' => $source,
         'is_private' => $private,
         'users_id'   => $user,
         'content'    => addslashes(Toolbox::clean_cross_side_scripting_deep($params["content"])));

      if (isset($params['close'])) {
         if (isset($params['reopen'])) {
            return (array( "error" => "cannot use both 'close' and 'reopen' parameters"));
         }

         if (in_array($ticket->fields["status"], $ticket->getSolvedStatusArray())) {
            $data['add_close'] = 1;
            if (isset($users_id)) {
               $data['users_id'] = $users_id;
            }
         } else {
            return (array( "error" => "cannot use 'close' for not solved ticket"));
         }
      }

      if (isset($params['reopen'])) {
         if (in_array($ticket->fields['status'], array(Ticket::SOLVED, Ticket::WAITING))) {
            $data['add_reopen'] = 1;
            if (isset($users_id)) {
               $data['users_id'] = $users_id;
            }
         } else {
            return (array( "error" => "cannot use 'reopen' for not solved or waiting ticket"));
         }
      }

      if (in_array($ticket->fields["status"], $ticket->getSolvedStatusArray())
            && !isset($params['close'])
            && !isset($params['reopen'])) {
         return (array( "error" => "missing 'reopen' or 'close' for solved ticket"));
      }

      if (in_array($ticket->fields["status"], $ticket->getClosedStatusArray())) {
         return (array( "error" => "cannot add a followup to a closed ticket"));
      }

      if ($followup->add($data)) {
         return self::methodGetTicket(array('ticket' => $params['ticket']), $protocol);
      }
      return (array("error" => "ticket add followup error" ));
   }

   /**
    * Add a user message
    *
    * @param $params array of options (ticket, content)
    * @param $protocol
    *
    * @return array of hashtable
    **/
   static function methodAddUserMessage($params, $protocol=null) {

      if (!Session::getLoginUserID()) {
         return self::Error("Not authenticated");
      }
      $message = new PluginAlignaksMessage();

      if (isset($params['users_login']) && is_numeric($params['users_login'])) {
         return self::Error("users_login should be a string" );
      }

      $users_id = Session::getLoginUserID();
      $user = new User();
      $user->getFromDB($users_id);
      if (isset($params['users_login']) && is_string($params['users_login'])) {
         $user = new User();
         if(!$users_id = $user->getIdByName($params['users_login'])) {
            return self::Error("Unknown user" );
         }
      }

      $entities_id = $_SESSION['glpiactive_entity'];
      if (isset($params['entities_id']) && is_numeric($params['entities_id'])) {
         $entities_id = $params['entities_id'];
      }

      if (!$message->canCreate()) {
         return self::Error("User message creation not allowed!");
      }

      if (!isset($params['message'])) {
         return self::Error("Missing parameter 'message'");
      }

      if (!isset($params['source'])) {
         return self::Error("Missing parameter 'source'");
      }

      if (!isset($params['type'])) {
         return self::Error("Missing parameter 'type'");
      }

      $data = array(
         'source' => $params['source'],
         'type'         => $params['type'],
         'users_id'     => $users_id,
         'entities_id'  => $entities_id,
         'message'      => addslashes(Toolbox::clean_cross_side_scripting_deep($params['message']))
         );

      PluginAlignaksConfig::debugLog("WS methodAddUserMessage: ", $data);

      if ($message->add($data)) {
         return (array("ok" => "" ));
      }
      return self::Error("WS AddUserMessage error");
   }

   /*
    * WS ackService :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - hostname : (obligatoire)
    *
    * - service : (obligatoire)
    *
    * - service_id : identifiant du service dans le plugin Monitoring (optionnel)
    *   Défaut: vide, pas d'enregistrement de l'information dans la table des services
    *
    * - comment : commentaire (optionnel)
    *   Défaut: vide
    *
    * - entity : entité courante (optionnel)
    *   Défaut: entité courante de l'utilisateur
    *
    * - operation : action liée à l'acquittement (optionnel)
    *   Défaut: add
    *   Possible: delete (à confirmer ... et à tester !)
    *
    * - sticky
    *   Défaut: 1
    *
    * - notify
    *   Défaut: 1
    *
    * - persistent
    *   Défaut: 1
    */
   static function methodAckService($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
      }

      // Assume it is an host acknowledge
      $ackHost = true;

      // Hostname : always mandatory
      if ((!isset($params['hostname']) || empty($params['hostname']))) {
         return ('{ "error": "Missing \'hostname\' parameter" }');
      }

      // Service : optional if host acknowledge
      if ((isset($params['service']) && ! empty($params['service']))) {
         $ackHost = false;
         // return ('{ "error": "Missing \'service\' parameter" }');
      }

      // Comment : optional
      if ((!isset($params['comment']) || empty($params['comment']))) {
         $params['comment'] = 'Acknowledged - No comment';
      }

      // Entity : optional, default = current one
      if (!isset($params['entity'])) {
         $entity = $_SESSION['glpiactive_entity'];
      } else {
         if (!is_numeric($params['entity'])
               || !in_array($params['entity'], $_SESSION['glpiactiveentities'])) {
            return (array( 'error' => "Entity Id is not numeric!" ));
         }
         $entity = $params['entity'];
      }

      // Get Shinken tag for current entity
      $tag = PluginMonitoringEntity::getTagByEntities($entity);
      $pmTag = new PluginMonitoringTag();

      $url = 'http://'.$pmTag->getIP($tag).':'.$pmTag->getPort($tag).'/';
      if ($debug) {
         echo "Shinken tag/url: $tag / $url\n";
      }

      $user = new User();
      $user->getFromDB(Session::getLoginUserID());

      $a_fields = array(
            'action'              => empty($params['operation']) ? 'add' : $params['operation'],
            'host_name'           => $params['hostname'],
            'author'              => $user->getName(1),
            'comment'             => mb_convert_encoding($params['comment'], "iso-8859-1"),
            'sticky'              => isset($params['sticky']) ? $params['sticky'] : '1',
            'notify'              => isset($params['notify']) ? $params['notify'] : '1',
            'persistent'          => isset($params['persistent']) ? $params['persistent'] : '1'
      );

      if ($ackHost) {
         if ($debug) {
            echo "Host_id: {$params['host_id']}\n";
         }

         $pmHost = new PluginMonitoringHost();
         $pmHost->getFromDB($params['host_id']);
         if (self::sendCommand($url, 'acknowledge', $a_fields, '', $pmTag->getAuth($tag), $debug)) {
            $pmHost->setAcknowledged(mb_convert_encoding($params['comment'], "iso-8859-1"), false);

            $a_services = $pmHost->getServicesID();
            if (is_array($a_services)) {
               foreach ($a_services as $service_id) {
                  if ($debug) {
                     echo "Service_id: {$service_id}\n";
                  }

                  // Update Plugin Monitoring services table
                  $pmService = new PluginMonitoringService();
                  $pmService->getFromDB($service_id);
                  $a_fields['service_description'] = $pmService->getName(array('shinken'=>'1'));

                  if (self::sendCommand($url, 'acknowledge', $a_fields, '', $pmTag->getAuth($tag), $debug)) {
                     // Update Plugin Monitoring services table
                     $pmService->setAcknowledged(mb_convert_encoding($params['comment'], "iso-8859-1"), false);
                  } else {
                     if ($debug) {
                        echo "Error";
                     }
                     return (array( 'error' => "Acknowledgement not received for service: $service_id!" ));
                  }
               }
            }
         } else {
            if ($debug) {
               echo "Error";
            }
            return (array( 'error' => "Acknowledgement not received for host!" ));
         }
         return (array( 'ok' => "Acknowledgement sent." ));
      } else {
         if ($debug) {
            echo "Service_id: {$params['service_id']}\n";
         }

         // Update Plugin Monitoring services table
         $pmService = new PluginMonitoringService();
         $pmService->getFromDB($params['service_id']);
         // if ($debug) print_r($pmService->fields);
         $a_fields['service_description'] = $pmService->getName(array('shinken'=>'1'));
         if ($debug) {
            print_r($a_fields);
         }

         if (self::sendCommand($url, 'acknowledge', $a_fields, '', $pmTag->getAuth($tag), $debug)) {
            if ($debug) {
               echo "Ok";
            }

            // Update Plugin Monitoring services table
            $pmService->setAcknowledged(mb_convert_encoding($params['comment'], "iso-8859-1"), false);
            // $pmService->getFromDB($params['service_id']);
            // if ($debug) print_r($pmService->fields);

            return (array( 'ok' => "Acknowledgement sent." ));
         } else {
            if ($debug) {
               echo "Error";
            }
            return (array( 'error' => "Acknowledgement not received!" ));
         }
      }
   }

   /*
    * WS downtimeHost :
    * - debug : mode debug
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - hostname : (obligatoire)
    *
    * - entity : entité courante (optionnel)
    *   Défaut: entité courante de l'utilisateur
    *
    * - operation : action liée à l'acquittement (optionnel)
    *   Défaut: add
    *   Possible: delete (à confirmer ... et à tester !)
    *
    * - sticky
    *   Défaut: 1
    *
    * - notify
    *   Défaut: 1
    *
    * - persistent
    *   Défaut: 1
    */
   static function methodDowntimeHost($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }


      $debug = false;
      if (isset($params['debug'])) {
         $debug=true;
      }

      // Hostname : always mandatory
      if ((!isset($params['hostname']) || empty($params['hostname']))) {
         return ('{ "error": "Missing \'hostname\' parameter" }');
      }

      // Comment : optional
      if ((!isset($params['comment']) || empty($params['comment']))) {
         $params['comment'] = 'Acknowledged - No comment';
      }

      // entity : optionnal, default = current one
      if (!isset($params['entity'])) {
         $entity = $_SESSION['glpiactive_entity'];
      } else {
         if (!is_numeric($params['entity'])
               || !in_array($params['entity'], $_SESSION['glpiactiveentities'])) {
            return (array( 'error' => "Entity Id is not numeric!" ));
         }
         $entity = $params['entity'];
      }

      // Get Shinken tag for current entity
      $tag = PluginMonitoringEntity::getTagByEntities($entity);
      $pmTag = new PluginMonitoringTag();

      $url = 'http://'.$pmTag->getIP($tag).':'.$pmTag->getPort($tag).'/';

      $user = new User();
      $user->getFromDB(Session::getLoginUserID());

      /*
      Schedules downtime for a specified host. If the "fixed" argument is set to one (1), downtime will start and end at the times specified by the "start" and "end" arguments. Otherwise, downtime will begin between the "start" and "end" times and last for "duration" seconds. The "start" and "end" arguments are specified in time_t format (seconds since the UNIX epoch). The specified host downtime can be triggered by another downtime entry if the "trigger_id" is set to the ID of another scheduled downtime entry. Set the "trigger_id" argument to zero (0) if the downtime for the specified host should not be triggered by another downtime entry.
      */
      // Start time : now ...
      $start_time = strtotime(date('Y-m-d H:i:s')) + 15;
      // Duration
      if ((!isset($params['duration']) || empty($params['duration']))) {
         // 3 days ...
         $params['duration'] = 60*60*24*3;
      }
      // End time : now + duration ...
      $end_time = $start_time+$params['duration'];

      $a_fields = array(
            'action'              => empty($params['operation']) ? 'add' : $params['operation'],
            'host_name'           => $params['hostname'],
            'author'              => $user->getName(1),
            'comment'             => mb_convert_encoding($params['comment'], "iso-8859-1"),
            'flexible'            => isset($params['flexible']) ? $params['flexible'] : '0',
            'fixed'               => isset($params['fixed']) ? $params['fixed'] : '1',
            'start_time'          => date('Y-m-d H:i:s', $start_time),
            'end_time'            => date('Y-m-d H:i:s', $end_time),
            'trigger_id'          => isset($params['trigger_id']) ? $params['trigger_id'] : '0',
            'duration'            => isset($params['duration']) ? $params['duration'] : '1'
      );
      if ($debug) {
         print_r($a_fields);
      }

      // Acknowledge host and services ...
      $cr = self::methodAckService($params, $protocol);
      if ($debug) {
         echo "Ack CR: $cr\n";
      }

      if (self::sendCommand($url, 'downtime', $a_fields, '', $pmTag->getAuth($tag), $debug)) {
         if ($debug) {
            echo "Ok";
         }
         if (isset($params['host_id'])) {
            // Update Plugin Monitoring hosts table
            $pmHost = new PluginMonitoringHost();
            $pmHost->getFromDB($params['host_id']);
            // $pmHost->setAcknowledged(mb_convert_encoding($params['comment'], "iso-8859-1"));

            $a_services = $pmHost->getServicesID();
            if (is_array($a_services)) {
               foreach ($a_services as $service_id) {
                  if ($debug) {
                     echo "Service_id: {$service_id}\n";
                  }

                  // Update Plugin Monitoring services table
                  $pmService = new PluginMonitoringService();
                  $pmService->getFromDB($service_id);
                  $a_fields['service_description'] = $pmService->getName(array('shinken'=>'1'));

                  if (self::sendCommand($url, 'downtime', $a_fields, '', $pmTag->getAuth($tag), $debug)) {
                     // Update Plugin Monitoring services table
                     // $pmService->setAcknowledged(mb_convert_encoding($params['comment'], "iso-8859-1"), false);
                  } else {
                     if ($debug) {
                        echo "Error";
                     }
                     return (array( 'error' => "Acknowledgement not received for service: $service_id!" ));
                  }
               }
            }
         }
         return (array( 'ok' => "Host and services downtime sent." ));
      } else {
         if ($debug) {
            echo "Error";
         }
         return (array( 'error' => "Host downtime not received!" ));
      }
   }

   /*
    * Send a Shinken command
    */
   static function sendCommand($url, $action, $a_fields, $fields_string='', $auth='', $debug=false) {

      if ($fields_string == '') {
         foreach($a_fields as $key=>$value) {
            $fields_string .= $key.'='.$value.'&';
         }
         rtrim($fields_string, '&');
      }
      if ($debug) {
         echo "Fields: $fields_string\n";
      }

      $ch = curl_init();

      curl_setopt($ch,CURLOPT_URL, $url.$action);
      curl_setopt($ch,CURLOPT_POST, count($a_fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
      if ($auth != '') {
         curl_setopt($ch,CURLOPT_USERPWD, $auth);
      }

      $ret = curl_exec($ch);
      $return = true;
      if ($ret === false) {
         if ($debug) {
            echo "Shinken communication failed: ".curl_error($ch).'<br/>'.$url.$action.'?'.$fields_string."\n";
         }
         $return = false;
      } else if (strstr($ret, 'error')) {
         if ($debug) {
            echo "Shinken communication failed: ".curl_error($ch).'<br/>'.$url.$action.'?'.$fields_string."\n";
         }
         $return = false;
      } else {
         if ($debug) {
            echo "Shinken communication succeeded: ".curl_error($ch).'<br/>'.$url.$action.'?'.$fields_string."\n";
         }
         $return = true;
      }
      curl_close($ch);
      return $return;
   }

   /**
      * Get a kiosk configuration for an authenticated user
      *
      * @param $params    array of options (ticket, id2name)
      * @param $protocol  the communication protocol used
      *
      * @return array of hashtable
    **/
   static function methodGetKioskConfiguration($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array('hostname'  => 'string,mandatory',
                           'help'      => 'bool,optional');
      }

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      $kc = new PluginAlignaksKioskConfiguration();

      if (!isset($params['hostname'])) {
         return (array( 'error' => "Missing parameter: hostname!" ));
      }

      $a_confs = $kc->find("`serial`='".$params['hostname']."'", "", 1);
      if (count($a_confs) > 0) {
         // Configuration found in DB for requested kiosk ...
         $a_conf = current($a_confs);
         $items_id = $a_conf['id'];
      } else {
         // No configuration found for requested kiosk ...
         $kiosk = new Computer();
         $a_kiosks = $kiosk->find("`name`='".$params['hostname']."'", "", 1);
         if (count($a_kiosks) > 0) {
            // Kiosk found in DB ...
            $a_kiosk = current($a_kiosks);
            $kiosks_id = $a_kiosk['id'];
            $kiosk->getFromDB($kiosks_id);

            $kc->setDefaultContent($kiosk->fields['organizations_id']);
            // Make computer configuration (organizations_id = -1)
            $kc->fields['organizations_id'] = -1;
            $kc->fields['kiosks_id'] = $kiosks_id;
            $kc->fields['comment'] = "-----\nConfiguration non renseignée !\n-----\n".$kc->fields['comment'];
            $items_id = $kc->add($kc->fields);
         } else {
            // Kiosk not found in DB ...
         }
         $items_id = $a_conf['id'];

         // return (array( 'error' => "Required kiosk configuration not found!" ));
      }
      $kc->getFromDB($items_id);

      $resp = $kc->fields;
      foreach ($kc->fields as $field=>$value) {
         if (in_array ($field, array('id', 'name', 'organizations_id', 'project_leaders_id'))) {
            unset($resp[$field]);
         }
      }
      return $resp;
   }

   /**
   * Convert strings with underscores into CamelCase
   *
   * @param    string   $string              The string to convert
   * @param    bool     $first_char_caps     camelCase or CamelCase
   * @return   string   The converted string
   *
   */
   static function underscoreToCamelCase($string, $first_char_caps = false) {
      if( $first_char_caps == true ) {
         $string[0] = strtoupper($string[0]);
      }
      $func = create_function('$c', 'return strtoupper($c[1]);');
      return preg_replace_callback('/_([a-z])/', $func, $string);
   }
   /**
   * Convert strings with CamelCase into underscores
   *
   * @param    string   $string              The string to convert
   * @return   string   The converted string
   *
   */
   function camelCaseToUnderscore($string) {
      return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $string));
   }

   /**
    * WS getApplicationConfiguration, parameters :
    * - debug : mode debug
    *   Non utilisé !
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - app_name : application name
    *   Nom de l'application tel que défini dans la base de données
    *
    * - version :
    *   Si ce champ est présent et vide, le WS ne renvoie que le manifeste de l'application et pas les données.
    *
    * - kiosk_name : kiosk serial number
    *   Numéro de série de la borne pour laquelle on veut récupérer la configuration
    *   * permet de récupérer la configuration par défaut de l'application
    *
    * - entitiesList : entité ou liste d'entités
    *   Non utilisé !
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    **
    *
    * Retour:
      Si un champ dont le nom commence par image existe dans la DB :
      - S’il est vide, on met enabled à false
      - S’il n’est pas vide, on renvoie :
         le contenu du champ si il n'y a pas l’image correspondante sur le serveur (répertoire PLUGIN_KIOSKS_IMAGES_DIR)
         l’encodage Base64 de l’image si le fichier est trouvé

      De cette façon, on peut savoir si une image est prévue dans la configuration par défaut ou pas, et si une image est déjà définie on a directement son contenu.

      {
         id: "1",
         entitiesId: "1",
         isActive: "1",
         kioskName: "*",
         storage: "json",
         manifest: {
            appName: "test",
            version: "1",
            author: "Fred",
            description: "Application de test - description de l",
            copyright: "(c) Fred, cette année!",
            license: ""
         },
         configuration: {
            startPage: null,
            touchMode: "1",
            oskbdEnabled: "1",
            oskbdAutoshow: "1",
            session: "1",
            navigationBar: "0",
            inactivity: "1",
            inactivityDelay: "300",
            reactivityDelay: "10"
         },
         screensaver: {
            enabled: "1",
            images: {
               1: {
                  enabled: false,
                  image: "",
                  text1: "Texte 1ère ligne",
                  text2: "Texte 2nde ligne"
               },
               2: {
                  enabled: true,
                  image: "ss2.jpg",
                  text1: null,
                  text2: null
               },
               3: {
                  enabled: true,
                  image: "ss3.jpg",
                  text1: null,
                  text2: null
               },
               4: {
                  enabled: true,
                  image: "data:image/jpg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD==……….",
                  text1: null,
                  text2: null
               }
            },
            delay: "15"
         },
         menu: {
            enabled: "1",
            choices: {
               1: {
                  enabled: true,
                  image: "data:image/jpg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP ……….",
                  text: "Texte",
                  url: "URL"
               },
               2: {
                  enabled: false,
                  image: null,
                  text: null,
                  url: null
               },
               3: {
                  enabled: false,
                  image: null,
                  text: null,
                  url: null
               },
               4: {
                  enabled: false,
                  image: null,
                  text: null,
                  url: null
               }
            }
         },
         imageBackground: "data:image/jpg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/ /Z ………."
      }

    *
    **/
   static function methodGetApplicationConfiguration($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array(  'app_name'        => 'string,mandatory',
                        'kiosk_name'      => 'string,mandatory',
                        'help'            => 'bool,optional');
      }

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      if (!isset($params['app_name'])) {
         return (array( 'error' => "Missing parameter: app_name!" ));
      }

      if (!isset($params['kiosk_name'])) {
         return (array( 'error' => "Missing parameter: kiosk_name!" ));
      }


      $debug = false;
      $row['id']=-1;
      if (isset($params['debug'])) {
         $debug=true;
         $row['ws']="kiosks.getApplicationConfiguration";
      }

      $version = false;
      if (isset($params['version'])) {
         $version=true;
      }

      $where = "`mnf_app_name`='" . $params['app_name'] . "' AND `kiosk_name`='" . $params['kiosk_name'] . "'";
      $whereDefault = "`mnf_app_name`='" . $params['app_name'] . "' AND (`kiosk_name`='*' OR `kiosk_name`='')";

      // Entities
      // if (isset($params['entitiesList'])) {
         // $row['entitiesList']=$params['entitiesList'];
         // if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            // return (array( 'error' => "Access to all required entities is not allowed!" ));
         // }
         // $where = $where . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications", '', $params['entitiesList']);
         // $whereDefault = $whereDefault . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications", '', $params['entitiesList']);
      // } else {
         // $where = $where . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications");
         // $whereDefault = $whereDefault . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications");
      // }

      // Search required application ...
      $ka = new PluginAlignaksApplication();
      $a_confs = $ka->find($where, "", 1);
      if (count($a_confs) > 0) {
         // Configuration found in DB for requested application and kiosk...
         $a_conf = current($a_confs);
         $items_id = $a_conf['id'];
      } else {
         // No configuration found for requested kiosk ...
         $a_confs = $ka->find($whereDefault, "", 1);
         if (count($a_confs) > 0) {
            // Configuration found in DB for default application ...
            $a_conf = current($a_confs);
            $items_id = $a_conf['id'];
         } else {
            // Default configuration not found in DB ...
            return (array( 'error' => "Configuration does not exist in database: '$where'!" ));
         }
      }
      $ka->getFromDB($items_id);

      // $resp = $ka->fields;
      $resp = [];
      foreach ($ka->fields as $field=>$value) {
         if (substr($field, 0, strlen("mnf_")) == "mnf_") {
            $field = substr($field, strlen("mnf_"));
            $resp["manifest"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;

         } else if (!$version && substr($field, 0, strlen("cfg_")) == "cfg_") {
            $field = substr($field, strlen("cfg_"));
            $resp["configuration"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;

         } else if (!$version && substr($field, 0, strlen("screensaver_")) == "screensaver_") {
            if ((! array_key_exists ('screensaver_enabled', $ka->fields)) || (! $ka->fields['screensaver_enabled'])) {
               continue;
            } else if (! array_key_exists('screensaver', $resp) || ! array_key_exists('enabled', $resp['screensaver'])) {
               $resp["screensaver"]["enabled"] = true;
               $resp["screensaver"]["images"] = [];
               $resp["screensaver"]["images"]["count"] = 4;
            }

            $field = substr($field, strlen("screensaver_"));
            if (substr($field, 0, strlen("img")) == "img") {
               $field = substr($field, strlen("img"));
               $number = $field[0];
               $field = substr($field, 2);
               if (substr($field, 0, strlen("image")) == "image") {
                  if (empty($value)) {
                     $resp["screensaver"]["images"][$number]["enabled"] = false;
                     $value = "screensaver.jpg";
                  } else {
                     $resp["screensaver"]["images"][$number]["enabled"] = true;
                  }

                  if (is_dir(PLUGIN_KIOSKS_IMAGES_DIR) && is_file(PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value)) {
                     PluginAlignaksConfig::debugLog("getApplicationConfiguration, found image file: ", PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value);
                     $path = PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value;
                     $type = pathinfo($path, PATHINFO_EXTENSION);
                     $data = file_get_contents($path);
                     $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                     $resp["screensaver"]["images"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $base64;
                     $resp["screensaver"]["images"][$number]["enabled"] = true;
                  } else {
                     $resp["screensaver"]["images"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
                  }
               } else {
                  $resp["screensaver"]["images"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
               }
            } else {
               $resp["screensaver"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
            }

         } else if (!$version && substr($field, 0, strlen("menu_")) == "menu_") {
            if ((! array_key_exists ('menu_enabled', $ka->fields)) || (! $ka->fields['menu_enabled'])) {
               continue;
            } else if (! array_key_exists('menu', $resp) || ! array_key_exists('enabled', $resp['menu'])) {
               $resp["menu"]["enabled"] = true;
               $resp["menu"]["images"] = [];
               $resp["menu"]["images"]["count"] = 4;
            }

            $field = substr($field, strlen("menu_"));
            if (substr($field, 0, strlen("btn")) == "btn") {
               $field = substr($field, strlen("img"));
               $number = $field[0];
               $field = substr($field, 2);
               if (substr($field, 0, strlen("image")) == "image") {
                  if (empty($value)) {
                     $resp["menu"]["choices"][$number]["enabled"] = false;
                     $value = "button.jpg";
                  } else {
                     $resp["menu"]["choices"][$number]["enabled"] = true;
                  }

                  if (is_dir(PLUGIN_KIOSKS_IMAGES_DIR) && is_file(PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value)) {
                     PluginAlignaksConfig::debugLog("getApplicationConfiguration, found image file: ", PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value);
                     $path = PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value;
                     $type = pathinfo($path, PATHINFO_EXTENSION);
                     $data = file_get_contents($path);
                     $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                     $resp["menu"]["choices"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $base64;
                  } else {
                     $resp["menu"]["choices"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
                  }
               } else {
                  $resp["menu"]["choices"][$number][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
               }
            } else if (substr($field, 0, strlen("image_")) == "image_") {
               $field = substr($field, strlen("image_"));
               if (is_dir(PLUGIN_KIOSKS_IMAGES_DIR) && is_file(PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value)) {
                  PluginAlignaksConfig::debugLog("getApplicationConfigurationxxxx, found image file: ", PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value);
                  $path = PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value;
                  $type = pathinfo($path, PATHINFO_EXTENSION);
                  $data = file_get_contents($path);
                  $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                  $resp["menu"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $base64;
               } else {
                  $resp["menu"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
               }
            } else {
               $resp["menu"][PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
            }

         } else if (!$version) {
            if (substr($field, 0, strlen("image")) == "image") {
               $value = "background.jpg";
               if (is_dir(PLUGIN_KIOSKS_IMAGES_DIR) && is_file(PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value)) {
                  PluginAlignaksConfig::debugLog("getApplicationConfiguration, found image file: ", PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value);
                  $path = PLUGIN_KIOSKS_IMAGES_DIR . "/" . $value;
                  $type = pathinfo($path, PATHINFO_EXTENSION);
                  $data = file_get_contents($path);
                  $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                  $resp[PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $base64;
               } else {
                  $resp[PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
               }
            } else {
               $resp[PluginAlignaksWebservice::underscoreToCamelCase($field, false)] = $value;
            }
         }
      }
      return $resp;
   }


   /**
    * WS setApplicationConfiguration, parameters :
    * - debug : mode debug
    *   Non utilisé !
    *   Renvoie une ligne de résultat contenant les paramètres et la requête postée sur la base.
    *   Cette ligne contient un id = -1 pour permettre de la filtrer dans les autres lignes de résultat
    *   Défaut: false
    *
    * - app_name : application name
    *   Nom de l'application tel que défini dans la base de données
    *
    * - kiosk_name : kiosk serial number
    *   Numéro de série de la borne pour laquelle on veut modifier la configuration
    *   * permet de modifier la configuration par défaut de l'application
    *
    * - entitiesList : entité ou liste d'entités
    *   Non utilisé !
    *   Filtre les résultats pour l'entité (ou les entités) demandée. Le login utilisateur utilisé doit avoir accès aux entités demandées sinon le WS retourne une erreur.
    *   exemple: 11 ou 11,12,13
    *   Défaut: les entités autorisées du compte utilisateur
    **
    *
    * Retour:


    *
    **/
   static function methodSetApplicationConfiguration($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array(  'app_name'        => 'string,mandatory',
                        'kiosk_name'      => 'string,mandatory',
                        'help'            => 'bool,optional');
      }

      if (!Session::getLoginUserID()) {
         return (array( 'error' => "User is not authenticated!" ));
      }

      if (!isset($params['app_name'])) {
         return (array( 'error' => "Missing parameter: app_name!" ));
      }

      if (!isset($params['kiosk_name'])) {
         return (array( 'error' => "Missing parameter: kiosk_name!" ));
      }

      if (!isset($params['json'])) {
         return (array( 'error' => "Missing parameter: json!" ));
      }
      // $row_debug['json'] = $params['json'];


      $debug = false;
      $row_debug['id']=-1;
      if (isset($params['debug'])) {
         $debug=true;
         $row_debug['ws']="kiosks.getApplicationConfiguration";
      }

      $where = "`mnf_app_name`='" . $params['app_name'] . "' AND `kiosk_name`='" . $params['kiosk_name'] . "'";
      $whereDefault = "`mnf_app_name`='" . $params['app_name'] . "' AND (`kiosk_name`='*' OR `kiosk_name`='')";

      // Entities
      // if (isset($params['entitiesList'])) {
         // $row['entitiesList']=$params['entitiesList'];
         // if (!Session::haveAccessToAllOfEntities($params['entitiesList'])) {
            // return (array( 'error' => "Access to all required entities is not allowed!" ));
         // }
         // $where = $where . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications", '', $params['entitiesList']);
         // $whereDefault = $whereDefault . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications", '', $params['entitiesList']);
      // } else {
         // $where = $where . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications");
         // $whereDefault = $whereDefault . getEntitiesRestrictRequest("AND", "glpi_plugin_kiosks_applications");
      // }

      // Search required application ...
      $ka = new PluginAlignaksApplication();
      $a_confs = $ka->find($where, "", 1);
      if (count($a_confs) > 0) {
         // Configuration found in DB for requested application and kiosk...
         $a_conf = current($a_confs);
         $items_id = $a_conf['id'];
      } else {
         // No configuration found for requested kiosk ...
         $a_confs = $ka->find($whereDefault, "", 1);
         if (count($a_confs) > 0) {
            // Configuration found in DB for default application ...
            $a_conf = current($a_confs);
            $items_id = $a_conf['id'];
         } else {
            // Default configuration not found in DB ...
            return (array( 'error' => "Configuration does not exist in database: '$where'!" ));
         }
      }
      $ka->getFromDB($items_id);
      $row_debug['found'] = $ka->fields['id']. ": " .$ka->fields['mnf_app_name']. " - " .$ka->fields['kiosk_name'];

      $data = array(
         'id' => $items_id
      );

      foreach ($params['json'] as $field=>$value) {
         if (strcmp($field, "manifest") === 0) {
            $row_debug['fields']['manifest']=[];
            foreach ($value as $field=>$value) {
               $row_debug['fields']['manifest'][]="Found manifest $field";
               // Only allow updating version field ...
               if (strcmp($field, "version") === 0) {
                  $data["mnf_$field"] = $value;
               }
            }

         } else if (strcmp($field, "configuration") === 0) {
            $row_debug['fields']['configuration']="Configuration object ignored ...";

         } else if (strcmp($field, "screensaver") === 0) {
            $row_debug['fields']['screensaver']=[];
            foreach ($value as $field=>$value) {
               $row_debug['fields']['screensaver'][]="Found screensaver $field";
               if (strcmp($field, "images") === 0 && is_array($value)) {
                  for ($i = 1; $i <= 4; $i++) {
                     $row_debug['fields']['screensaver'][]="Found screensaver image $i";
                     if (isset($value[$i]['image'])) $data["screensaver_img{$i}_image"] = $value[$i]['image'];
                     if (isset($value[$i]['text1'])) $data["screensaver_img{$i}_text1"] = $value[$i]['text1'];
                     if (isset($value[$i]['text2'])) $data["screensaver_img{$i}_text2"] = $value[$i]['text2'];
                  }
               } else {
                  $data["screensaver_$field"] = $value;
               }
            }

         } else if (strcmp($field, "menu") === 0) {
            $row_debug['fields']['menu']=[];
            foreach ($value as $field=>$value) {
               $row_debug['fields']['menu'][]="Found menu $field";
               if (strcmp($field, "choices") === 0 && is_array($value)) {
                  for ($i = 1; $i <= 4; $i++) {
                     $row_debug['fields']['menu'][]="Found menu image $i";
                     if (isset($value[$i]['image'])) $data["menu_btn{$i}_image"] = $value[$i]['image'];
                     if (isset($value[$i]['text'])) $data["menu_btn{$i}_text"] = $value[$i]['text'];
                     if (isset($value[$i]['url'])) $data["menu_btn{$i}_url"] = $value[$i]['url'];
                  }
               } else {
                  if (strcmp($field, "background") === 0) {
                     $data["menu_image_background"] = $value;
                  } else {
                     $data["menu_$field"] = $value;
                  }
               }
            }

         } else {
         }
      }

      $resp = [];
      if ($debug) {
         $row_debug['data'] = $data;
         $resp['debug'] = $row_debug;
      }

      if ($ka->update($data)) {
         $resp['ok'] = "Data updated in database";
      } else {
         $resp['error'] = "Error when updating database!";
      }

      return ($resp);
   }


   /**
    * Get table structure for jQuery Datatable SSP table view ...
   **/
   static function methodGetComponentCounterTable($params, $protocol) {
      global $DB;

      $return = "";
      $columns = [];
      $column_names = [];
      $sql = "";
      $message = "";

      $id_component = (isset($_REQUEST['id_component']) ) ? $_REQUEST['id_component'] : "";
      if ($id_component == "") {
         return($return);
      }

      $componentObj = new PluginMonitoringComponent();
      $component = $componentObj->find("`id` = '$id_component'");
      $component_name = str_replace(" ", "", $component[$id_component]['description']);
      $hdc_table = "glpi_plugin_kiosks_hdc_".$component_name;

      if (TableExists($hdc_table)) {
         $sql = "SHOW COLUMNS FROM `".$hdc_table."`";
         $result = $DB->query($sql);
         while ($data = $DB->fetch_array($result)) {
            $counter_name = $data['Field'];
            $columns[] = $counter_name;
            if (in_array($counter_name, array('id', 'entities_id', 'kiosk_name', 'entity_name', 'day'))) {
               $column_names[$counter_name] =  $counter_name;
               continue;
            }
            $counterConf = new PluginAlignaksCounter();
            $counters = $counterConf->find("`counter_name` = '$counter_name'");
            if (count($counters) > 0) {
               $a_found = current($counters);
               $counterConf->getFromDB($a_found['id']);
               $column_names[$counter_name] = $counterConf->getField('name');
            } else {
               $column_names[$counter_name] =  $counter_name;
            }
         }
      } else {
         $message .= "$hdc_table does not exists";
      }

      $return = array('message'=>$message, 'table_name'=> $hdc_table, 'name' => $component_name, 'columns' => $columns, 'column_names' => $column_names);

      return($return);
   }

   /**
    * Get table data for jQuery Datatable SSP table view ...
   **/
   static function methodGetSspTableData($params, $protocol) {
      require_once (GLPI_ROOT . "/plugins/kiosks/lib/ssp.php");

      // DataTables params
      $db = new DB();
      $sql_details = array(
           'user' => $db->dbuser,
           'pass' => $db->dbpassword,
           'db'   => $db->dbdefault,
           'host' => $db->dbhost
           );
      $primaryKey = "id";
      $table = $params['table_name'];
      $data_columns = $params['fields'];

      $columns = array();
      $i = 0;
      foreach($data_columns as $column) {
         $columns[] = array('db' => $column, 'dt' => $i++);
      }
      $join = "";
      $extraWhere = "";
      $ssp = SSP::simple($_REQUEST, $sql_details, $table, $primaryKey, $columns, $join, $extraWhere);
      return ($ssp);
   }
}
