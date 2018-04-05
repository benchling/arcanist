<?php

final class TypeScriptTestEngine extends ArcanistUnitTestEngine {
  public function getEngineConfigurationName() {
    return 'typescript';
  }

  protected function supportsRunAllTests() {
    return true;
  }

  public function run() {
    $root = $this->getWorkingCopy()->getProjectRoot();
    $tsc = Filesystem::resolvePath('./node_modules/.bin/tsc', $root);
    $time_start_seconds = microtime(true);
    exec(sprintf("%s --project %s --noEmit", $tsc, $root), $output, $return_var);
    $time_taken_seconds = microtime(true) - $time_start_seconds;

    $result = new ArcanistUnitTestResult();
    $result->setName('TypeScript type check');
    if ($return_var) {
      $result->setResult(ArcanistUnitTestResult::RESULT_FAIL);
    } else {
      $result->setResult(ArcanistUnitTestResult::RESULT_PASS);
    }
    $result->setUserData(join('\n', $output));
    $result->setDuration($time_taken_seconds);
    return array($result);
  }

  public function shouldEchoTestResults() {
    return false;
  }
}
