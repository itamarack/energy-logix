<?php

use Tests\TestCase;

test('API health check returns a successful response', function () {
    /** @var TestCase $this */
    $response = $this->getJson('/up');

    $response->assertOk();
});
