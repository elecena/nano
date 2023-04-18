<?php

namespace Nano\AppTests;

/**
 * Set of unit tests for Nano's Application request
 */
class AppRequestTest extends AppTestBase
{
    public function testRequest()
    {
        $this->setUp();
        $request = $this->app->getRequest();

        // this one will be handled by route() method
        $this->assertTrue($request->wasPosted());
        $this->assertEquals('lm317', $request->get('q'));
        $this->assertEquals('/foo/test/', $request->getPath());
        $this->assertEquals($this->ip, $request->getIP());
    }
}
