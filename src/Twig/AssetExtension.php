<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Twig;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('asset_url', [$this, 'assetUrl']),
        ];
    }

    public function assetUrl(string $name): string
    {
        $asset = $this->config->getOutputFolder(false) . '/' . $name;
        $file  = $this->config->getProjectRoot() . '/' . $this->config->getOutputFolder() . '/' . $name;

        return sprintf('/%s?%s', $asset, filemtime($file));
    }
}
