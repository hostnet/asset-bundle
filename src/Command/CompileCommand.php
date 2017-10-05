<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommand extends Command
{
    /**
     * @var PipelineBundler
     */
    private $bundler;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(PipelineBundler $bundler, ConfigInterface $config)
    {
        parent::__construct('assets:compile');

        $this->bundler = $bundler;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reader = new FileReader($this->config->cwd());
        $writer = new FileWriter($this->config->cwd());

        $this->bundler->execute($reader, $writer);
    }
}
