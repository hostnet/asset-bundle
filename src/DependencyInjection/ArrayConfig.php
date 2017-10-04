<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Component\Resolver\ConfigInterface;

class ArrayConfig implements ConfigInterface
{
    private $is_dev;
    private $cwd;
    private $entry_points;
    private $asset_files;
    private $output_folder;
    private $web_root;
    private $source_root;
    private $cache_dir;

    public function __construct(
        bool $is_dev,
        string $cwd,
        array $entry_points,
        array $asset_files,
        string $output_folder,
        string $web_root,
        string $source_root,
        string $cache_dir
    ) {
        $this->is_dev        = $is_dev;
        $this->cwd           = $cwd;
        $this->entry_points  = $entry_points;
        $this->asset_files   = $asset_files;
        $this->output_folder = $output_folder;
        $this->web_root      = $web_root;
        $this->source_root   = $source_root;
        $this->cache_dir     = $cache_dir;
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
    public function cwd(): string
    {
        return $this->cwd;
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
    public function getOutputFolder(): string
    {
        return $this->output_folder;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebRoot(): string
    {
        return $this->web_root;
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
}
