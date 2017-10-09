<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Bundle\AssetBundle\EventListener;

use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
        $this->bundler = $this->prophesize(PipelineBundler::class);
        $this->config  = $this->prophesize(ConfigInterface::class);

        $this->assets_change_listener = new AssetsChangeListener(
            $this->bundler->reveal(),
            $this->config->reveal()
        );
    }

    public function testOnKernelResponse()
    {
        $this->config->cwd()->willReturn(__DIR__);

        $this->bundler
            ->execute(Argument::type(ReaderInterface::class), Argument::type(WriterInterface::class))
            ->shouldBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new FilterResponseEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new Response());

        $this->assets_change_listener->onKernelResponse($e);
    }

    public function testOnKernelResponseSubRequest()
    {
        $this->config->cwd()->willReturn(__DIR__);

        $this->bundler
            ->execute()
            ->shouldNotBeCalled();

        $kernel  = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();

        $e = new FilterResponseEvent($kernel->reveal(), $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $this->assets_change_listener->onKernelResponse($e);
    }
}
