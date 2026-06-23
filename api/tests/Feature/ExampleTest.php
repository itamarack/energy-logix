<?php

test('API health check returns a successful response', function ()
{
    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/up');

    $response->assertOk();
});
