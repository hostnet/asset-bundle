<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener
 */
class AssetsChangeListenerTest extends TestCase
{
    private $bundler;
    private $config;

    /**
     * @var AssetsChangeListener
     */
    private $assets_change_listener;

    protected function setUp()
    {
        $this->bundler = $this->prophesize(BundlerInterface::class);
        $this->config  = $this->prophesize(BuildConfig::class);

        $this->assets_change_listener = new AssetsChangeListener(
            $this->bundler->reveal(),
            $this->config->reveal()
        );
    }

    public function testOnKernelRequest()
    {
        $this->bundler->bundle($this->config)->shouldBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new GetResponseEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

        $this->assets_change_listener->onKernelRequest($e);
    }

    public function testOnKernelResponseSubRequest()
    {
        $this->bundler->bundle()->shouldNotBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new GetResponseEvent($kernel->reveal(), $request, HttpKernelInterface::SUB_REQUEST);

        $this->assets_change_listener->onKernelRequest($e);
    }
}
