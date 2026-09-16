<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_hours_and_clickable_contacts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('de 19h à 2h du matin')
            ->assertSee('fa-brands fa-whatsapp', false)
            ->assertSee('href="https://wa.me/22249625325', false)
            ->assertDontSee('tel:', false)
            ->assertSee('Développé par');
    }
}
