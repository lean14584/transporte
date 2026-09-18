<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_visiting_the_root_is_redirected_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_visiting_the_root_is_redirected_to_the_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertRedirect(route('dashboard'));
    }
}
