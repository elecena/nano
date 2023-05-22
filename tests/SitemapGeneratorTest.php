<?php

/** use the same namespace as the @see \Nano\Utils\SitemapGenerator is in */
namespace Nano\Utils;

use Nano\AppTests\AppTestBase;

/**
 * @covers \Nano\Utils\SitemapGenerator
 */
class SitemapGeneratorTest extends AppTestBase
{
    public static array $filesWritten = [];

    public function setUp(): void
    {
        parent::setUp();
        static::$filesWritten = [];
    }

    public function testSaveXml()
    {
        $sitemap = new SitemapGenerator();
        $sitemap->startSitemap('pages');
        $sitemap->addUrl('/foo/bar');
        $urlsCount = $sitemap->save();

        $this->assertEquals(1, $urlsCount);

        // assert the given files were saved
        $this->assertCount(2, static::$filesWritten);
        $this->assertArrayHasKey('sitemap.xml', static::$filesWritten);
        $this->assertArrayHasKey('sitemap-001-pages.xml.gz', static::$filesWritten);

        $sitemapIndexXml = (string) static::$filesWritten['sitemap.xml'];

        $this->assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $sitemapIndexXml);
        $this->assertStringContainsString('<loc>http://example.org/site/sitemap-001-pages.xml.gz</loc>', $sitemapIndexXml);
    }
}

function file_put_contents(string $fileName, string $content): int
{
    //    var_dump(__METHOD__, $fileName, $content);

    SitemapGeneratorTest::$filesWritten[ basename($fileName) ] = $content;
    return strlen($content);
}
