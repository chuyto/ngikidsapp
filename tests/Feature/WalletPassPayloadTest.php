<?php

namespace Tests\Feature;

use App\Models\Padre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WalletPassPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_qr_lleva_solo_el_uid_y_sin_texto_visible(): void
    {
        foreach (['padres.crear', 'padres.ver'] as $permiso) {
            Permission::findOrCreate($permiso);
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['padres.crear', 'padres.ver']);
        $this->actingAs($user);

        Http::fake([
            '*' => Http::response(['serialNumber' => 'test-serial', 'shareUrl' => 'https://example.com/p/test'], 200),
        ]);

        $this->post(route('padres.store'), [
            'nombre' => 'Juan Perez',
            'parentesco' => 'Padre',
            'telefono' => '5512345678',
            'privacidad' => '1',
            'hijos' => [
                ['nombre' => 'Mateo Perez', 'fecha_nacimiento' => '2018-05-06'],
            ],
        ]);

        $padre = Padre::first();
        $this->assertNotNull($padre);

        Http::assertSent(function ($request) use ($padre) {
            $data = $request->data();

            return ($data['barcodeValue'] ?? null) === $padre->uuid_short
                && ! str_contains((string) ($data['barcodeValue'] ?? ''), 'http')
                && array_key_exists('barcodeAltText', $data)
                && $data['barcodeAltText'] === ''
                && ($data['colorPreset'] ?? null) === 'orange'
                && ! array_key_exists('color', $data)
                && ! array_key_exists('logoURL', $data);
        });
    }
}
