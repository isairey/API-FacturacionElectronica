<?php

use App\Models\DispatchGuide;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('puede crear una guía de remisión básica con transporte privado', function () {
    // Preparar datos de prueba
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        'version' => '2022',
        
        // Datos del envío
        'cod_traslado' => '01',
        'des_traslado' => 'Venta',
        'mod_traslado' => '02', // Transporte privado
        'fec_traslado' => '2025-09-07',
        'peso_total' => 45.5,
        'und_peso_total' => 'KGM',
        'num_bultos' => 3,
        
        // Direcciones
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'AV LIMA 123, LIMA',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'AV ARRIOLA 456, SAN MARTIN',
        
        // Conductor (transporte privado)
        'conductor_tipo' => 'Principal',
        'conductor_tipo_doc' => '1',
        'conductor_num_doc' => '12345678',
        'conductor_licencia' => 'A12345678',
        'conductor_nombres' => 'CARLOS',
        'conductor_apellidos' => 'RODRIGUEZ',
        
        // Vehículo
        'vehiculo_placa' => 'ABC123',
        
        // Detalles
        'detalles' => [
            [
                'cantidad' => 10,
                'unidad' => 'NIU',
                'descripcion' => 'PRODUCTO ELECTRÓNICO',
                'codigo' => 'PROD001',
                'peso_total' => 45.5
            ]
        ],
        
        'observaciones' => 'Transporte con cuidado',
        'usuario_creacion' => 'TEST_USER'
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Guía de remisión creada correctamente'
            ]);

    // Verificar que se creó en la base de datos
    $guide = DispatchGuide::first();
    expect($guide)->not->toBeNull();
    expect($guide->company_id)->toBe($company->id);
    expect($guide->branch_id)->toBe($branch->id);
    expect($guide->destinatario_id)->toBe($client->id);
    expect($guide->mod_traslado)->toBe('02');
    expect($guide->peso_total)->toEqual(45.5);
    expect($guide->conductor_nombres)->toBe('CARLOS');
});

test('puede crear una guía de remisión con transporte público', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        
        // Datos del envío
        'cod_traslado' => '01',
        'des_traslado' => 'Venta',
        'mod_traslado' => '01', // Transporte público
        'fec_traslado' => '2025-09-07',
        'peso_total' => 30.0,
        'und_peso_total' => 'KGM',
        'num_bultos' => 2,
        
        // Direcciones
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'AV LIMA 123',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'AV ARRIOLA 456',
        
        // Transportista (transporte público)
        'transportista_tipo_doc' => '6',
        'transportista_num_doc' => '20123456789',
        'transportista_razon_social' => 'TRANSPORTES PUBLICOS SAC',
        'transportista_nro_mtc' => 'MTC001',
        
        // Vehículo
        'vehiculo_placa' => 'DEF456',
        
        // Detalles
        'detalles' => [
            [
                'cantidad' => 5,
                'unidad' => 'NIU',
                'descripcion' => 'PRODUCTO TEXTIL',
                'codigo' => 'PROD002',
                'peso_total' => 30.0
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Guía de remisión creada correctamente'
            ]);

    $guide = DispatchGuide::first();
    expect($guide->mod_traslado)->toBe('01');
    expect($guide->transportista_razon_social)->toBe('TRANSPORTES PUBLICOS SAC');
    expect($guide->transportista_nro_mtc)->toBe('MTC001');
});

test('puede crear guía de remisión para traslado entre establecimientos', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        
        // Traslado entre establecimientos
        'cod_traslado' => '04',
        'des_traslado' => 'Traslado entre establecimientos de la misma empresa',
        'mod_traslado' => '02',
        'fec_traslado' => '2025-09-07',
        'peso_total' => 75.0,
        'und_peso_total' => 'KGM',
        'num_bultos' => 5,
        
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'ALMACEN CENTRAL',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'SUCURSAL NORTE',
        
        'conductor_tipo' => 'Principal',
        'conductor_tipo_doc' => '1',
        'conductor_num_doc' => '87654321',
        'conductor_licencia' => 'B87654321',
        'conductor_nombres' => 'MIGUEL',
        'conductor_apellidos' => 'SANCHEZ',
        
        'vehiculo_placa' => 'GHI789',
        
        'detalles' => [
            [
                'cantidad' => 50,
                'unidad' => 'NIU',
                'descripcion' => 'INVENTARIO PARA SUCURSAL',
                'codigo' => 'INV001',
                'peso_total' => 75.0
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(201);

    $guide = DispatchGuide::first();
    expect($guide->cod_traslado)->toBe('04');
    expect($guide->motivo_traslado_name)->toBe('Traslado entre establecimientos de la misma empresa');
});

test('puede crear guía con vehículos secundarios', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        'cod_traslado' => '01',
        'mod_traslado' => '02',
        'fec_traslado' => '2025-09-07',
        'peso_total' => 100.0,
        'und_peso_total' => 'KGM',
        'num_bultos' => 10,
        
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'ORIGEN',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'DESTINO',
        
        'conductor_tipo' => 'Principal',
        'conductor_tipo_doc' => '1',
        'conductor_num_doc' => '11111111',
        'conductor_licencia' => 'A11111111',
        'conductor_nombres' => 'PEDRO',
        'conductor_apellidos' => 'MARTINEZ',
        
        'vehiculo_placa' => 'VEH001',
        
        // Vehículos secundarios
        'vehiculos_secundarios' => [
            ['placa' => 'VEH002'],
            ['placa' => 'VEH003']
        ],
        
        'detalles' => [
            [
                'cantidad' => 100,
                'unidad' => 'NIU',
                'descripcion' => 'CARGA PESADA',
                'codigo' => 'HEAVY001',
                'peso_total' => 100.0
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(201);

    $guide = DispatchGuide::first();
    expect($guide->vehiculos_secundarios)->toHaveCount(2);
    expect($guide->vehiculos_secundarios[0]['placa'])->toBe('VEH002');
    expect($guide->vehiculos_secundarios[1]['placa'])->toBe('VEH003');
});

test('valida campos requeridos para transporte privado', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        'cod_traslado' => '01',
        'mod_traslado' => '02', // Transporte privado pero sin datos de conductor
        'fec_traslado' => '2025-09-07',
        'peso_total' => 30.0,
        'und_peso_total' => 'KGM',
        'num_bultos' => 2,
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'ORIGEN',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'DESTINO',
        'vehiculo_placa' => 'ABC123',
        'detalles' => [
            [
                'cantidad' => 1,
                'unidad' => 'NIU',
                'descripcion' => 'PRODUCTO',
                'codigo' => 'PROD001'
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'conductor_tipo_doc',
                'conductor_num_doc', 
                'conductor_licencia',
                'conductor_nombres',
                'conductor_apellidos'
            ]);
});

test('valida campos requeridos para transporte público', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'destinatario_id' => $client->id,
        'serie' => 'T001',
        'fecha_emision' => '2025-09-06',
        'cod_traslado' => '01',
        'mod_traslado' => '01', // Transporte público pero sin datos de transportista
        'fec_traslado' => '2025-09-07',
        'peso_total' => 30.0,
        'und_peso_total' => 'KGM',
        'num_bultos' => 2,
        'partida_ubigeo' => '150101',
        'partida_direccion' => 'ORIGEN',
        'llegada_ubigeo' => '150203',
        'llegada_direccion' => 'DESTINO',
        'vehiculo_placa' => 'ABC123',
        'detalles' => [
            [
                'cantidad' => 1,
                'unidad' => 'NIU',
                'descripcion' => 'PRODUCTO',
                'codigo' => 'PROD001'
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/dispatch-guides', $data);

    $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'transportista_tipo_doc',
                'transportista_num_doc',
                'transportista_razon_social'
            ]);
});

test('puede generar PDF para una guía de remisión', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    
    $dispatchGuide = DispatchGuide::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);

    $response = $this->postJson("/api/v1/dispatch-guides/{$dispatchGuide->id}/generate-pdf");

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'PDF de guía de remisión generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'serie',
                    'correlativo',
                    'numero_documento',
                    'fecha_emision',
                    'fecha_traslado',
                    'pdf_path',
                    'pdf_url',
                    'download_url',
                    'estado_sunat',
                    'peso_total',
                    'modalidad_traslado',
                    'motivo_traslado',
                    'destinatario' => [
                        'numero_documento',
                        'razon_social'
                    ]
                ]
            ]);
});

test('puede obtener el catálogo de motivos de traslado', function () {
    $response = $this->getJson('/api/v1/dispatch-guides/catalogs/transfer-reasons');

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Motivos de traslado obtenidos correctamente'
            ]);

    $reasons = $response->json('data');
    expect(count($reasons))->toBeGreaterThan(10); // Debe tener varios motivos
    
    // Verificar que incluye los motivos principales
    $venta = collect($reasons)->firstWhere('code', '01');
    expect($venta)->not->toBeNull();
    expect($venta['name'])->toBe('Venta');
    
    $compra = collect($reasons)->firstWhere('code', '02');
    expect($compra)->not->toBeNull();
    expect($compra['name'])->toBe('Compra');

    $traslado = collect($reasons)->firstWhere('code', '04');
    expect($traslado)->not->toBeNull();
    expect($traslado['name'])->toBe('Traslado entre establecimientos de la misma empresa');
});

test('puede obtener el catálogo de modalidades de transporte', function () {
    $response = $this->getJson('/api/v1/dispatch-guides/catalogs/transport-modes');

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Modalidades de transporte obtenidas correctamente'
            ]);

    $modes = $response->json('data');
    expect(count($modes))->toBe(2); // Solo 2 modalidades
    
    $publico = collect($modes)->firstWhere('code', '01');
    expect($publico)->not->toBeNull();
    expect($publico['name'])->toBe('Transporte público');
    
    $privado = collect($modes)->firstWhere('code', '02');
    expect($privado)->not->toBeNull();
    expect($privado['name'])->toBe('Transporte privado');
});

test('puede listar guías de remisión con filtros', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    
    // Crear algunas guías de remisión
    DispatchGuide::factory()->count(3)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'PENDIENTE',
        'mod_traslado' => '02'
    ]);
    
    DispatchGuide::factory()->count(2)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'ACEPTADO',
        'mod_traslado' => '01'
    ]);

    // Filtrar por estado
    $response = $this->getJson("/api/v1/dispatch-guides?company_id={$company->id}&estado_sunat=PENDIENTE");

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Guías de remisión obtenidas correctamente'
            ]);

    $data = $response->json('data.data');
    expect(count($data))->toBe(3);

    // Filtrar por modalidad
    $response2 = $this->getJson("/api/v1/dispatch-guides?company_id={$company->id}&mod_traslado=01");

    $data2 = $response2->json('data.data');
    expect(count($data2))->toBe(2);
});