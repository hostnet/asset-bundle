<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */
namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AssetsChangeListener
{
    /**
     * @var PipelineBundler
     */
    private $bundler;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(PipelineBundler $bundler, ConfigInterface $config)
    {
        $this->bundler = $bundler;
        $this->config  = $config;
    }

    public function onKernelRequest(GetResponseEvent $e): void
    {
        // Only trigger on the master request.
        if ($e->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $reader = new FileReader($this->config->getProjectRoot());
        $writer = new FileWriter($this->config->getEventDispatcher(), $this->config->getProjectRoot());

        $this->bundler->execute($reader, $writer);
    }
}
