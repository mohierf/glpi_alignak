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

$paComputerCountersTemplate = new PluginAlignakComputerCountersTemplate();

/*
// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", READ);

if (isset($_GET["id"])) {
   $paComputerCountersTemplate->showForm($_GET['id'], -1, ['canedit'=>PluginKiosksDashboard::canUpdate(), 'colspan'=>4]);
} else {
   $paComputerCountersTemplate->showForm(-1);
}
*/

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", UPDATE);

if (isset($_POST["copy"])) {
   $paComputerCountersTemplate->showForm(-1, -1, ['canedit'=>PluginAlignakComputerCountersTemplate::canUpdate(), 'colspan'=>4], $_POST);
   Html::footer();
   exit;
} else if (isset ($_POST["update"])) {
   $paComputerCountersTemplate->update($_POST);
   Html::back();
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", CREATE);

if (isset ($_POST["add"])) {
   $paComputerCountersTemplate->add($_POST);
   Html::back();
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", DELETE);

if (isset ($_POST["delete"])) {
   $paComputerCountersTemplate->delete($_POST);
   Html::back();
}

Html::footer();

/*
if ($_POST && isset($_POST['_glpi_csrf_token']) && isset($_POST['items_id'])) {
   // Check that a template identifier has been provided
   if (!isset($_POST['template_id']) or empty($_POST['template_id']) or $_POST['template_id'] == -1) {
      Html::displayErrorAndDie('Please specify a template');
   }

   // Load the Computer that need association with that given template
   # todo: ? what for ?
   $computerCountersTemplate = new PluginAlignakComputerCountersTemplate();
   if (! $this->getFromDBByCrit(["itemtype" => 'Computer', "items_id" => $item->fields['id']])) {

   $ret = $computerCountersTemplate->find( "items_id = ".$_POST['computer_id']);
   $computerCountersTemplate->fields['computer_id'] = $_POST['computer_id'];
   $computerCountersTemplate->fields['template_id'] = $_POST['template_id'];
   foreach ($ret as $r) {
      $id = $r['id'];
   }

   if ($ret != null) {
      $computerCountersTemplate->fields['id'] = $id;
      $updates = ['template_id'];
      $computerCountersTemplate->updateInDB( $updates);
   } else {
      // Save the new computerCounterTemplate to the DataBase
      $computerCountersTemplate->addToDB();
   }

   // Redirect the user to the Computer Page
   $url = explode("?", $_SERVER['HTTP_REFERER']);
   Html::redirect($url[0] . "?id=" . $_POST['computer_id']);
}
*/