<?php
namespace Civi\Cv;

use Symfony\Component\Console\Tester\CommandTester;

class CivilTestCase extends \PHPUnit\Framework\TestCase {

  use CvTestTrait;

  /**
   * @var string
   */
  private $originalCwd;

  /**
   * Path to the "cv" binary.
   *
   * @var string
   */
  protected $cv;

  public function setUp(): void {
    $this->originalCwd = getcwd();
    chdir($this->getExampleDir());
    $this->cv = dirname(__DIR__) . '/bin/cv';
  }

  public function tearDown(): void {
    chdir($this->originalCwd);
  }

  public function getExampleDir() {
    $dir = getenv('CV_TEST_BUILD');
    if (empty($dir)) {
      throw new \RuntimeException('Environment variable CV_TEST_BUILD must point to a civicrm-cms build');
    }
    return $dir;
  }

  /**
   * Create a helper for executing command-tests in our application.
   *
   * @param array $args must include key "command"
   * @return \Symfony\Component\Console\Tester\CommandTester
   */
  public function createCommandTester($args) {
    if (!isset($args['command'])) {
      throw new \RuntimeException("Missing mandatory argument: command");
    }
    $application = new Application();
    $command = $application->find($args['command']);
    $commandTester = new CommandTester($command);
    $commandTester->execute($args);
    return $commandTester;
  }

}
