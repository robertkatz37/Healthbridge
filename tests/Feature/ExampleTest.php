<?php

it('returns a successful response', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertStatus(200);
    $response->assertSee('HealthsBridge');
});
