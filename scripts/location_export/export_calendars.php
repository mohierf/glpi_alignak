<?php

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include ("../../../../inc/includes.php");

include ("./docopt.php");

$doc = <<<DOC
calendar_export.php

Usage:
   calendar_export.php [--entity ENTITY] [--include] [--verbose]
   
Options:
   --entity ENTITY      Entity to export calendar from.
   --verbose            Verbose mode (default: 0)
   --include            Include the provided entity (default: 0)
   
If --include is set, the provided entity is included in the list
of entities searched for the calendar exportation. If not, only
its sons are searched for calendars.

DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

// Init debug variable
$_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
$_SESSION['glpilanguage']  = "en_GB";

Session::LoadLanguage();

// Only show errors
$CFG_GLPI["debug_sql"] = $CFG_GLPI["debug_vars"] = 0;
$CFG_GLPI["use_log_in_files"] = 1;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
//set_error_handler('userErrorHandlerDebug');

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

# Verbose mode
$verbose = false;
if (! is_null($args['--verbose']) && $args['--verbose']) {
   echo "Verbose mode is on\n";
   $verbose = true;
}
if ($verbose) {
   echo "Verbose mode is on\n";
}

# Entity
$entityName = "0";
if (! is_null($args['--entity'])) {
   $entityName = $args['--entity'];
}
if ($verbose) {
   echo "Entity: $entityName\n";
}

$foundEntity = new Entity();
if (! $foundEntity->getFromDBByCrit(['name' => $entityName])) {
   if (! $foundEntity->getFromDBByCrit(['id' => $entityName])) {
      die("Required entity was not found, neither by name nor by id!\n");
   }
}
// $foundEntity is the required entity
if ($verbose) {
   echo "Found: ". $foundEntity->getName(). PHP_EOL;
}

$dbu = new DbUtils();
$sonEntity = new Entity();
$calendar = new Calendar();
$entities = [];
if (! is_null($args['--verbose']) && $args['--verbose']) {
   $entities[] = $foundEntity->getID();
}

// Calendars Csv file
$fileName = 'calendars';
$fh_calendars = fopen("$fileName.csv", "w+");
if (! $fh_calendars) {
   die("Disk file writing is not possible". PHP_EOL);
}
$output = "calendar_name;entity_complete_name;is_recursive;comment" . PHP_EOL;
fwrite($fh_calendars, $output);

// Segments Csv file
$fh_segments = fopen($fileName . "_segments.csv", "w+");
if (! $fh_segments) {
   die("Disk file writing is not possible". PHP_EOL);
}
$output = "calendar_name;entity_complete_name;day;begin;end" . PHP_EOL;
fwrite($fh_segments, $output);

// Holidays Csv file
$fh_holidays = fopen($fileName . "_holidays.csv", "w+");
if (! $fh_holidays) {
   die("Disk file writing is not possible". PHP_EOL);
}
$output = "holiday_name;calendar_name;entity_complete_name;is_recursive;comment;begin_date;end_date;is_perpetual" . PHP_EOL;
fwrite($fh_holidays, $output);

foreach ($dbu->getSonsOf("glpi_entities", $foundEntity->getID()) as $sonId) {
   $sonEntity->getFromDB($sonId);
   if (! $calendar->getFromDBByCrit(['entities_id' => $sonEntity->getID()])) {
      continue;
   }

   if ($verbose) {
      echo "Found  a calendar: '" . $calendar->getName() . "' for the entity: '" . $sonEntity->getName() . "'\n";
   }
   $output = $calendar->fields['name'].";".$sonEntity->fields['completename'].";".$calendar->fields['is_recursive'].";".$calendar->fields['comment'] . PHP_EOL;
   fwrite($fh_calendars, $output);

   $query = "SELECT * FROM `glpi_calendarsegments` WHERE `calendars_id`='". $calendar->getID() ."'";
   foreach ($DB->request($query) as $data) {
      if ($verbose) {
         echo "+: {$data['day']}, from {$data['begin']} to {$data['end']}\n";
      }
      $output = $calendar->getName().";".$sonEntity->fields['completename'].";".$data['day'].";".$data['begin'].";".$data['end'] . PHP_EOL;
      fwrite($fh_segments, $output);
   }

   $query = "SELECT DISTINCT `glpi_calendars_holidays`.`id` AS linkID,
                                `glpi_holidays`.*
                FROM `glpi_calendars_holidays`
                LEFT JOIN `glpi_holidays`
                     ON (`glpi_calendars_holidays`.`holidays_id` = `glpi_holidays`.`id`)
                WHERE `glpi_calendars_holidays`.`calendars_id` = '". $calendar->getID() ."'
                ORDER BY `glpi_holidays`.`name`";
   foreach ($DB->request($query) as $data) {
      if ($verbose) {
         echo "-: {$data['name']}, from {$data['begin_date']} to {$data['end_date']}". PHP_EOL;
      }
      $output = $data['name'].";".$calendar->fields['name'].";".$sonEntity->fields['completename'].";".$data['is_recursive'].";".$data['comment'].";".$data['begin_date'].";".$data['end_date'].";".$data['is_perpetual'] . PHP_EOL;
      fwrite($fh_holidays, $output);
   }
}
if ($fh_calendars) {
   fclose($fh_calendars);
}
if ($fh_segments) {
   fclose($fh_segments);
}
if ($fh_holidays) {
   fclose($fh_holidays);
}
