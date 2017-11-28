<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\DependencyInjection;

use Hostnet\Component\Resolver\Plugin\PluginApi;
use Hostnet\Component\Resolver\Plugin\PluginInterface;

class MockPlugin implements PluginInterface
{

    public function activate(PluginApi $plugin_api): void
    {
    }
}
