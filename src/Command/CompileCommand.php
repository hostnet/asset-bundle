<?php
namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommand extends Command
{
    /**
     * @var PipelineBundler
     */
    private $bundler;

    public function __construct(PipelineBundler $bundler)
    {
        parent::__construct('assets:compile');

        $this->bundler = $bundler;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bundler->execute();
    }
}
