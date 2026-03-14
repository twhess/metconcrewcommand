<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_record_id',
        'part_number',
        'part_name',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'total_price',
        'vendor_company_id',
        'vendor_part_number',
        'invoice_number',
        'is_warranty_part',
        'warranty_expires_at',
        'part_type',
        'has_core_charge',
        'core_charge_amount',
        'core_returned',
        'core_returned_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_warranty_part' => 'boolean',
        'warranty_expires_at' => 'date',
        'has_core_charge' => 'boolean',
        'core_charge_amount' => 'decimal:2',
        'core_returned' => 'boolean',
        'core_returned_date' => 'date',
    ];

    /**
     * Get the maintenance record this part belongs to
     */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    /**
     * Get the vendor company
     */
    public function vendorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'vendor_company_id');
    }

    /**
     * Get the user who created this record
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if warranty is still valid
     */
    public function isWarrantyValid(): bool
    {
        if (!$this->is_warranty_part || !$this->warranty_expires_at) {
            return false;
        }

        return now()->lessThanOrEqualTo($this->warranty_expires_at);
    }

    /**
     * Check if core charge is outstanding
     */
    public function hasCoreOutstanding(): bool
    {
        return $this->has_core_charge && !$this->core_returned;
    }

    /**
     * Calculate total price with core charge if applicable
     */
    public function getTotalWithCore(): float
    {
        $total = $this->total_price ?? 0;

        if ($this->hasCoreOutstanding()) {
            $total += $this->core_charge_amount ?? 0;
        }

        return $total;
    }
}
