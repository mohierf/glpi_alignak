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
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ('../../../inc/includes.php');

Session::checkRight('plugin_alignak_alignak', READ);

$object = new PluginAlignakComputer();
if (isset($_POST['add'])) {
   // Check CREATE ACL
   Session::checkRight('plugin_alignak_alignak', CREATE);
   $object->add($_POST);
   Html::back();
} else if (isset($_POST['update'])) {
   // Check UPDATE ACL
   Session::checkRight('plugin_alignak_alignak', UPDATE);
   // Do object update
   $object->update($_POST);
   // Redirect to object form
   Html::back();
} else if (isset($_POST['delete'])) {
   // Check DELETE ACL
   Session::checkRight('plugin_alignak_alignak', DELETE);
   // Put object in dustbin
   $object->delete($_POST);
   // Redirect to objects list
   $object->redirectToList();
} else if (isset($_POST['purge'])) {
   // Check PURGE ACL
   Session::checkRight('plugin_alignak_alignak', PURGE);
   // Do object purge
   $object->delete($_POST, 1);
   // Redirect to objects list
   $object->redirectToList();
}

Html::header(
   __('Monitored host', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'alignak_computer');

// Default is to display the object
$with_template = (isset($_GET['withtemplate']) ? $_GET['withtemplate'] : 0);

if (isset($_GET["id"])) {
   $object->display([
      'id' => $_GET['id'],
      'canedit' => PluginAlignakComputer::canUpdate(),
      'withtemplate' => $with_template]);
} else {
   $object->showForm(-1);
}

Html::footer();
