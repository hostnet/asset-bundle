<?php
/**
 * @copyright 2017 Hostnet B.V.
 */

declare(strict_types=1);

namespace Hostnet\Bundle\AssetBundle\Functional;

use Hostnet\Bundle\AssetBundle\Functional\Fixtures\TestKernel;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversNothing
 */
class FunctionalTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    protected static function getKernelClass()
    {
        return TestKernel::class;
    }

    public function testGetPipeline()
    {
        $container = self::$kernel->getContainer();

        $pipeline = $container->get('hostnet_asset.bundler');
        self::assertInstanceOf(PipelineBundler::class, $pipeline);

        /* @var PipelineBundler $pipeline */
        $reader = new FileReader(__DIR__ . '/../../');

        $writer = new class implements WriterInterface {
            public $files = [];

            public function write(File $file, string $content): void
            {
                $this->files[$file->path] = $content;
            }
        };
        $pipeline->execute($reader, $writer);

        self::assertEquals(
            [
                'web' . DIRECTORY_SEPARATOR . 'dev/require.js',
                'web' . DIRECTORY_SEPARATOR . 'dev/foo.js',
                'web' . DIRECTORY_SEPARATOR . 'dev/subfolder/subfolder.js',
            ],
            array_keys($writer->files)
        );
    }
}
