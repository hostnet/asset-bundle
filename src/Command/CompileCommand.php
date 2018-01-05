<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\BufferingLogger;

final class CompileCommand extends Command
{
    public const EXIT_MESSAGE = 'Compile command complete';

    /**
     * @var PipelineBundler
     */
    private $bundler;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PipelineBundler $bundler, ConfigInterface $config, BufferingLogger $logger)
    {
        parent::__construct('assets:compile');

        $this->bundler = $bundler;
        $this->config  = $config;
        $this->logger  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reader = new FileReader($this->config->getProjectRoot());
        $writer = new FileWriter($this->config->getEventDispatcher(), $this->config->getProjectRoot());

        $start = microtime(true);

        $this->bundler->execute($reader, $writer);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->writeln('Time: ' . round((microtime(true) - $start) * 1000) . 'ms');
            $output->writeln('');

            // Output the logs.
            foreach ($this->logger->cleanLogs() as $line) {
                if ($line[0] === 'debug' && $output->getVerbosity() < OutputInterface::VERBOSITY_DEBUG) {
                    continue;
                }

                $output->writeln($this->interpolate($line[1], $line[2]));
            }
        }

        // Write this exit message to denote that we are done
        // Is used in a functional way to prevent defunct processes
        // https://github.com/symfony/symfony/issues/12097#issuecomment-343145050
        $output->writeln(self::EXIT_MESSAGE);
    }

    private function interpolate($message, array $context)
    {
        // Build a replacement array with braces around the context keys.
        $replace = [];
        foreach ($context as $key => $val) {
            if (! is_array($val) && (! is_object($val) || method_exists($val, '__toString'))) {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // Interpolate replacement values into the message and return the result.
        return strtr($message, $replace);
    }
}
