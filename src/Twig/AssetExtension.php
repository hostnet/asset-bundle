<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Twig;

use Hostnet\Component\Resolver\Config\ConfigInterface;

final class AssetExtension extends \Twig_Extension
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('asset_url', [$this, 'assetUrl']),
        ];
    }

    public function assetUrl(string $name): string
    {
        $asset = $this->config->getOutputFolder(false) . '/' . $name;
        $file  = $this->config->getProjectRoot() . '/' . $this->config->getOutputFolder() . '/' . $name;

        return sprintf('/%s?%s', $asset, filemtime($file));
    }
}
