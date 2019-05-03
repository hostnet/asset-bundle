<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Command;

use Hostnet\Component\Resolver\Builder\Asset;
use Hostnet\Component\Resolver\Builder\EntryPoint;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends Command
{
    private $config;
    private $finder;

    public function __construct(ConfigInterface $config, ImportFinderInterface $finder)
    {
        parent::__construct('debug:asset');

        $this->config = $config;
        $this->finder = $finder;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Displays asset information.')
            ->addArgument('file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('file')) {
            $this->checkFile($output, $input->getArgument('file'));
        } else {
            $this->printAll($output);
        }
    }

    private function checkFile(OutputInterface $output, string $file): void
    {
        $entry_points = $this->config->getEntryPoints();
        $assets       = $this->config->getAssetFiles();
        $source_dir   = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');
        $output_dir   = $this->config->getOutputFolder();

        $type = 'Unknown';

        foreach ($entry_points as $asset) {
            if ($source_dir . $asset === $file) {
                $type = 'Entry Point';
                break;
            }
        }
        foreach ($assets as $asset) {
            if ($source_dir . $asset === $file) {
                $type = 'Asset';
                break;
            }
        }

        $output->writeln('File info:');
        $output->writeln('  - Root: ' . $file);
        $output->writeln('  - Type: ' . $type);
        $output->writeln('  - Size: ' . $this->filesize($file));
        $output->writeln('');

        $file_obj = new File($file);

        if ($type === 'Entry Point') {
            $entry_points   = new EntryPoint($this->finder->all($file_obj), $this->config->getSplitStrategy());
            $files_to_build = $entry_points->getFilesToBuild($output_dir);
            foreach ($files_to_build as $output_file => $dependencies) {
                $output->writeln('Bundle bundled files: (' . $output_file . ')');

                foreach ($dependencies as $dep) {
                    $output->writeln('  - ' . $dep->getFile()->getName());
                }
            }
        } else {
            $root = new Asset($this->finder->all($file_obj));

            $output->writeln('Bundled files:');

            foreach ($root->getFiles() as $dep) {
                $output->writeln('  - ' . $dep->getFile()->getName());
            }
            if (\count($root->getFiles()) === 0) {
                $output->writeln('  - None');
            }
        }

        $output->writeln('');
        $output->writeln('Dependency tree:');
        $this->printDependencyTree($output, $file_obj);
    }

    private function printDependencyTree(OutputInterface $output, File $file, int $depth = 0): void
    {
        $deps = $this->finder->all($file);

        foreach ($deps->getChildren() as $child) {
            $child_file = $child->getFile();
            $output->writeln(str_repeat('  ', $depth) . '  - ' . $child_file->getName());

            $this->printDependencyTree($output, $child_file, $depth + 1);
        }
    }

    private function filesize(string $file): string
    {
        $bytes  = filesize($this->config->getProjectRoot() . DIRECTORY_SEPARATOR . $file);
        $sizes  = 'BKMGTP';
        $factor = (int) floor((\strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f', $bytes / (1024 ** $factor)) . @$sizes[$factor];
    }

    private function printAll(OutputInterface $output): void
    {
        $entry_points = $this->config->getEntryPoints();
        $assets       = $this->config->getAssetFiles();
        $source_dir   = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

        $output->writeln('Entry points:');

        if (\count($entry_points) === 0) {
            $output->writeln('  - None');
        }

        foreach ($entry_points as $entry_point) {
            $output->writeln('  - ' . $source_dir . $entry_point);
        }

        $output->writeln('');
        $output->writeln('Asset files:');

        if (\count($assets) === 0) {
            $output->writeln('  - None');
        }

        foreach ($assets as $asset) {
            $output->writeln('  - ' . $source_dir . $asset);
        }
    }
}
