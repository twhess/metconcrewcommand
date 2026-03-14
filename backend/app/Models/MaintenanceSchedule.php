<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'maintainable_type',
        'maintainable_id',
        'maintenance_type',
        'description',
        'category',
        'frequency_type',
        'frequency_days',
        'last_performed_date',
        'next_due_date',
        'frequency_miles',
        'frequency_hours',
        'last_performed_odometer',
        'last_performed_hours',
        'next_due_odometer',
        'next_due_hours',
        'notify_days_before',
        'notify_miles_before',
        'notify_hours_before',
        'assigned_to_type',
        'assigned_user_id',
        'assigned_vendor_id',
        'estimated_cost',
        'estimated_labor_hours',
        'is_active',
        'is_overdue',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_performed_date' => 'date',
        'next_due_date' => 'date',
        'frequency_miles' => 'decimal:1',
        'frequency_hours' => 'decimal:1',
        'last_performed_odometer' => 'decimal:1',
        'last_performed_hours' => 'decimal:1',
        'next_due_odometer' => 'decimal:1',
        'next_due_hours' => 'decimal:1',
        'notify_miles_before' => 'decimal:1',
        'notify_hours_before' => 'decimal:1',
        'estimated_cost' => 'decimal:2',
        'estimated_labor_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'is_overdue' => 'boolean',
    ];

    /**
     * Get the maintainable entity (Equipment or Vehicle)
     */
    public function maintainable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the assigned user (if internal)
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the assigned vendor (if external)
     */
    public function assignedVendor(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'assigned_vendor_id');
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
     * Check if maintenance is overdue by date
     */
    public function isOverdueByDate(): bool
    {
        if (!$this->is_active || !$this->next_due_date) {
            return false;
        }

        return now()->greaterThan($this->next_due_date);
    }

    /**
     * Check if maintenance is overdue by odometer
     */
    public function isOverdueByOdometer(?float $currentOdometer): bool
    {
        if (!$this->is_active || !$this->next_due_odometer || !$currentOdometer) {
            return false;
        }

        return $currentOdometer >= $this->next_due_odometer;
    }

    /**
     * Check if maintenance is overdue by hours
     */
    public function isOverdueByHours(?float $currentHours): bool
    {
        if (!$this->is_active || !$this->next_due_hours || !$currentHours) {
            return false;
        }

        return $currentHours >= $this->next_due_hours;
    }

    /**
     * Check if notification should be sent (date-based)
     */
    public function shouldNotifyByDate(): bool
    {
        if (!$this->is_active || !$this->next_due_date) {
            return false;
        }

        $daysUntilDue = now()->diffInDays($this->next_due_date, false);

        return $daysUntilDue >= 0 && $daysUntilDue <= $this->notify_days_before;
    }

    /**
     * Check if notification should be sent (odometer-based)
     */
    public function shouldNotifyByOdometer(?float $currentOdometer): bool
    {
        if (!$this->is_active || !$this->next_due_odometer || !$this->notify_miles_before || !$currentOdometer) {
            return false;
        }

        $milesUntilDue = $this->next_due_odometer - $currentOdometer;

        return $milesUntilDue >= 0 && $milesUntilDue <= $this->notify_miles_before;
    }

    /**
     * Check if notification should be sent (hours-based)
     */
    public function shouldNotifyByHours(?float $currentHours): bool
    {
        if (!$this->is_active || !$this->next_due_hours || !$this->notify_hours_before || !$currentHours) {
            return false;
        }

        $hoursUntilDue = $this->next_due_hours - $currentHours;

        return $hoursUntilDue >= 0 && $hoursUntilDue <= $this->notify_hours_before;
    }

    /**
     * Update the schedule after maintenance is performed
     */
    public function updateAfterMaintenance(MaintenanceRecord $record): void
    {
        $updates = [
            'last_performed_date' => $record->performed_at,
            'updated_by' => auth()->id(),
        ];

        // Update calendar-based
        if ($this->frequency_type === 'calendar' || $this->frequency_type === 'hybrid') {
            if ($this->frequency_days) {
                $updates['next_due_date'] = $record->performed_at->addDays($this->frequency_days);
            }
        }

        // Update odometer-based
        if (($this->frequency_type === 'usage' || $this->frequency_type === 'hybrid') && $record->odometer_at_service) {
            $updates['last_performed_odometer'] = $record->odometer_at_service;

            if ($this->frequency_miles) {
                $updates['next_due_odometer'] = $record->odometer_at_service + $this->frequency_miles;
            }
        }

        // Update hours-based
        if (($this->frequency_type === 'usage' || $this->frequency_type === 'hybrid') && $record->hours_at_service) {
            $updates['last_performed_hours'] = $record->hours_at_service;

            if ($this->frequency_hours) {
                $updates['next_due_hours'] = $record->hours_at_service + $this->frequency_hours;
            }
        }

        // Reset overdue flag
        $updates['is_overdue'] = false;

        $this->update($updates);
    }
}
