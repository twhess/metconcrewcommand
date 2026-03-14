<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'maintainable_type',
        'maintainable_id',
        'maintenance_type',
        'category',
        'performed_at',
        'performed_by_type',
        'performed_by_user_id',
        'vendor_company_id',
        'odometer_at_service',
        'hours_at_service',
        'labor_hours',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'next_due_date',
        'next_due_odometer',
        'next_due_hours',
        'is_warranty_work',
        'warranty_claim_number',
        'warranty_provider',
        'work_order_number',
        'invoice_number',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'odometer_at_service' => 'decimal:1',
        'hours_at_service' => 'decimal:1',
        'labor_hours' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'next_due_date' => 'date',
        'next_due_odometer' => 'decimal:1',
        'next_due_hours' => 'decimal:1',
        'is_warranty_work' => 'boolean',
    ];

    /**
     * Get the maintainable entity (Equipment or Vehicle)
     */
    public function maintainable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the maintenance (if internal)
     */
    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * Get the vendor company (if external)
     */
    public function vendorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'vendor_company_id');
    }

    /**
     * Get the parts used in this maintenance
     */
    public function parts(): HasMany
    {
        return $this->hasMany(MaintenancePart::class);
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
     * Check if this maintenance is overdue for follow-up
     */
    public function isOverdue(): bool
    {
        if ($this->next_due_date && now()->greaterThan($this->next_due_date)) {
            return true;
        }

        return false;
    }

    /**
     * Check if this maintenance is approaching due date
     */
    public function isDueSoon(int $daysThreshold = 7): bool
    {
        if ($this->next_due_date && now()->diffInDays($this->next_due_date, false) <= $daysThreshold && now()->diffInDays($this->next_due_date, false) >= 0) {
            return true;
        }

        return false;
    }

    /**
     * Calculate total parts cost from related parts
     */
    public function calculatePartsCost(): float
    {
        return $this->parts()->sum('total_price') ?? 0.0;
    }

    /**
     * Calculate total maintenance cost
     */
    public function calculateTotalCost(): float
    {
        return ($this->labor_cost ?? 0) + $this->calculatePartsCost();
    }
}
