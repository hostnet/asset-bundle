<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\SingleProcessRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ArrayConfig implements ConfigInterface
{
    private $is_dev;
    private $project_root;
    private $include_paths;
    private $entry_points;
    private $asset_files;
    private $web_root;
    private $output_folder;
    private $source_root;
    private $cache_dir;
    private $plugins;
    private $enable_unix_socket;
    private $node_js_executable;
    private $logger;
    private $event_dispatcher;

    public function __construct(
        bool $is_dev,
        string $project_root,
        array $include_paths,
        array $entry_points,
        array $asset_files,
        string $web_root,
        string $output_folder,
        string $source_root,
        string $cache_dir,
        bool $enable_unix_socket,
        array $plugins,
        Executable $node_js_executable,
        EventDispatcherInterface $event_dispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->is_dev        = $is_dev;
        $this->project_root  = $project_root;
        $this->include_paths = $include_paths;
        $this->entry_points  = $entry_points;
        $this->asset_files   = $asset_files;
        $this->web_root      = $web_root;
        $this->output_folder = $output_folder;
        $this->source_root   = $source_root;
        $this->cache_dir     = $cache_dir;
        $this->plugins       = $plugins;

        $this->enable_unix_socket = $enable_unix_socket;
        $this->node_js_executable = $node_js_executable;
        $this->event_dispatcher   = $event_dispatcher ?? new EventDispatcher();
        $this->logger             = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function isDev(): bool
    {
        return $this->is_dev;
    }

    /**
     * {@inheritdoc}
     */
    public function getIncludePaths(): array
    {
        return $this->include_paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectRoot(): string
    {
        return $this->project_root;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntryPoints(): array
    {
        return $this->entry_points;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(): array
    {
        return $this->asset_files;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputFolder(bool $include_public_folder = true): string
    {
        if (! $include_public_folder) {
            return $this->output_folder;
        }

        return $this->web_root . DIRECTORY_SEPARATOR . $this->output_folder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceRoot(): string
    {
        return $this->source_root;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->cache_dir;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeJsExecutable(): Executable
    {
        return $this->node_js_executable;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->event_dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): RunnerInterface
    {
        return $this->enable_unix_socket
            ? new UnixSocketRunner($this)
            : new SingleProcessRunner($this);
    }
}
