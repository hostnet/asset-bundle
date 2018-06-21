<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Report\ConsoleLoggingReporter;
use Hostnet\Component\Resolver\Report\ConsoleReporter;
use Hostnet\Component\Resolver\Report\Helper\FileSizeHelper;
use Hostnet\Component\Resolver\Report\NullReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommand extends Command
{
    public const EXIT_MESSAGE = 'Compile command complete';

    private $config;
    private $bundler;
    private $build_config;

    public function __construct(ConfigInterface $config, BundlerInterface $bundler, BuildConfig $build_config)
    {
        parent::__construct('assets:compile');

        $this->config       = $config;
        $this->bundler      = $bundler;
        $this->build_config = $build_config;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_DEBUG:
                $reporter = new ConsoleReporter($this->config, new FileSizeHelper(), true);
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $reporter = new ConsoleReporter($this->config, new FileSizeHelper());
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $reporter = new ConsoleLoggingReporter($this->config, $output, new FileSizeHelper());
                break;
            default:
                $reporter = new NullReporter();
        }

        $this->config->replaceReporter($reporter);

        $start = microtime(true);

        $this->bundler->bundle($this->build_config);

        $end = microtime(true);

        if ($reporter instanceof ConsoleReporter) {
            $reporter->printReport($output);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Total time: ' . round(($end - $start) * 1000) . "ms\n");
        }

        // Write this exit message to denote that we are done
        // Is used in a functional way to prevent defunct processes
        // https://github.com/symfony/symfony/issues/12097#issuecomment-343145050
        $output->writeln(self::EXIT_MESSAGE);
    }
}
