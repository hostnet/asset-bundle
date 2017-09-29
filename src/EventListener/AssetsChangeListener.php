<?php

namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AssetsChangeListener
{
    /**
     * @var PipelineBundler
     */
    private $bundler;

    public function __construct(PipelineBundler $bundler)
    {
        $this->bundler = $bundler;
    }

    public function onKernelResponse(FilterResponseEvent $e)
    {
        // Only trigger on the master request.
        if ($e->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $this->bundler->execute();
    }
}
