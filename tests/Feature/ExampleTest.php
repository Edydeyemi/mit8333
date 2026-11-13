<?php

declare(strict_types=1);

test('the application returns a successful response on public landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Headworld Campus Operations');
});
