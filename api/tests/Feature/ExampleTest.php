<?php

test('API health check returns a successful response', function () {
    $response = $this->getJson('/up');

    $response->assertOk();
});
