<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnboardingDraft extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'draft_id',
        'tenant_id',
        'company_id',
        'candidate_name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'location_id',
        'department_id',
        'current_step',
        'completion_percentage',
        'form_data',
        'uploaded_files',
        'version',
        'status',
        'created_by',
        'updated_by',
        'last_saved_at',
        'submitted_at',
        'employee_id',
    ];

    protected $casts = [
        'form_data' => 'array',
        'uploaded_files' => 'array',
        'current_step' => 'integer',
        'completion_percentage' => 'integer',
        'version' => 'integer',
        'last_saved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getCandidateDisplayNameAttribute(): string
    {
        if (!empty($this->candidate_name)) {
            return $this->candidate_name;
        }

        $names = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        if (!empty($names)) {
            return implode(' ', $names);
        }

        return 'Unnamed Candidate (' . $this->draft_id . ')';
    }

    /**
     * Compute completion percentage across the 16 wizard sections.
     */
    public function recalculateProgress(): int
    {
        $data = $this->form_data ?? [];
        $sectionsCompleted = 0;
        $totalSections = 16;

        // Section 1: Basic Info
        if (!empty($data['first_name']) && !empty($data['gender']) && !empty($data['date_of_birth'])) {
            $sectionsCompleted++;
        }

        // Section 2: Employment
        if (!empty($data['joining_date']) || !empty($data['location_id']) || !empty($data['department_id'])) {
            $sectionsCompleted++;
        }

        // Section 3: Contact
        if (!empty($data['phone']) || !empty($data['personal_email']) || !empty($data['email'])) {
            $sectionsCompleted++;
        }

        // Section 4: Address
        if (!empty($data['current_address_line1']) || !empty($data['curr_flat']) || !empty($data['permanent_address_line1'])) {
            $sectionsCompleted++;
        }

        // Section 5: Family
        if (!empty($data['family_members']) || !empty($data['father_name']) || !empty($data['spouse_name'])) {
            $sectionsCompleted++;
        }

        // Section 6: Personal Details
        if (!empty($data['marital_status']) || !empty($data['blood_group']) || !empty($data['nationality'])) {
            $sectionsCompleted++;
        }

        // Section 7: Statutory & Compliance
        if (!empty($data['pan_number']) || !empty($data['aadhaar_number']) || !empty($data['uan_number']) || !empty($data['pf_number'])) {
            $sectionsCompleted++;
        }

        // Section 8: Banking
        if (!empty($data['bank_name']) || !empty($data['bank_account_number']) || !empty($data['ifsc_code'])) {
            $sectionsCompleted++;
        }

        // Section 9: Education
        if (!empty($data['educations']) || !empty($data['highest_qualification'])) {
            $sectionsCompleted++;
        }

        // Section 10: Skills
        if (!empty($data['skills']) || !empty($data['primary_skills'])) {
            $sectionsCompleted++;
        }

        // Section 11: Visa & Immigration
        if (!empty($data['passport_number']) || !empty($data['visa_number']) || !empty($data['visa_type'])) {
            $sectionsCompleted++;
        }

        // Section 12: Documents
        if (!empty($this->uploaded_files) && count($this->uploaded_files) > 0) {
            $sectionsCompleted++;
        }

        // Section 13: Assets
        if (!empty($data['laptop_required']) || !empty($data['asset_requests'])) {
            $sectionsCompleted++;
        }

        // Section 14: Login Account
        if (!empty($data['official_email']) || !empty($data['role'])) {
            $sectionsCompleted++;
        }

        // Section 15: User Defined
        if (!empty($data['custom_fields'])) {
            $sectionsCompleted++;
        }

        // Minimum progress is based on current step if few fields filled
        $stepProgress = (int)(($this->current_step / $totalSections) * 100);
        $fieldProgress = (int)(($sectionsCompleted / $totalSections) * 100);

        return max(5, max($stepProgress, $fieldProgress));
    }
}

