<?php

namespace tests\units;
use GlpiPlugin\Alignak\Tests\CommonTestCase;

class PluginAlignakAlignak extends CommonTestCase {

   public function setUp() {
      parent::setUp();

      // instanciate classes
      $pa = new \PluginAlignakAlignak;
   }

   public function testGetTypeNameSingular() {
      $this
         // création d'une nouvelle instance de la classe à tester
         ->given($this->newTestedInstance)

         ->then
            ->string($this->testedInstance->getTypeName(1))
            ->isIdenticalTo('Alignak instance')
      ;
   }

   public function testGetTypeNamePlural() {
      $this
         ->given($this->newTestedInstance)

         ->then
            ->string($this->testedInstance->getTypeName(0))
            ->isIdenticalTo('Alignak instances')
            ->string($this->testedInstance->getTypeName(2))
            ->isIdenticalTo('Alignak instances')
      ;
   }

   public function testDefineTabs() {
      $this
         ->given($this->newTestedInstance)

         ->then
            ->array($this->testedInstance->defineTabs())
            ->isEqualTo(
               [ "PluginAlignakAlignak\$main" => 'Alignak instance' ]
            )
      ;
   }

   public function testRawSearchOptions() {
      $this
         ->given($this->newTestedInstance)

         ->then
         ->array($this->testedInstance->rawSearchOptions())
         ->isEqualTo(
            [ "PluginAlignakAlignak\$main" => 'Alignak instance' ]
         )
      ;
   }

}
