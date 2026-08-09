<?php

namespace Tests\Feature;

use App\Livewire\TestComponent;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireTestComponentTest extends TestCase
{
    /**
     * Test that the livewire component is rendered on the page.
     */
    public function test_livewire_component_is_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeLivewire(TestComponent::class);
    }

    /**
     * Test livewire custom message.
     */
    public function test_livewire_component_has_message(): void
    {
        Livewire::test(TestComponent::class)
            ->assertSet('message', '¡Hola desde Livewire!')
            ->assertSet('showExtra', false)
            ->call('toggleExtra')
            ->assertSet('showExtra', true)
            ->assertSee('¡Excelente! Livewire ha procesado la acción');
    }
}
