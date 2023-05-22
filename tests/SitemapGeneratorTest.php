<?php

/** use the same namespace as the @see \Nano\Utils\SitemapGenerator is in */
namespace Nano\Utils;

use Nano\NanoBaseTest;

/**
 * @covers \Nano\Utils\SitemapGenerator
 */
class SitemapGeneratorTest extends NanoBaseTest
{
    public function testSaveXml()
    {
        $sitemap = new SitemapGenerator();
        $sitemap->startSitemap('pages');
        $sitemap->addUrl('/foo/bar');
    }
}

function file_put_contents(string $fileName, string $content): int
{
    var_dump(__METHOD__, $fileName, $content);
    return strlen($content);
}
