<?php

use App\Models\DebitNote;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('puede crear una nota de débito básica', function () {
    // Preparar datos de prueba
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FD01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '02',
        'des_motivo' => 'AUMENTO EN EL VALOR',
        'client' => [
            'tipo_documento' => '6',
            'numero_documento' => '20123456789',
            'razon_social' => 'EMPRESA TEST SAC',
        ],
        'detalles' => [
            [
                'codigo' => 'PROD001',
                'descripcion' => 'Aumento por concepto adicional',
                'unidad' => 'NIU',
                'cantidad' => 2,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/debit-notes', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Nota de débito creada correctamente'
            ]);

    expect(DebitNote::count())->toBe(1);
    
    $debitNote = DebitNote::first();
    expect($debitNote->tipo_doc_afectado)->toBe('01');
    expect($debitNote->cod_motivo)->toBe('02');
    expect($debitNote->tipo_documento)->toBe('08');
});

test('puede crear una nota de débito por intereses por mora', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FD01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '01',
        'des_motivo' => 'INTERESES POR MORA',
        'client' => [
            'tipo_documento' => '6',
            'numero_documento' => '20123456789',
            'razon_social' => 'EMPRESA TEST SAC',
        ],
        'detalles' => [
            [
                'codigo' => 'INT001',
                'descripcion' => 'Intereses por pago tardío',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mto_valor_unitario' => 25.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/debit-notes', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Nota de débito creada correctamente'
            ]);

    $debitNote = DebitNote::first();
    expect($debitNote->cod_motivo)->toBe('01');
    expect($debitNote->des_motivo)->toBe('INTERESES POR MORA');
});

test('puede crear una nota de débito para boleta', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'BD01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '03',
        'num_doc_afectado' => 'B001-000001',
        'cod_motivo' => '03',
        'des_motivo' => 'PENALIDADES/OTROS CONCEPTOS',
        'client' => [
            'tipo_documento' => '1',
            'numero_documento' => '12345678',
            'razon_social' => 'CLIENTE NATURAL',
        ],
        'detalles' => [
            [
                'codigo' => 'PEN001',
                'descripcion' => 'Penalidad por incumplimiento',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mto_valor_unitario' => 100.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/debit-notes', $data);

    $response->assertStatus(201);

    $debitNote = DebitNote::first();
    expect($debitNote->tipo_doc_afectado)->toBe('03');
    expect($debitNote->cod_motivo)->toBe('03');
    expect($debitNote->serie)->toBe('BD01');
});

test('valida motivos correctos de nota de débito', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FD01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '99', // Código inválido
        'des_motivo' => 'MOTIVO INVALIDO',
        'client' => [
            'tipo_documento' => '6',
            'numero_documento' => '20123456789',
            'razon_social' => 'EMPRESA TEST SAC',
        ],
        'detalles' => [
            [
                'codigo' => 'PROD001',
                'descripcion' => 'Producto de prueba',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/debit-notes', $data);

    $response->assertStatus(422)
            ->assertJsonValidationErrors('cod_motivo');
});

test('puede obtener el catálogo de motivos', function () {
    $response = $this->getJson('/api/v1/debit-notes/catalogs/motivos');

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Motivos de nota de débito obtenidos correctamente'
            ]);

    $motivos = $response->json('data');
    expect(count($motivos))->toBe(5); // 5 motivos válidos
    
    // Verificar que incluye los motivos principales
    $motivo02 = collect($motivos)->firstWhere('code', '02');
    expect($motivo02)->not->toBeNull();
    expect($motivo02['name'])->toBe('Aumento en el valor');
    
    $motivo01 = collect($motivos)->firstWhere('code', '01');
    expect($motivo01)->not->toBeNull();
    expect($motivo01['name'])->toBe('Intereses por mora');
});

test('puede listar notas de débito con filtros', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    
    // Crear algunas notas de débito
    DebitNote::factory()->count(3)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'PENDIENTE'
    ]);
    
    DebitNote::factory()->count(2)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'ACEPTADO'
    ]);

    $response = $this->getJson("/api/v1/debit-notes?company_id={$company->id}&estado_sunat=PENDIENTE");

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notas de débito obtenidas correctamente'
            ]);

    $data = $response->json('data.data');
    expect(count($data))->toBe(3);
});

test('puede generar PDF para una nota de débito', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    
    $debitNote = DebitNote::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);

    $response = $this->postJson("/api/v1/debit-notes/{$debitNote->id}/generate-pdf");

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'PDF de nota de débito generado correctamente'
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
                    'pdf_path',
                    'pdf_url',
                    'download_url',
                    'estado_sunat',
                    'mto_imp_venta',
                    'moneda',
                    'client' => [
                        'numero_documento',
                        'razon_social'
                    ]
                ]
            ]);
            
    // Verificar que los datos corresponden al debit note creado
    $responseData = $response->json('data');
    expect($responseData['id'])->toBe($debitNote->id);
    expect($responseData['serie'])->toBe($debitNote->serie);
    expect($responseData['correlativo'])->toBe($debitNote->correlativo);
});