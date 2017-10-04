<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */

namespace Hostnet\Bundle\AssetBundle\Twig;

class AssetExtension extends \Twig_Extension
{
    private $is_dev;

    public function __construct(bool $is_dev)
    {
        $this->is_dev = $is_dev;
    }

    public function getFunctions()
    {
        return [
            new \Twig_Function('asset_url', [$this, 'assetUrl'])
        ];
    }

    public function assetUrl(string $name): string
    {
        return ($this->is_dev ? '/dev/' : '/dist/' ) . $name;
    }
}
