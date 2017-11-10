<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\Command\CompileCommandRunner
 */
class CompileCommandRunnerTest extends TestCase
{
    /**
     * @dataProvider runCommandProvider
     */
    public function testRunCommand(int $verbosity, string $expected)
    {
        $between = ' assets:compile --env=prod';
        $this->expectOutputString('> ' . CompileCommand::EXIT_MESSAGE . $between . PHP_EOL);
        CompileCommandRunner::runCommand('echo ' . CompileCommand::EXIT_MESSAGE);
    }

    public function runCommandProvider()
    {
        return [
            [OutputInterface::VERBOSITY_NORMAL, ''],
            [OutputInterface::VERBOSITY_VERBOSE, '-v'],
            [OutputInterface::VERBOSITY_VERY_VERBOSE, '-vv'],
            [OutputInterface::VERBOSITY_DEBUG, '-vvv'],
        ];
    }
}
