<?php
/**
 * @copyright 2017-present Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\BundlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \Hostnet\Bundle\AssetBundle\EventListener\AssetsChangeListener
 */
class AssetsChangeListenerTest extends TestCase
{
    use ProphecyTrait;

    private $bundler;
    private $config;

    /**
     * @var AssetsChangeListener
     */
    private $assets_change_listener;

    protected function setUp(): void
    {
        $this->bundler = $this->prophesize(BundlerInterface::class);
        $this->config  = $this->prophesize(BuildConfig::class);

        $this->assets_change_listener = new AssetsChangeListener(
            $this->bundler->reveal(),
            $this->config->reveal()
        );
    }

    public function testOnKernelRequest(): void
    {
        $this->bundler->bundle($this->config)->shouldBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

        $this->assets_change_listener->onKernelRequest($e);
    }

    public function testOnKernelResponseSubRequest(): void
    {
        $this->bundler->bundle()->shouldNotBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::SUB_REQUEST);

        $this->assets_change_listener->onKernelRequest($e);
    }
}
