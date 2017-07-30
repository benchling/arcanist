<?php

final class ArcanistESLintLinter extends ArcanistExternalLinter {

  protected $eslintenv;
  protected $eslintconfig;

  public function __construct() {
    // This sets the min supported version of this
    // linter. If you have set it in your .arcconfig or
    // .arclint file it will overwrite this. However if
    // the value is set lower it will cause the linter
    // to break.
    $this->setVersionRequirement('>=v1.0.0');
  }

  public function getInfoName() {
    return 'ESLint';
  }

  public function getInfoURI() {
    return 'https://www.eslint.org';
  }

  public function getInfoDescription() {
    return pht('ESLint is a linter for JavaScript source files.');
  }

  public function getVersion() {
    $output = exec('./node_modules/.bin/eslint --version');

    if (strpos($output, 'command not found') !== false) {
      return false;
    }

    return $output;
  }

  public function getLinterName() {
    return 'ESLINT';
  }

  public function getLinterConfigurationName() {
    return 'eslint';
  }

  public function getDefaultBinary() {
    return './node_modules/.bin/eslint';
  }

  public function getInstallInstructions() {
    return pht('Install ESLint using `%s`.', 'npm install -g eslint');
  }

  public function getUpgradeInstructions() {
    return pht('Upgrade ESLint using `%s`.', 'npm update -g eslint');
  }

  protected function getMandatoryFlags() {
    $options = array();

    $options[] = '--format=json';

    if ($this->eslintenv) {
      $options[] = '--env='.$this->eslintenv;
    }

    if ($this->eslintconfig) {
      $options[] = '--config='.$this->eslintconfig;
    }

    return $options;
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'eslint.eslintenv' => array(
        'type' => 'optional string',
        'help' => pht('enables specific environments.'),
      ),
      'eslint.eslintconfig' => array(
        'type' => 'optional string',
        'help' => pht('config file to use the default is .eslint.'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {

    switch ($key) {
      case 'eslint.eslintenv':
        $this->eslintenv = $value;
        return;
      case 'eslint.eslintconfig':
        $this->eslintconfig = $value;
        return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  protected function canCustomizeLintSeverities() {
    return true;
  }

  protected function getDefaultMessageSeverity($code) {
    // since severity is provided in the output, here
    // we simply output `NULL` so the output result could
    // be used
    return NULL;
  }

  protected function getESLintMessageSeverity($code, $outputtedSeverity) {
    // allow overwrite through config
    $severityWithCode = $this->getLintMessageSeverity($code);

    if (!is_null($severityWithCode)) {
      return $severityWithCode;
    }

    // did not overwrite, output the original severity
    return $outputtedSeverity === 2 ?
      ArcanistLintSeverity::SEVERITY_ERROR :
      ArcanistLintSeverity::SEVERITY_WARNING;
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $fileResults = json_decode($stdout);
    $code = $this->engine->loadData($path);

    $messages = array();
    foreach ($fileResults as $fileResult) {
      foreach ($fileResult->messages as $eslintMessage) {
        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine($eslintMessage->line);
        $message->setChar($eslintMessage->column);
        $message->setCode($eslintMessage->ruleId);
        $message->setName($this->getLinterName());
        $message->setDescription($eslintMessage->message);
        $message->setSeverity(
          $this->getESLintMessageSeverity(
            $eslintMessage->ruleId, $eslintMessage->severity
          )
        );

        if (array_key_exists('fix', $eslintMessage)) {
          $replaceRange = $eslintMessage->fix->range;
          $replaceStart = $replaceRange[0];
          $replaceLen = $replaceRange[1] - $replaceRange[0];
          $message->setOriginalText(substr($code, $replaceStart, $replaceLen));
          $message->setReplacementText($eslintMessage->fix->text);

          // Override line and column, since the patcher expects that to be the
          // start of the replacement.
          list($line, $char) =
            $this->engine->getLineAndCharFromOffset($path, $replaceStart);
          $message->setLine($line + 1);
          $message->setChar($char + 1);
        }

        $messages[] = $message;
      }
    }
    return $messages;
  }
}
