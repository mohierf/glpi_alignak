<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

require_once 'vendor/autoload.php';

class RoboFile extends Glpi\Tools\RoboFile
{
   protected $csignore = ['/lib/', '/scripts/', '/vendor/'];
   //Own plugin's robo stuff
}
