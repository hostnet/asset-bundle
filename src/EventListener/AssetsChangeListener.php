<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AssetsChangeListener
{
    private $bundler;
    private $build_config;

    public function __construct(BundlerInterface $bundler, BuildConfig $build_config)
    {
        $this->bundler      = $bundler;
        $this->build_config = $build_config;
    }

    public function onKernelRequest(GetResponseEvent $e): void
    {
        // Only trigger on the master request.
        if ($e->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $this->bundler->bundle($this->build_config);
    }
}
