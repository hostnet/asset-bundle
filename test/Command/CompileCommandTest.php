<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\Command\CompileCommand
 */
class CompileCommandTest extends TestCase
{
    private $bundler;
    private $config;

    /**
     * @var CompileCommand
     */
    private $compile_command;

    protected function setUp()
    {
        $this->bundler = $this->prophesize(PipelineBundler::class);
        $this->config  = $this->prophesize(ConfigInterface::class);

        $this->compile_command = new CompileCommand(
            $this->bundler->reveal(),
            $this->config->reveal()
        );
    }

    public function testExecute()
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);

        $output = $this->prophesize(OutputInterface::class);

        $this->bundler
            ->execute(Argument::type(ReaderInterface::class), Argument::type(WriterInterface::class))
            ->shouldBeCalled()
            ->will(function () use ($output) {
                $output->writeln(CompileCommand::EXIT_MESSAGE)->shouldBeCalled();
            });

        $this->compile_command->run(new ArrayInput([]), $output->reveal());
    }
}
