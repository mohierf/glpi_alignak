<?php

/**
 * Script for importing timeslots into the GLPI Fusion Inventory
 * - search and open a CSV file
 * - import found timeslot entries in the DB
 */

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include ("../../../../inc/includes.php");
$dbu = new DbUtils();
$db_entity = new Entity();
$calendar = new Calendar();
$holiday = new Holiday();
$calendarHoliday = new Calendar_Holiday();
$segment = new CalendarSegment();

include ("./docopt.php");

$doc = <<<DOC
calendar_export.php

Usage:
   import_calendars.php [--file FILE] [--verbose] [--update] [--dry-run]
   
Options:
   --file FILE      File prefix to import from.
   --update         Update mode (default: 0)
   --dry-run        Dry-run mode (default: 0)
   --verbose        Verbose mode (default: 0)

If dry-run mode is set, no database operation is done and a message is raised instead of the 
operation. This allows to make a test before really importing in the database or updating
 the database.

If update mode is not set, and an existing item is found, then no database update is done 
and a message is raised instead of the update operation. In this mode, only the new items 
are imported and the existing one are left unchanged.

Indeed, file prefix is not a file name but a file name prefix. With, file as 'calendars'
the script will search for a 'calendars_holidays.csa', a 'calendars_segments.csv' and 
a 'calendars.csv' files to import from.

These files are the one produced by the *export_calendards.php* script.
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

// Verbose mode
$nl2br = false;
$verbose = false;
if (! is_null($args['--verbose']) && $args['--verbose']) {
   $verbose = true;
}
if ($verbose) {
   echon("Verbose mode is on");
}

// Update mode
$update = false;
if (! is_null($args['--update']) && $args['--update']) {
   $update = true;
}
if ($update) {
   echon("Update mode is on");
} else {
   echon("Update mode is off");
}

// Dry-run mode
$dry_run = false;
if (! is_null($args['--dry-run']) && $args['--dry-run']) {
   $dry_run = true;
}
if ($dry_run) {
   echon("Dry-run mode is on");
} else {
   echon("Dry-run mode is off");
}

// Files
if (!isset($fileName)) {
   $fileName = 'calendars';
}
if (! is_null($args['--file'])) {
   $fileName = $args['--file'];
}
echon("Searching for file prefix: $fileName");

// Calendars Csv file
$fh_calendars = fopen($fileName . ".csv", "r");
if (! $fh_calendars) {
   die("Missing '$fileName.csv' file in the current directory.". PHP_EOL);
}
echon("Found: $fileName.csv");

// Segments Csv file
$fh_segments = fopen($fileName . "_segments.csv", "r");
if (! $fh_segments) {
   die("Missing '" . $fileName . "_segments.csv' file in the current directory.". PHP_EOL);
}
echon("Found: " . $fileName . "_segments.csv");

// Holidays Csv file
$fh_holidays = fopen($fileName . "_holidays.csv", "r");
if (! $fh_holidays) {
   die("Missing '" . $fileName . "_holidays.csv' file in the current directory.". PHP_EOL);
}
echon("Found: " . $fileName . "_holidays.csv");

// CVS default file format
$DELIMITER = ",";
if (isset($_SESSION["glpicsv_delimiter"])) {
    $DELIMITER = $_SESSION["glpicsv_delimiter"];
}
$ENCLOSURE = '"';

/*
 * First, managing the calendars
 */
echon("1/ calendars");
echon("-----");
while (($data = fgetcsv($fh_calendars, 0, $DELIMITER, $ENCLOSURE)) !== FALSE) {
   $data[0] = trim($data[0]);
   if (strtolower($data[0]) == 'calendar_name') {
      // File header
      // Expected: "calendar_name;entity_complete_name;is_recursive;comment"
      if ((strtolower($data[1]) != 'entity_complete_name')
         or (strtolower($data[2]) != 'is_recursive')
         or (strtolower($data[3]) != 'comment')){
         echon("***** Malformed header!", true);
         echon("File header: " . serialize($data), true);
         die("The '" . $fileName . ".csv' file does not match the expected format. Giving up!". PHP_EOL);
      }
      continue;
   }
   // Check fields count
   if (count($data) < 4) {
      // Skip incomplete line...
      echon("***** skipping an empty line: " . serialize($data));
      continue;
   }

   // Clean name field
   $name = trim($data[0]);
   if ($name == '') {
      // Skip empty name...
      echon("***** skipping empty name: " . serialize($data));
      continue;
   }

   // Clean and check Entity field
   $entity = trim($data[1]);
   $entity_id = -1;
   if ($entity != '') {
      $db_entities = $db_entity->find("`completename`='".$DB->escape($entity)."'", '', 1);
      if (count($db_entities) > 0) {
         $found_entity = current($db_entities);
         $entity_id = $found_entity["id"];
      } else {
         echon("***** skipping not found entity: '$name / $entity'!");
         continue;
      }
   } else {
      echon("***** skipping empty entity: " . serialize($data));
      continue;
   }

   // Clean and check is_recursive field
   $is_recursive = trim($data[2]);
   if (! in_array($is_recursive, ["0", "1"])) {
      // Skip invalid data...
      echon("***** skipping empty or invalid is_recursive: '$name / $is_recursive'!");
      continue;
   }

   /*
    * Now we have all the fields to create a new calendar
    */
   $input = array(
      'name'            => $name,
      'entities_id'     => $entity_id,
      'is_recursive'    => $is_recursive,
      'comment'         => $data[3]
   );

   $calendar_id = -1;
   $calendars = $calendar->find("`name`='". $DB->escape($name) ."' AND `entities_id`='$entity_id'", '', 1);
   if (count($calendars) > 0) {
      // Update an existing calendar
      $the_calendar = current($calendars);
      $calendar->getFromDB($the_calendar["id"]);
      $input['id'] = $the_calendar["id"];

      echon("-> updating an existing calendar: '$name'...");
      if (! $dry_run) {
         $calendar->update($input);
      } else {
         echon("-> dry-run: update in DB", true);
      }
      echon("  updated.");

      echon("-> searching calendar segments...");
      $segment = new CalendarSegment();
      $segments = $segment->find("`calendars_id`='".$the_calendar["id"]."'");
      $seg = new CalendarSegment();
      foreach ($segments as $segment) {
         $seg->getFromDB($segment["id"]);
         echon("  found: ". $seg->fields['day']);
      }

      echon("-> deleting the existing calendar segments: '$name'...");
      if (! $dry_run) {
         $segment = new CalendarSegment();
         $segment->deleteByCriteria(["`calendars_id`='".$the_calendar["id"]]);
      } else {
         echon("-> dry-run: delete from DB", true);
      }
      echon("  deleted.");

      echon("-> searching calendar segments...");
      $segment = new CalendarSegment();
      $segments = $segment->find("`calendars_id`='".$the_calendar["id"]."'");
      $seg = new CalendarSegment();
      foreach ($segments as $segment) {
         $seg->getFromDB($segment["id"]);
         echon("  found: ". $seg->fields['day']);
      }


      echon("-> searching calendar holidays...");
      $holiday = new Calendar_Holiday();
      $holidays = $holiday->find("`calendars_id`='".$the_calendar["id"]."'");
      foreach ($holidays as $day) {
         $holiday->getFromDB($day["id"]);
         echon("  found: ". serialize($holiday->fields));
      }

      echon("-> deleting the existing calendar holidays: '$name'...");
      if (! $dry_run) {
         $holiday = new Calendar_Holiday();
         $holiday->deleteByCriteria(["`calendars_id`='".$the_calendar["id"]]);
      } else {
         echon("-> dry-run: delete from DB", true);
      }
      echon("  deleted.");

      echon("-> searching calendar holidays...");
      $holiday = new Calendar_Holiday();
      $holidays = $holiday->find("`calendars_id`='".$the_calendar["id"]."'");
      foreach ($holidays as $day) {
         $holiday->getFromDB($day["id"]);
         echon("  found: ". serialize($holiday->fields));
      }
   } else {
      // Create a new calendar
      echon("-> creating a new calendar: '$name'...");
      if (! $dry_run) {
         $calendar_id = $calendar->add($input);
         if (! $calendar_id) {
            echon(" ***** error when adding a calendar!", true);
            print_r($input);
            continue;
         } else {
            echon(" created.");
         }
      } else {
         echon("-> dry-run: created in DB", true);
      }


      echon("-> searching calendar segments...");
      $segment = new CalendarSegment();
      $segments = $segment->find("`calendars_id`='".$the_calendar["id"]."'");
      $seg = new CalendarSegment();
      foreach ($segments as $segment) {
         $seg->getFromDB($segment["id"]);
         echon("  found: ". $seg->fields['day']);
      }

      echon("-> deleting the existing calendar segments: '$name'...");
      if (! $dry_run) {
         $segment = new CalendarSegment();
         $segment->deleteByCriteria(["`calendars_id`='".$the_calendar["id"]]);
      } else {
         echon("-> dry-run: delete from DB", true);
      }
      echon("  deleted.");

      echon("-> searching calendar segments...");
      $segment = new CalendarSegment();
      $segments = $segment->find("`calendars_id`='".$the_calendar["id"]."'");
      $seg = new CalendarSegment();
      foreach ($segments as $segment) {
         $seg->getFromDB($segment["id"]);
         echon("  found: ". $seg->fields['day']);
      }
   }
}
if ($fh_calendars) {
   fclose($fh_calendars);
}

/*
 * Then, import the holidays
 */
echon("2/ holidays");
echon("-----");
while (($data = fgetcsv($fh_holidays, 0, $DELIMITER, $ENCLOSURE)) !== FALSE) {
   $data[0] = trim($data[0]);
   if (strtolower($data[0]) == 'holiday_name') {
      // File header
      // Expected: "holiday_name;calendar_name;entity_complete_name;is_recursive;comment;begin_date;end_date;is_perpetual"
      if ((strtolower($data[1]) != 'calendar_name')
         or (strtolower($data[2]) != 'entity_complete_name')
         or (strtolower($data[3]) != 'is_recursive')
         or (strtolower($data[4]) != 'comment')
         or (strtolower($data[5]) != 'begin_date')
         or (strtolower($data[6]) != 'end_date')
         or (strtolower($data[7]) != 'is_perpetual')
      ) {
         echon("***** Malformed header!", true);
         echon("File header: " . serialize($data), true);
         die("The '" . $fileName . "_holidays.csv' file does not match the expected format. Giving up!" . PHP_EOL);
      }
      continue;
   }
   // Check fields count
   if (count($data) < 8) {
      // Skip incomplete line...
      echon("***** skipping an empty line: " . serialize($data));
      continue;
   }

   // Clean name field
   $name = trim($data[0]);
   if ($name == '') {
      // Skip empty name...
      echon("***** skipping empty name: " . serialize($data));
      continue;
   }

   // Clean name field
   $calendar_name = trim($data[1]);
   if ($calendar_name == '') {
      // Skip empty calendar name...
      echon("***** skipping empty calendar name: " . serialize($data));
      continue;
   }

   // Clean and check Entity field
   $entity = trim($data[2]);
   $entity_id = -1;
   if ($entity != '') {
      $db_entities = $db_entity->find("`completename`='" . $DB->escape($entity) . "'", '', 1);
      if (count($db_entities) > 0) {
         $found_entity = current($db_entities);
         $entity_id = $found_entity["id"];
      } else {
         echon("***** skipping not found entity: '$name / $entity'!");
         continue;
      }
   } else {
      echon("***** skipping empty entity: " . serialize($data));
      continue;
   }

   // Clean and check is_recursive field
   $is_recursive = trim($data[3]);
   if (!in_array($is_recursive, ["0", "1"])) {
      // Skip invalid data...
      echon("***** skipping empty or invalid is_recursive: '$name / $is_recursive'!");
      continue;
   }

   // Get all other fields
   $is_perpetual = trim($data[7]);
   if (!in_array($is_perpetual, ["0", "1"])) {
      // Skip invalid data...
      echon("***** skipping empty or invalid $is_perpetual: '$name / $is_perpetual'!");
      continue;
   }

   /*
    * Now we have all the fields to create a new holiday
    */
   $input = array(
      'name' => $name,
      'entities_id' => $entity_id,
      'is_recursive' => $is_recursive,
      'comment' => $data[4],
      'begin_date' => $data[5],
      'end_date' => $data[6],
      'is_perpetual' => $is_perpetual
   );

   // To be deleted if begin and end date are empty
   $to_be_deleted = false;
   if (empty($data[4]) && empty($data[5])) {
      $to_be_deleted = true;
   }

   $holiday_id = -1;
   $holiday = new Holiday();
   $holidays = $holiday->find("`name`='" . $DB->escape($name) . "' AND `entities_id`='$entity_id'", '', 1);
   if (count($holidays) > 0) {
      // Update an existing holiday
      $the_holiday = current($holidays);
      $holiday->getFromDB($the_holiday["id"]);
      $input['id'] = $the_holiday["id"];

      if ($to_be_deleted) {
         if ($verbose) {
            echo nl2br("-> begin and end date are empty, deleting an existing holiday...");
         }
         if (!$dry_run) {
            $holiday->deleteFromDB();
         } else {
            echo nl2br("-> dry-run: delete from DB");
         }
         if ($verbose) {
            echo nl2br(" deleted." . PHP_EOL);
         }
      } else {
         echon("-> updating an existing holiday: '$name'...");
         if (!$dry_run) {
            $holiday->update($input);
         } else {
            echon("-> dry-run: updated", true);
         }
         echon("  updated.");
      }
   } else {
      if ($to_be_deleted) {
         echo nl2br("-> do not create the holiday: '$name' because begin and end dates are empty." . PHP_EOL);
      } else {
         // Create a new holiday
         echon("-> creating a new holiday: '$name'...");
         if (!$dry_run) {
            $holiday_id = $holiday->add($input);
            if (!$holiday_id) {
               echon(" ***** error when adding a holiday!", true);
               print_r($input);
               continue;
            } else {
               echon(" created.");
            }
         } else {
            echon("-> dry-run: created in DB", true);
         }
      }
   }

   $calendar_id = -1;
   $calendars = $calendar->find("`name`='" . $DB->escape($calendar_name) . "' AND `entities_id`='$entity_id'", '', 1);
   if (count($calendars) > 0) {
      // Found an existing calendar
      $my_calendar = current($calendars);
      $input['calendars_id'] = $my_calendar["id"];

   }
}
if ($fh_holidays) {
   fclose($fh_holidays);
}

/*
 * And the calendar segments
 */
echon("3/ segments");
echon("-----");
while (($data = fgetcsv($fh_segments, 0, $DELIMITER, $ENCLOSURE)) !== FALSE) {
   $data[0] = trim($data[0]);
   if (strtolower($data[0]) == 'calendar_name') {
      // File header
      // Expected: "calendar_name;entity_complete_name;day;begin;end"
      if ((strtolower($data[1]) != 'entity_complete_name')
         or (strtolower($data[2]) != 'day')
         or (strtolower($data[3]) != 'begin')
         or (strtolower($data[4]) != 'end')){
         echon("***** Malformed header!", true);
         echon("File header: " . serialize($data), true);
         die("The '" . $fileName . "_holidays.csv' file does not match the expected format. Giving up!". PHP_EOL);
      }
      continue;
   }
   // Check fields count
   if (count($data) < 5) {
      // Skip incomplete line...
      echon("***** skipping an empty line: " . serialize($data));
      continue;
   }

   // Clean name field
   $name = trim($data[0]);
   if ($name == '') {
      // Skip empty name...
      echon("***** skipping empty name: " . serialize($data));
      continue;
   }

   // Clean and check Entity field
   $entity = trim($data[1]);
   $entity_id = -1;
   if ($entity != '') {
      $db_entities = $db_entity->find("`completename`='".$DB->escape($entity)."'", '', 1);
      if (count($db_entities) > 0) {
         $found_entity = current($db_entities);
         $entity_id = $found_entity["id"];
      } else {
         echon("***** skipping not found entity: '$name / $entity'!");
         continue;
      }
   } else {
      echon("***** skipping empty entity: " . serialize($data));
      continue;
   }

   // Clean and check day field
   $day = trim($data[2]);
   if (! in_array($day, ["0", "1", "2", "3", "4", "5", "6", "7"])) {
      // Skip invalid data...
      echon("***** skipping empty or invalid day: '$name / $day'!");
      continue;
   }

   $day = $data[2];
   $begin = $data[3];
   $end = $data[4];

   /*
    * Now we have all the fields to create a new segment
    */
   $input = array(
      'entities_id'     => $entity_id,
      'is_recursive'    => $is_recursive,
      'day'             => $day,
      'begin'           => $begin,
      'end'             => $end
   );

   // To be deleted if begin and end date are empty
   $to_be_deleted = false;
   if (empty($data[4]) && empty($data[5])) {
      $to_be_deleted = true;
   }

   $calendar_id = -1;
   $calendars = $calendar->find("`name`='". $DB->escape($name) ."' AND `entities_id`='$entity_id'", '', 1);
   if (count($calendars) > 0) {
      // Found an existing calendar
      $my_calendar = current($calendars);
      $input['calendars_id'] = $my_calendar["id"];

      if ($to_be_deleted) {
         echon("-> begin and end date are empty, deleting an existing segment...");
         if (! $dry_run) {
            $holiday->deleteFromDB();
         } else {
            echon("-> dry-run: delete from DB");
         }
         echon(" deleted." . PHP_EOL);
      } else {
         echon("-> updating an existing calendar segment: '$name', day: $day-$begin-$end...");
         if (! $dry_run) {
            $holiday->update($input);
         } else {
            echon("-> dry-run: update in DB", true);
         }
         echon("  updated.");
      }
   } else {
      echon("Calendar not found!", true);
   }
}
if ($fh_segments) {
   fclose($fh_segments);
}

/*
 * Dump information message to the console according to the verbose mode configuration
 * -----
 */
function echon($message, $force=false) {
   global $verbose, $nl2br;

   if ($verbose or $force) {
      if ($nl2br) {
         echo nl2br($message . PHP_EOL);
      } else {
         echo($message . PHP_EOL);
      }
   }
}
