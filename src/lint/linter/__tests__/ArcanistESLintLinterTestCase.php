<?php

final class ArcanistESLintLinterTestCase
  extends ArcanistExternalLinterTestCase {

  public function testLinter() {
    $linter = new ArcanistESLintLinter();
    $linter->setLinterConfigurationValue(
      'eslint.eslintconfig',
      dirname(__FILE__).'/eslint/.eslintrc');
    $this->executeTestsInDirectory(dirname(__FILE__).'/eslint/', $linter);
  }

}
