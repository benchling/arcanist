<?php

/**
 * Unit test engine for TypeScript and TSLint. Both of these work around the
 * fact that the lint system requires each file to be checked separately, but
 * TypeScript typechecking has cross-file dependencies. We run TSLint as both a
 * lint rule and a test suite since the lint system allows autofixing and the
 * test suite covers rules that require type info.
 */
final class TypeScriptTestEngine extends ArcanistUnitTestEngine {
  public function getEngineConfigurationName() {
    return 'typescript';
  }

  protected function supportsRunAllTests() {
    return true;
  }

  public function run() {
    function isTypeScript($path) {
      return preg_match('/^.*\.tsx?$/', $path);
    }
    $typescriptPaths = array_filter($this->getPaths(), 'isTypeScript');
    if (empty($typescriptPaths)) {
      // Skip all TypeScript checking if no TypeScript files changed.
      return array();
    }
    $root = $this->getWorkingCopy()->getProjectRoot();

    $tsc = Filesystem::resolvePath('./node_modules/.bin/tsc', $root);
    $time_start_seconds = microtime(true);
    exec(sprintf("%s --project %s --noEmit", $tsc, $root), $tscOutput, $tscReturnVar);
    $time_taken_seconds = microtime(true) - $time_start_seconds;

    $tscResult = new ArcanistUnitTestResult();
    $tscResult->setName('TypeScript type check');
    if ($tscReturnVar) {
      $tscResult->setResult(ArcanistUnitTestResult::RESULT_FAIL);
    } else {
      $tscResult->setResult(ArcanistUnitTestResult::RESULT_PASS);
    }
    $tscResult->setUserData(join("\n", $tscOutput));
    $tscResult->setDuration($time_taken_seconds);

    $tslint = Filesystem::resolvePath('./node_modules/.bin/tslint', $root);
    $time_start_seconds = microtime(true);
    exec(sprintf("%s --project %s %s", $tslint, $root, implode(" ", $typescriptPaths)), $tslintOutput, $tslintReturnVar);
    $time_taken_seconds = microtime(true) - $time_start_seconds;

    $tslintResult = new ArcanistUnitTestResult();
    $tslintResult->setName('TSLint with type info');
    if ($tslintReturnVar) {
      $tslintResult->setResult(ArcanistUnitTestResult::RESULT_FAIL);
    } else {
      $tslintResult->setResult(ArcanistUnitTestResult::RESULT_PASS);
    }
    $tslintResult->setUserData(join("\n", $tslintOutput));
    $tslintResult->setDuration($time_taken_seconds);
    
    return array($tscResult, $tslintResult);
  }

  public function shouldEchoTestResults() {
    return false;
  }
}
