<?php

use Nano\NanoBaseTest;

class TestSkin extends Skin
{
    const NAME = 'test-skin';
}

class SkinTest extends NanoBaseTest
{
    private TestSkin $skin;

    public function setUp(): void
    {
        $app = Nano::app(__DIR__ . '/app');
        $this->skin = new TestSkin($app, TestSkin::NAME);
    }

    public function testRenderHeadNullHandling()
    {
        $this->skin->addMeta('null', null);
        $this->skin->addMeta('empty_string', '');
        $this->skin->addMeta('a_string', 'Foo Bar');
        $this->skin->addMeta('a_number', 42);

        $this->skin->addLink('foo', null);
        $this->skin->addLink('bar', 'test', ['type' => 'some/thing']);

        $head = $this->skin->renderHead();

        $this->assertStringContainsString('<meta name="null" value="">', $head, );
        $this->assertStringContainsString('<meta name="empty_string" value="">', $head, );
        $this->assertStringContainsString('<meta name="a_string" value="Foo Bar">', $head, );
        $this->assertStringContainsString('<meta name="a_number" value="42">', $head, );

        $this->assertStringContainsString('<link rel="foo" value="">', $head, );
        $this->assertStringContainsString('<link rel="bar" value="test" type="some/thing">', $head, );
    }
}
