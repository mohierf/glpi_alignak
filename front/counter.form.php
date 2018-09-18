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
include ('../../../inc/includes.php');

if ($_POST && isset($_POST['save']) && isset($_POST['id'])) {
   // Check that a name has been passed
   if (!isset($_POST['name']) or empty($_POST['name'])) {
      Html::displayErrorAndDie('Please specified a counter name');
   }
   if (!isset($_POST['template_id']) or empty($_POST['template_id']) or $_POST['template_id'] == -1) {
      Html::displayErrorAndDie('Please specify a template for that counter');
   }

   $counter = new PluginAlignakCounter();
   $ret = $counter->getFromDB($_POST['id']);
   if (! $ret) { // Save the new counter to the DataBase
      if ($counter->add($_POST)) {
         Session::addMessageAfterRedirect(__('The counter has been successfully added!', 'alignak'), true, INFO);
      } else {
         Session::addMessageAfterRedirect(__('An error occured adding a counter!', 'alignak'), true, ERROR);
      }
   } else {    // Update counter to the DataBase
      if ($counter->update($_POST)) {
         Session::addMessageAfterRedirect(__('The counter has been successfully updated!', 'alignak'), true, INFO);
      } else {
         Session::addMessageAfterRedirect(__('An error occured Updating a counter!', 'alignak'), true, ERROR);
      }
   }

   // Redirect the user to the Counters Page
   Html::redirect( $CFG_GLPI['root_doc']."/plugins/alignak/front/counter.php");
} else if (isset($_POST["delete_counter"])) {
   // Delete a Counter
   Session::checkRight("entity", UPDATE);
   $counter = new PluginAlignakCounter();
   $counter->getFromDB($_POST['id']);
   $counter->delete($_POST);
} else {
   $counter = new PluginAlignakCounter();
   Html::header(__('Counter'), '', "tools", "pluginalignak", "config");

   $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : -1;

   $counter->display($_GET);
   Html::footer();
}
