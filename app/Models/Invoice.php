<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'tipo_documento',
        'serie',
        'correlativo',
        'numero_completo',
        'fecha_emision',
        'fecha_vencimiento',
        'ubl_version',
        'tipo_operacion',
        'moneda',
        'forma_pago_tipo',
        'forma_pago_cuotas',
        'valor_venta',
        'mto_oper_gravadas',
        'mto_oper_exoneradas',
        'mto_oper_inafectas',
        'mto_oper_exportacion',
        'mto_oper_gratuitas',
        'mto_igv_gratuitas',
        'mto_igv',
        'mto_base_ivap',
        'mto_ivap',
        'mto_isc',
        'mto_icbper',
        'mto_otros_tributos',
        'mto_detraccion',
        'mto_percepcion',
        'mto_retencion',
        'total_impuestos',
        'sub_total',
        'mto_imp_venta',
        'mto_anticipos',
        'detalles',
        'leyendas',
        'guias',
        'documentos_relacionados',
        'detraccion',
        'percepcion',
        'retencion',
        'datos_adicionales',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'estado_sunat',
        'respuesta_sunat',
        'codigo_hash',
        'usuario_creacion',
        'consulta_cpe_estado',
        'consulta_cpe_respuesta',
        'consulta_cpe_fecha',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'forma_pago_cuotas' => 'array',
        'valor_venta' => 'decimal:2',
        'mto_oper_gravadas' => 'decimal:2',
        'mto_oper_exoneradas' => 'decimal:2',
        'mto_oper_inafectas' => 'decimal:2',
        'mto_oper_exportacion' => 'decimal:2',
        'mto_oper_gratuitas' => 'decimal:2',
        'mto_igv_gratuitas' => 'decimal:2',
        'mto_igv' => 'decimal:2',
        'mto_isc' => 'decimal:2',
        'mto_icbper' => 'decimal:2',
        'mto_otros_tributos' => 'decimal:2',
        'mto_detraccion' => 'decimal:2',
        'mto_percepcion' => 'decimal:2',
        'mto_retencion' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'mto_imp_venta' => 'decimal:2',
        'mto_anticipos' => 'decimal:2',
        'detalles' => 'array',
        'leyendas' => 'array',
        'guias' => 'array',
        'documentos_relacionados' => 'array',
        'detraccion' => 'array',
        'percepcion' => 'array',
        'retencion' => 'array',
        'datos_adicionales' => 'array',
        'consulta_cpe_respuesta' => 'array',
        'consulta_cpe_fecha' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getTipoDocumentoNameAttribute(): string
    {
        return 'Factura Electrónica';
    }

    public function getEstadoSunatColorAttribute(): string
    {
        return match($this->estado_sunat) {
            'PENDIENTE' => 'warning',
            'ENVIADO' => 'info',
            'ACEPTADO' => 'success',
            'RECHAZADO' => 'danger',
            default => 'secondary'
        };
    }

    public function scopePending($query)
    {
        return $query->where('estado_sunat', 'PENDIENTE');
    }

    public function scopeSent($query)
    {
        return $query->where('estado_sunat', 'ENVIADO');
    }

    public function scopeAccepted($query)
    {
        return $query->where('estado_sunat', 'ACEPTADO');
    }

    public function scopeRejected($query)
    {
        return $query->where('estado_sunat', 'RECHAZADO');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_emision', [$startDate, $endDate]);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            if (empty($invoice->numero_completo)) {
                $invoice->numero_completo = $invoice->serie . '-' . $invoice->correlativo;
            }
        });
    }
}