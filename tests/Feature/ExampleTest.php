<?php

test('returns a successful response', function () {
    $response = $this->get(route('dashboard'));

    // Dashboard redirects to dashboard.date with today's date
    $response->assertRedirect();
    $response->assertStatus(302);
});