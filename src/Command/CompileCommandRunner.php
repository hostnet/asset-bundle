<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommandRunner
{
    /**
     * Private by design because this is class only has static members
     */
    private function __construct()
    {
    }

    public static function runCommand(
        string $console = 'bin/console',
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ) {
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_DEBUG:
                $vvv = ' -vvv';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $vvv = ' -vv';
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $vvv = ' -v';
                break;
            default:
                $vvv = '';
        }
        $cmd = $console . ' assets:compile --env=prod' . $vvv;

        $resource = proc_open($cmd, [1 => ['pipe', 'w']], $pipes);

        if (! is_resource($resource)) {
            throw new \RuntimeException('Expected proc_open to create a resource');
        }
        // $pipes now looks like this:
        // 1 => readable handle connected to child stdout

        while ($line = fgets($pipes[1])) {
            if ($line === CompileCommand::EXIT_MESSAGE . PHP_EOL) {
                break;
            }
            echo '> ' . $line;
        }

        // First close pipe to prevent deadlock
        fclose($pipes[1]);
        proc_terminate($resource);
    }
}
