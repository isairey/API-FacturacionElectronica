<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear usuario para autenticación
    $this->user = User::factory()->create();
    
    // Crear empresa de prueba
    $this->company = Company::factory()->create([
        'ruc' => '20123456789',
        'razon_social' => 'EMPRESA DE PRUEBA S.A.C.',
        'modo_produccion' => false,
    ]);
});

test('puede obtener credenciales GRE de una empresa', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/companies/{$this->company->id}/gre-credentials");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'company_id',
                'company_name',
                'modo_actual',
                'credenciales_configuradas',
                'credenciales' => [
                    'beta' => [
                        'client_id',
                        'client_secret',
                        'ruc_proveedor',
                        'usuario_sol',
                        'clave_sol',
                    ],
                    'produccion' => [
                        'client_id',
                        'client_secret',
                        'ruc_proveedor',
                        'usuario_sol',
                        'clave_sol',
                    ]
                ]
            ]
        ]);

    expect($response->json('data.company_id'))->toBe($this->company->id);
    expect($response->json('data.modo_actual'))->toBe('beta');
});

test('puede actualizar credenciales GRE para ambiente beta', function () {
    $credenciales = [
        'modo' => 'beta',
        'client_id' => 'test-nueva-client-id',
        'client_secret' => 'test-nuevo-secret-123456',
        'ruc_proveedor' => '20987654321',
        'usuario_sol' => 'NUEVOUSUARIO',
        'clave_sol' => 'nuevaclave123',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/companies/{$this->company->id}/gre-credentials", $credenciales);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Credenciales GRE para beta actualizadas correctamente',
        ]);

    // Verificar que se guardaron en la base de datos
    $this->company->refresh();
    expect($this->company->getGreClientId())->toBe('test-nueva-client-id');
    expect($this->company->getGreRucProveedor())->toBe('20987654321');
});

test('valida credenciales requeridas para ambiente producción', function () {
    $credenciales = [
        'modo' => 'produccion',
        'client_id' => 'prod-client-id',
        'client_secret' => 'prod-secret-123456',
        // Faltan campos obligatorios para producción
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/companies/{$this->company->id}/gre-credentials", $credenciales);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ruc_proveedor', 'usuario_sol', 'clave_sol']);
});

test('no permite usar credenciales de beta en producción', function () {
    $credenciales = [
        'modo' => 'produccion',
        'client_id' => 'test-85e5b0ae-255c-4891-a595-0b98c65c9854', // Credencial de beta
        'client_secret' => 'prod-secret-123456',
        'ruc_proveedor' => '20123456789',
        'usuario_sol' => 'PRODUSER',
        'clave_sol' => 'prodpass123',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/companies/{$this->company->id}/gre-credentials", $credenciales);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['modo']);
});

test('puede probar conexión con credenciales configuradas', function () {
    // Configurar credenciales primero
    $this->company->setGreCredentials('beta', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-secret-123456',
        'ruc_proveedor' => '20123456789',
        'usuario_sol' => 'TESTUSER',
        'clave_sol' => 'testpass123',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/companies/{$this->company->id}/gre-credentials/test-connection");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'company_id',
                'modo',
                'client_id',
                'ruc_proveedor',
                'timestamp'
            ]
        ]);
});

test('falla test de conexión sin credenciales configuradas', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/companies/{$this->company->id}/gre-credentials/test-connection");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Las credenciales GRE no están configuradas para esta empresa'
        ]);
});

test('puede limpiar credenciales para un ambiente', function () {
    // Configurar credenciales primero
    $this->company->setGreCredentials('beta', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-secret',
        'ruc_proveedor' => '20123456789',
        'usuario_sol' => 'TEST',
        'clave_sol' => 'test123',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/companies/{$this->company->id}/gre-credentials/clear", [
            'modo' => 'beta'
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Credenciales GRE para beta han sido limpiadas'
        ]);

    // Verificar que se limpiaron
    $this->company->refresh();
    expect($this->company->getGreClientId())->toBeNull();
});

test('puede copiar credenciales entre ambientes', function () {
    // Configurar credenciales en beta
    $this->company->setGreCredentials('beta', [
        'client_id' => 'beta-client-id',
        'client_secret' => 'beta-secret',
        'ruc_proveedor' => '20123456789',
        'usuario_sol' => 'BETAUSER',
        'clave_sol' => 'betapass',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/companies/{$this->company->id}/gre-credentials/copy", [
            'origen' => 'beta',
            'destino' => 'produccion'
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Credenciales copiadas de beta a produccion'
        ]);

    // Verificar que se copiaron
    $credencialesProduccion = $this->company->getConfig('credenciales_gre.produccion');
    expect($credencialesProduccion['client_id'])->toBe('beta-client-id');
});

test('no puede copiar credenciales del mismo ambiente', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/companies/{$this->company->id}/gre-credentials/copy", [
            'origen' => 'beta',
            'destino' => 'beta'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['destino']);
});

test('puede obtener valores por defecto para un ambiente', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/gre-credentials/defaults/beta');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'modo',
                'credenciales_default' => [
                    'client_id',
                    'client_secret',
                    'ruc_proveedor',
                    'usuario_sol',
                    'clave_sol',
                ],
                'descripcion'
            ]
        ]);

    expect($response->json('data.modo'))->toBe('beta');
});

test('rechaza ambiente inválido en valores por defecto', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/gre-credentials/defaults/invalid');

    $response->assertStatus(404); // No coincide con la constraint de ruta
});

test('requiere autenticación para todas las rutas', function () {
    $endpoints = [
        'GET' => "/api/v1/companies/{$this->company->id}/gre-credentials",
        'PUT' => "/api/v1/companies/{$this->company->id}/gre-credentials",
        'POST' => "/api/v1/companies/{$this->company->id}/gre-credentials/test-connection",
        'DELETE' => "/api/v1/companies/{$this->company->id}/gre-credentials/clear",
        'POST' => "/api/v1/companies/{$this->company->id}/gre-credentials/copy",
        'GET' => '/api/v1/gre-credentials/defaults/beta',
    ];

    foreach ($endpoints as $method => $endpoint) {
        $response = match($method) {
            'GET' => $this->getJson($endpoint),
            'PUT' => $this->putJson($endpoint, []),
            'POST' => $this->postJson($endpoint, []),
            'DELETE' => $this->deleteJson($endpoint, []),
        };

        $response->assertStatus(401);
    }
});