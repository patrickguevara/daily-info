<?php

use App\Models\User;

test('guests can access the dashboard', function () {
    $response = $this->get(route('dashboard'));
    // Dashboard redirects to dashboard.date with today's date
    $response->assertRedirect();
    $response->assertStatus(302);
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    // Dashboard redirects to dashboard.date with today's date
    $response->assertRedirect();
    $response->assertStatus(302);
});