<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Report\ConsoleLoggingReporter;
use Hostnet\Component\Resolver\Report\ConsoleReporter;
use Hostnet\Component\Resolver\Report\NullReporter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\Command\CompileCommand
 */
class CompileCommandTest extends TestCase
{
    private $bundler;
    private $config;
    private $build_config;

    /**
     * @var CompileCommand
     */
    private $compile_command;

    protected function setUp(): void
    {
        $this->config       = $this->prophesize(ConfigInterface::class);
        $this->bundler      = $this->prophesize(BundlerInterface::class);
        $this->build_config = $this->prophesize(BuildConfig::class);

        $this->compile_command = new CompileCommand(
            $this->config->reveal(),
            $this->bundler->reveal(),
            $this->build_config->reveal()
        );
    }

    public function testExecute(): void
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->replaceReporter(Argument::type(NullReporter::class))->willReturn(new NullReporter());

        $output = $this->prophesize(OutputInterface::class);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->writeln(CompileCommand::EXIT_MESSAGE)->shouldBeCalled();

        $this->bundler->bundle($this->build_config)->shouldBeCalled();

        $this->compile_command->run(new ArrayInput([]), $output->reveal());
    }

    public function testExecuteVerbose(): void
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->replaceReporter(Argument::type(ConsoleLoggingReporter::class))->willReturn(new NullReporter());

        $output = $this->prophesize(OutputInterface::class);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(CompileCommand::EXIT_MESSAGE)->shouldBeCalled();
        $output->writeln(Argument::that(function (string $v) {
            return preg_match('/Total time: \dms/i', $v);
        }))->shouldBeCalled();

        $this->bundler->bundle($this->build_config)->shouldBeCalled();

        $this->compile_command->run(new ArrayInput([]), $output->reveal());
    }

    public function testExecuteVeryVerbose(): void
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->replaceReporter(Argument::type(ConsoleReporter::class))->willReturn(new NullReporter());

        $formatter = $this->prophesize(OutputFormatterInterface::class);

        $output = $this->prophesize(OutputInterface::class);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->getFormatter()->willReturn($formatter);
        $output->writeln(CompileCommand::EXIT_MESSAGE)->shouldBeCalled();
        $output->writeln('')->shouldBeCalled();
        $output->writeln(' Asset   Size    Status ')->shouldBeCalled();
        $output->writeln(Argument::that(function (string $v) {
            return preg_match('/Total time: \d+ms/i', $v);
        }))->shouldBeCalled();

        $this->bundler->bundle($this->build_config)->shouldBeCalled();

        $this->compile_command->run(new ArrayInput([]), $output->reveal());
    }
}
