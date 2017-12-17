<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\RootFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Hostnet\Bundle\AssetBundle\Command\DebugCommand
 */
class DebugCommandTest extends TestCase
{
    private $config;
    private $finder;

    /**
     * @var CompileCommand
     */
    private $compile_command;

    protected function setUp()
    {
        $this->config  = $this->prophesize(ConfigInterface::class);
        $this->finder = $this->prophesize(ImportFinderInterface::class);

        $this->compile_command = new DebugCommand(
            $this->config->reveal(),
            $this->finder->reveal()
        );
    }

    public function testExecute()
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getSourceRoot()->willReturn('src');
        $this->config->getEntryPoints()->willReturn([basename(__FILE__)]);
        $this->config->getAssetFiles()->willReturn([]);

        $output = new BufferedOutput();

        $this->compile_command->run(new ArrayInput([]), $output);

        self::assertStringEqualsFile(__DIR__ . '/expected.output.txt', $output->fetch());
    }

    public function testExecuteWithEntryPoint()
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getSourceRoot()->willReturn('');
        $this->config->getEntryPoints()->willReturn([basename(__FILE__)]);
        $this->config->getAssetFiles()->willReturn([]);
        $this->config->getOutputFolder()->willReturn('dev');

        $file = new File(basename(__FILE__));
        $dep = new File('expected.output.txt');
        $root = new RootFile($file);
        $root->addChild(new Dependency($dep));

        $this->finder->all($file)->willReturn($root);
        $this->finder->all($dep)->willReturn(new RootFile($dep));

        $output = new BufferedOutput();

        $this->compile_command->run(new ArrayInput(['file' => basename(__FILE__)]), $output);

        self::assertStringEqualsFile(__DIR__ . '/expected-entry_point.output.txt', $output->fetch());
    }

    public function testExecuteWithAsset()
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getSourceRoot()->willReturn('');
        $this->config->getEntryPoints()->willReturn([]);
        $this->config->getAssetFiles()->willReturn([basename(__FILE__)]);
        $this->config->getOutputFolder()->willReturn('dev');

        $file = new File(basename(__FILE__));
        $dep = new File('expected.output.txt');
        $root = new RootFile($file);
        $root->addChild(new Dependency($dep));

        $this->finder->all($file)->willReturn($root);
        $this->finder->all($dep)->willReturn(new RootFile($dep));

        $output = new BufferedOutput();

        $this->compile_command->run(new ArrayInput(['file' => basename(__FILE__)]), $output);

        self::assertStringEqualsFile(__DIR__ . '/expected-asset.output.txt', $output->fetch());
    }
}
