<?php

use App\Models\CreditNote;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('puede crear una nota de crédito básica', function () {
    // Preparar datos de prueba
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FC01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '07',
        'des_motivo' => 'DEVOLUCION POR ITEM',
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
                'cantidad' => 2,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/credit-notes', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Nota de crédito creada correctamente'
            ]);

    expect(CreditNote::count())->toBe(1);
    
    $creditNote = CreditNote::first();
    expect($creditNote->tipo_doc_afectado)->toBe('01');
    expect($creditNote->cod_motivo)->toBe('07');
    expect($creditNote->forma_pago_tipo)->toBe('Contado');
});

test('puede crear una nota de crédito con forma de pago a crédito', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create();

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FC01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'forma_pago_tipo' => 'Credito',
        'forma_pago_cuotas' => [
            [
                'monto' => 118.00,
                'fecha_pago' => '2025-10-06'
            ]
        ],
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '13',
        'des_motivo' => 'AJUSTES - MONTOS Y/O FECHAS DE PAGO',
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
                'cantidad' => 2,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/credit-notes', $data);

    $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Nota de crédito creada correctamente'
            ]);

    $creditNote = CreditNote::first();
    expect($creditNote->forma_pago_tipo)->toBe('Credito');
    expect($creditNote->forma_pago_cuotas)->toBeArray();
    expect(count($creditNote->forma_pago_cuotas))->toBe(1);
});

test('puede crear una nota de crédito con guías relacionadas', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FC01',
        'fecha_emision' => '2025-09-06',
        'moneda' => 'PEN',
        'tipo_doc_afectado' => '01',
        'num_doc_afectado' => 'F001-000001',
        'cod_motivo' => '07',
        'des_motivo' => 'DEVOLUCION POR ITEM',
        'guias' => [
            [
                'tipo_doc' => '09',
                'nro_doc' => '0001-213'
            ]
        ],
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
                'cantidad' => 2,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/credit-notes', $data);

    $response->assertStatus(201);

    $creditNote = CreditNote::first();
    expect($creditNote->guias)->toBeArray();
    expect(count($creditNote->guias))->toBe(1);
    expect($creditNote->guias[0]['tipo_doc'])->toBe('09');
});

test('valida motivos correctos de nota de crédito', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);

    $data = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'serie' => 'FC01',
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
                'cantidad' => 2,
                'mto_valor_unitario' => 50.00,
                'porcentaje_igv' => 18.00,
                'tip_afe_igv' => '10',
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/credit-notes', $data);

    $response->assertStatus(422)
            ->assertJsonValidationErrors('cod_motivo');
});

test('puede obtener el catálogo de motivos', function () {
    $response = $this->getJson('/api/v1/credit-notes/catalogs/motivos');

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Motivos de nota de crédito obtenidos correctamente'
            ]);

    $motivos = $response->json('data');
    expect(count($motivos))->toBe(13); // Todos los motivos incluido el '13'
    
    // Verificar que incluye el nuevo motivo '13'
    $motivo13 = collect($motivos)->firstWhere('code', '13');
    expect($motivo13)->not->toBeNull();
    expect($motivo13['name'])->toBe('Ajustes - montos y/o fechas de pago');
});

test('puede listar notas de crédito con filtros', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    
    // Crear algunas notas de crédito
    CreditNote::factory()->count(3)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'PENDIENTE'
    ]);
    
    CreditNote::factory()->count(2)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'estado_sunat' => 'ACEPTADO'
    ]);

    $response = $this->getJson("/api/v1/credit-notes?company_id={$company->id}&estado_sunat=PENDIENTE");

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notas de crédito obtenidas correctamente'
            ]);

    $data = $response->json('data.data');
    expect(count($data))->toBe(3);
});