<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @author    Thierry Bugier
 * @author    Jérémy Moreau
 * @copyright Copyright © 2011 - 2018 Teclib'
 * @license   GPLv3+ http://www.gnu.org/licenses/gpl.txt
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

include ('../../../inc/includes.php');
/*Session::checkRight("entity", UPDATE);

$counter = new PluginAlignakCounter();
if (empty($_REQUEST['counter_id'])) {
   $counter_id = 0;
   $counter->getEmpty();
} else {
   $counter_id = intval($_REQUEST['counter_id']);
   $counter->getFromDB($counter_id);
}
$counter->showForm($counter_id); */
// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('alignak') || !$plugin->isActivated('alignak')) {
   Html::displayNotFoundError();
}

// Check if current user have the appropriate right
Session::checkRight("plugin_alignak_counters", UPDATE);

Html::header(
   __('Counter templates', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'counter');

Search::show('PluginAlignakCounter');

Html::footer();
