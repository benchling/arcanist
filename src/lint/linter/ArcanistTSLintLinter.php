<?php
/*

Taken from https://github.com/material-foundation/arc-tslint/blob/develop/lint/linter/TypescriptLinter.php

Modifications:
* Add autofixing when the error has exactly one fix.
* All problems should be marked as errors, not warnings.
* Use -> syntax to be consistent with ArcanistESLintLinter.

Original copyright notice:

 Copyright 2016-present The Material Motion Authors. All Rights Reserved.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

final class ArcanistTSLintLinter extends ArcanistExternalLinter {
  private $project = null;

  public function getInfoName() {
    return 'tslint';
  }

  public function getInfoURI() {
    return '';
  }

  public function getInfoDescription() {
    return pht('Use tslint for processing specified files.');
  }

  public function getLinterName() {
    return 'tslint';
  }

  public function getLinterConfigurationName() {
    return 'tslint';
  }

  public function getDefaultBinary() {
    return 'tslint';
  }

  public function getInstallInstructions() {
    return pht('Install tslint with `npm install tslint typescript -g`');
  }

  public function shouldExpectCommandErrors() {
    return false;
  }

  public function getVersion() {
    list($stdout) = execx('%C --version', $this->getExecutableCommand());

    $matches = array();
    $regex = '/(?P<version>\d+\.\d+\.\d+)/';
    if (preg_match($regex, $stdout, $matches)) {
      return $matches['version'];
    } else {
      return false;
    }
  }

  protected function getMandatoryFlags() {
    $flags = array(
      '--force',
      '--format',
      'json'
    );
    if ($this->project) {
      array_push($flags, '--project', $this->project);
    }
    return $flags;
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'tslint.project' => array(
        'type' => 'optional string',
        'help' => pht(
          'The path to your tsconfig.json file. Will be provided as --project <path> to tslint.'),
      ),
    );
    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'tslint.project':
        $this->project = $value;
        return;
      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $code = $this->engine->loadData($path);
    $output = json_decode($stdout);
    $messages = array();
    foreach ($output as $warning) {
      $message = new ArcanistLintMessage();
      $message->setPath($path);
      $message->setLine($warning->startPosition->line + 1);
      $message->setChar($warning->startPosition->character + 1);
      $message->setCode($warning->ruleName);
      $message->setSeverity($this->getLintMessageSeverity($warning->ruleName));
      $message->setName('tslint violation');
      $message->setDescription($warning->failure);

      if (array_key_exists('fix', $warning) && count($warning->fix) == 1) {
        $replaceStart = $warning->fix[0]->innerStart;
        $replaceLen = $warning->fix[0]->innerLength;
        $message->setOriginalText(substr($code, $replaceStart, $replaceLen));
        $message->setReplacementText($warning->fix[0]->innerText);

        // Override line and column, since the patcher expects that to be the
        // start of the replacement.
        list($line, $char) =
          $this->engine->getLineAndCharFromOffset($path, $replaceStart);
        $message->setLine($line + 1);
        $message->setChar($char + 1);
      }
      $messages[] = $message;
    }
    return $messages;
  }
}