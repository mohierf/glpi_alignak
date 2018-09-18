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

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", READ);

if (isset($_GET["id"])) {
   $paComputerCountersTemplate->showForm($_GET['id'], -1, ['canedit'=>PluginAlignakComputerCountersTemplate::canUpdate(), 'colspan'=>4]);
} else {
   $paComputerCountersTemplate->showForm(-1);
}

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