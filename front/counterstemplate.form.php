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

Html::header(
   __('Counter templates', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'counters_template');

PluginAlignakToolbox::log("Counters template form: ". serialize($_POST));

$paCountersTemplate = new PluginAlignakCountersTemplate();
if (isset ($_POST["add"])) {
   if ($paCountersTemplate->alreadyExistCountersTemplateForThatEntity($_POST['entities_id'])) {
      Html::displayErrorAndDie('A counters template for that entity already exists');
   } else {
      $paCountersTemplate->add($_POST);
      $paCountersTemplate->redirectToList();
   }
} else if (isset ($_POST["update"])) {
   $paCountersTemplate->update($_POST);
   Html::back();
} else if (isset ($_POST["purge"])) {
   $paCountersTemplate->delete($_POST);
   $paCountersTemplate->redirectToList();
}

if (isset($_GET["id"])) {
   $paCountersTemplate->showForm($_GET['id'], -1, [ 'canedit'=>PluginAlignakCountersTemplate::canUpdate(), 'colspan'=>4 ]);
} else {
   $paCountersTemplate->showForm();
}

Html::footer();
