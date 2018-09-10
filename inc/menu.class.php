<?php
class PluginAlignakMenu extends CommonGLPI {
   static $rightname = 'entity';

   static function getMenuName() {
      return __("Alignak", "alignak");
   }

   static function getMenuContent() {

      if (!Session::haveRight('entity', READ)) {
         return;
      }

      $front_fields = "/plugins/alignak/front";
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = "$front_fields/alignak.php";

      $itemtypes = ['PluginAlignakAlignak' => 'alignakalignak'];

      foreach ($itemtypes as $itemtype => $option) {
         $menu['options'][$option]['title']           = $itemtype::getTypeName(2);
         $menu['options'][$option]['page']            = $itemtype::getSearchURL(false);
         $menu['options'][$option]['links']['search'] = $itemtype::getSearchURL(false);
         if ($itemtype::canCreate()) {
            $menu['options'][$option]['links']['add'] = $itemtype::getFormURL(false);
         }

      }
      return $menu;
   }


}