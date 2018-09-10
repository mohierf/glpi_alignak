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
   @co-author David Durieux
   @copyright Copyright (c) 2018 Alignak team
   @license   AGPLv3 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://alignak.net/
   @link      http://alignak.net/
   @since     2018

   ------------------------------------------------------------------------
 */



include ('../../../inc/includes.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = 0;
}
if (!isset($_GET["preconfig"])) {
   $_GET["preconfig"] = -1;
}

$config = new PluginAlignakConfig();
$model  = new PluginManufacturersimportsModel();

if (isset($_POST["add"])) {
   Session::checkRight("plugin_alignak", CREATE);
   $config->add($_POST);
   Html::back();

} else if (isset($_POST["update"])) {

   Session::checkRight("plugin_alignak", UPDATE);
   $config->update($_POST);
   Html::back();

} else if (isset($_POST["delete"])) {

   Session::checkRight("plugin_alignak", PURGE);
   $config->delete($_POST, true);
   Html::redirect("./config.form.php");

} else if (isset($_POST["update_model"])) {
   Session::checkRight("plugin_alignak", UPDATE);
   $model->addModel($_POST);
   Html::back();

} else if (isset($_POST["delete_model"])) {
   Session::checkRight("plugin_alignak", UPDATE);
   $model->delete($_POST);
   Html::back();

} else {

   Html::header(__('Setup'), '', "tools", "pluginalignakmenu", "config");

   $config->checkGlobal(READ);
   $config->display($_GET);
   Html::footer();
}
