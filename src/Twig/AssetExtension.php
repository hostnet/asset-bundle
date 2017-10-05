<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */

namespace Hostnet\Bundle\AssetBundle\Twig;

use Hostnet\Component\Resolver\ConfigInterface;

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
            new \Twig_Function('asset_url', [$this, 'assetUrl'])
        ];
    }

    public function assetUrl(string $name): string
    {
        return sprintf('/%s/%s', $this->config->getOutputFolder(), $name);
    }
}
