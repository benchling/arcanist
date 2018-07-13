<?php

final class BenchlingCheckTestEngine extends ArcanistUnitTestEngine {
  public function getEngineConfigurationName() {
    return 'benchling-check';
  }

  protected function supportsRunAllTests() {
    return true;
  }

  public function run() {
    function quote($s) {
      return sprintf('"%s"', $s);
    }

    $root = $this->getWorkingCopy()->getProjectRoot();

    $binDev = Filesystem::resolvePath('./bin/dev', $root);
    $timeStartSeconds = microtime(true);

    $quotedPaths = implode(" ", array_map("quote", $this->getPaths()));
    // Pass --arc in case the script wants to behave differently when run from arc.
    passthru(sprintf("%s check --arc %s", $binDev, $quotedPaths),$checkReturnVar);
    $time_taken_seconds = microtime(true) - $timeStartSeconds;

    $checkResult = new ArcanistUnitTestResult();
    $checkResult->setName('bin/dev check');
    if ($checkReturnVar) {
      $checkResult->setResult(ArcanistUnitTestResult::RESULT_FAIL);
    } else {
      $checkResult->setResult(ArcanistUnitTestResult::RESULT_PASS);
    }
    $checkResult->setDuration($time_taken_seconds);

    return array($checkResult);
  }

  public function shouldEchoTestResults() {
    return false;
  }
}
