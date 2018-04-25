<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Component\Resolver\Plugin\PluginApi;
use Hostnet\Component\Resolver\Plugin\PluginInterface;

class MockPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
    }
}
