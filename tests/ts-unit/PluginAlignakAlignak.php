<?php

namespace tests\units;
use GlpiPlugin\Alignak\Tests\CommonTestCase;

class PluginAlignakAlignak extends \AlignakDbTestCase {

   public function testGetTypeName() {
      $this->string(\PluginAlignakAlignak::getTypeName())->isIdenticalTo('Alignak');
   }

   public function testGetTypeName2() {
      $this->string(\PluginAlignakAlignak::getTypeName())->isIdenticalTo('AlignakXxx');
   }

   /*
   public function testGetSearchOptions() {
      $container = new \PluginAlignakConfig();
      $this
         ->given($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getSearchOptions())
               ->hasSize(8);
   }

   public function testNewConfig() {
      $container = new \PluginAlignakConfig();

      $data = [
         'label'     => '_container_label1',
         'type'      => 'tab',
         'is_active' => '1',
         'itemtypes' => ["Computer", "User"]
      ];

      $newid = $container->add($data);
      $this->integer($newid)->isGreaterThan(0);

      $this->boolean(class_exists('PluginFieldsComputercontainerlabel1'))->isTrue();
   }
   */
}
