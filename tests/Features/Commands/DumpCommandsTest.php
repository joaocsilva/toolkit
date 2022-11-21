<?php

declare(strict_types=1);

namespace EcEuropa\Toolkit\Tests\Features\Commands;

use EcEuropa\Toolkit\TaskRunner\Commands\DumpCommands;
use EcEuropa\Toolkit\TaskRunner\Runner;
use EcEuropa\Toolkit\Tests\AbstractTest;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

use function str_contains;

/**
 * Test Toolkit dump commands.
 *
 * @group dump
 */
class DumpCommandsTest extends AbstractTest
{

    /**
     * Data provider for testDump.
     *
     * @return array
     *   An array of test data arrays with assertions.
     */
    public function dataProvider()
    {
        return $this->getFixtureContent('commands/dump.yml');
    }

    /**
     * Test DumpCommands commands.
     *
     * @param string $command
     *   A command.
     * @param array $config
     *   A configuration.
     * @param array $expectations
     *   Tests expected.
     *
     * @dataProvider dataProvider
     */
    public function testDump(string $command, array $config = [], array $expectations = [])
    {
        // Setup configuration file.
        if (!empty($config)) {
            $this->fs->dumpFile($this->getSandboxFilepath('runner.yml'), Yaml::dump($config));
        }

        if (str_contains($command, 'install-dump')) {
            $this->fs->copy(
                $this->getFixtureFilepath('samples/sample-dump.sql.gz'),
                $this->getSandboxFilepath('dump.sql.gz')
            );
        }
        if (str_contains($command, 'download-dump')) {
            $this->fs->copy(
                $this->getFixtureFilepath('samples/sample-mysql-latest.sh1'),
                $this->getSandboxFilepath('mysql-latest.sh1')
            );
        }

        // Run command.
        $input = new StringInput($command . ' --simulate --working-dir=' . $this->getSandboxRoot());
        $output = new BufferedOutput();

        $runner = new Runner($this->getClassLoader(), $input, $output);
        $runner->run();

        // Fetch the output.
        $content = $output->fetch();
//        $this->debugExpectations($content, $expectations);
        // Assert expectations.
        foreach ($expectations as $expectation) {
            $this->assertContainsNotContains($content, $expectation);
        }
    }

    public function testConfigurationFileExists()
    {
        $this->assertFileExists((new DumpCommands())->getConfigurationFile());
    }

}
