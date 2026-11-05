<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingDropdownMaster extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'location_id',
        'category',
        'name',
        'code',
        'description',
        'parent_id',
        'display_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Predefined Master Categories for Enterprise Onboarding.
     */
    public const CATEGORIES = [
        'title' => [
            'name' => 'Titles / Salutations',
            'icon' => 'bi-person-badge',
            'section' => '1. Basic Information',
            'description' => 'Personal title prefixes (Mr., Ms., Mrs., Dr., etc.)'
        ],
        'gender' => [
            'name' => 'Gender Options',
            'icon' => 'bi-gender-ambiguous',
            'section' => '1. Basic Information',
            'description' => 'Gender classifications for demographic and statutory reporting'
        ],
        'blood_group' => [
            'name' => 'Blood Groups',
            'icon' => 'bi-droplet-half',
            'section' => '1. Basic Information',
            'description' => 'Standard blood groups for occupational health and emergency records'
        ],
        'nationality' => [
            'name' => 'Nationalities',
            'icon' => 'bi-flag',
            'section' => '1. Basic Information',
            'description' => 'National citizenship classifications'
        ],
        'disability_category' => [
            'name' => 'Disability / Divyaang Categories',
            'icon' => 'bi-universal-access',
            'section' => '1. Basic Information',
            'description' => 'Divyaang classifications for statutory affirmative compliance'
        ],
        'employee_grade' => [
            'name' => 'Employee Grades / Bands',
            'icon' => 'bi-award',
            'section' => '2. Employment Information',
            'description' => 'Corporate hierarchy cadre levels (E1, E2, M1, M2, W1, W2, etc.)'
        ],
        'division' => [
            'name' => 'Corporate Divisions',
            'icon' => 'bi-diagram-3',
            'section' => '2. Employment Information',
            'description' => 'High-level business units and corporate divisions'
        ],
        'plant_unit' => [
            'name' => 'Plant Units / Work Areas',
            'icon' => 'bi-gear-wide-connected',
            'section' => '2. Employment Information',
            'description' => 'Manufacturing units, shops, and plant facilities'
        ],
        'project' => [
            'name' => 'Project Allocations',
            'icon' => 'bi-kanban',
            'section' => '2. Employment Information',
            'description' => 'Client or internal operational project assignments'
        ],
        'onboarding_channel' => [
            'name' => 'Workforce Acquisition Channels',
            'icon' => 'bi-signpost-split',
            'section' => '2. Employment Information',
            'description' => 'Hiring sources (Direct, Contractor, Referral, Agency, Campus)'
        ],
        'skill_classification' => [
            'name' => 'Worker Skill Classifications',
            'icon' => 'bi-tools',
            'section' => '2. Employment Information',
            'description' => 'Statutory wage classifications (Skilled, Semi-Skilled, Unskilled, Highly Skilled)'
        ],
        'employment_type' => [
            'name' => 'Employment Contract Types',
            'icon' => 'bi-file-earmark-person',
            'section' => '2. Employment Information',
            'description' => 'Contractual arrangements (Permanent, Intern, Contract, Probationary, Trainee)'
        ],
        'employment_status' => [
            'name' => 'Initial Employment Statuses',
            'icon' => 'bi-activity',
            'section' => '2. Employment Information',
            'description' => 'Lifecycle states upon registration (Active, On Probation, Internship Active)'
        ],
        'payment_mode' => [
            'name' => 'Disbursement Payment Modes',
            'icon' => 'bi-cash-coin',
            'section' => '2. Employment Information',
            'description' => 'Approved salary disbursement methods (Bank Transfer, Cheque, Cash)'
        ],
        'marital_status' => [
            'name' => 'Marital Statuses',
            'icon' => 'bi-heart',
            'section' => '5. Family Details',
            'description' => 'Legal marital statuses (Single, Married, Divorced, Widowed)'
        ],
        'relationship_type' => [
            'name' => 'Relationship Types',
            'icon' => 'bi-people',
            'section' => '5. Family Details',
            'description' => 'Dependent, nominee, and emergency contact relations'
        ],
        'religion' => [
            'name' => 'Religions',
            'icon' => 'bi-flower1',
            'section' => '6. Personal Details',
            'description' => 'Optional socio-demographic religious affiliations'
        ],
        'caste_category' => [
            'name' => 'Social / Caste Categories',
            'icon' => 'bi-tag',
            'section' => '6. Personal Details',
            'description' => 'Statutory affirmative categories (General, OBC, SC, ST, EWS)'
        ],
        'bank_name' => [
            'name' => 'Approved Commercial Banks',
            'icon' => 'bi-bank',
            'section' => '8. Banking & Identity',
            'description' => 'Scheduled commercial and rural banking institutions'
        ],
        'bank_account_type' => [
            'name' => 'Bank Account Types',
            'icon' => 'bi-credit-card-2-front',
            'section' => '8. Banking & Identity',
            'description' => 'Disbursement account classes (Salary, Savings, Current)'
        ],
        'qualification_level' => [
            'name' => 'Qualification Levels',
            'icon' => 'bi-mortarboard',
            'section' => '9. Education',
            'description' => 'Academic hierarchy tiers (Doctorate, Master\'s, Bachelor\'s, Diploma, ITI, 12th, 10th)'
        ],
        'degree_name' => [
            'name' => 'Degrees & Specializations',
            'icon' => 'bi-journal-bookmark',
            'section' => '9. Education',
            'description' => 'Standard degree designations (B.E. Mechanical, B.Tech CSE, MBA, B.Com, etc.)'
        ],
        'board_university' => [
            'name' => 'Boards & Universities',
            'icon' => 'bi-building',
            'section' => '9. Education',
            'description' => 'Recognized educational boards and universities'
        ],
        'skill_category' => [
            'name' => 'Skill Competency Domains',
            'icon' => 'bi-stars',
            'section' => '10. Skills & Competencies',
            'description' => 'Competency categories (Technical, Operational, Safety, Quality, Leadership)'
        ],
        'skill_name' => [
            'name' => 'Operational Skills',
            'icon' => 'bi-lightning',
            'section' => '10. Skills & Competencies',
            'description' => 'Specific operational skills (CNC Operation, TIG Welding, PLC Programming, etc.)'
        ],
        'skill_proficiency' => [
            'name' => 'Skill Proficiency Tiers',
            'icon' => 'bi-bar-chart-steps',
            'section' => '10. Skills & Competencies',
            'description' => 'Competency rating tiers (Beginner, Intermediate, Advanced, Expert)'
        ],
        'visa_type' => [
            'name' => 'Visa & Work Permit Types',
            'icon' => 'bi-globe-americas',
            'section' => '11. Visa & Immigration',
            'description' => 'International work permits, visas, and resident permits'
        ],
        'country' => [
            'name' => 'Countries & Issuing Authorities',
            'icon' => 'bi-geo-alt',
            'section' => '11. Visa & Immigration',
            'description' => 'ISO standard countries for passports, citizenship, and employment'
        ],
        'document_type' => [
            'name' => 'Document Vault Types',
            'icon' => 'bi-file-earmark-lock',
            'section' => '12. Documents & Certificates',
            'description' => 'Mandatory proof categories (Identity, Education, Employment, Banking, Health)'
        ],
        'uniform_size' => [
            'name' => 'Uniform & Apparel Sizes',
            'icon' => 'bi-bag',
            'section' => '15. User Defined Fields',
            'description' => 'Standard enterprise uniform sizing (S, M, L, XL, XXL, 3XL)'
        ],
        'parking_type' => [
            'name' => 'Parking Space Types',
            'icon' => 'bi-p-circle',
            'section' => '15. User Defined Fields',
            'description' => 'Vehicle parking facility allocations (No, Two Wheeler, Four Wheeler)'
        ],
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function getScopeTypeAttribute(): string
    {
        if ($this->location_id && $this->company_id) {
            return 'selected_branch';
        }
        if ($this->company_id) {
            return 'selected_company';
        }
        return 'global';
    }

    public function getScopeBadgeAttribute(): array
    {
        if ($this->location_id && $this->location) {
            return [
                'type' => 'branch',
                'label' => 'Branch: ' . ($this->location->name ?? 'Branch #' . $this->location_id),
                'class' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                'icon' => 'bi-geo-alt',
            ];
        }
        if ($this->company_id && $this->company) {
            return [
                'type' => 'company',
                'label' => 'Company: ' . ($this->company->code ?? $this->company->name ?? 'Co #' . $this->company_id),
                'class' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                'icon' => 'bi-building',
            ];
        }
        return [
            'type' => 'global',
            'label' => 'Global Enterprise Standard',
            'class' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'icon' => 'bi-globe2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForScope(Builder $query, ?int $companyId = null, ?int $tenantId = null, ?int $locationId = null): Builder
    {
        return $query->where(function (Builder $q) use ($companyId, $tenantId, $locationId) {
            $q->where(function (Builder $gq) {
                $gq->whereNull('company_id')->whereNull('tenant_id');
            });

            if ($tenantId) {
                $q->orWhere(function (Builder $tq) use ($tenantId, $companyId, $locationId) {
                    $tq->where('tenant_id', $tenantId)
                       ->where(function (Builder $cq) use ($companyId, $locationId) {
                           $cq->whereNull('company_id');
                           if ($companyId) {
                               $cq->orWhere(function (Builder $ccq) use ($companyId, $locationId) {
                                   $ccq->where('company_id', $companyId);
                                   if ($locationId) {
                                       $ccq->where(function (Builder $lq) use ($locationId) {
                                           $lq->whereNull('location_id')->orWhere('location_id', $locationId);
                                       });
                                   } else {
                                       $ccq->whereNull('location_id');
                                   }
                               });
                           }
                       });
                });
            } elseif ($companyId) {
                $q->orWhere(function (Builder $cq) use ($companyId, $locationId) {
                    $cq->where('company_id', $companyId);
                    if ($locationId) {
                        $cq->where(function (Builder $lq) use ($locationId) {
                            $lq->whereNull('location_id')->orWhere('location_id', $locationId);
                        });
                    } else {
                        $cq->whereNull('location_id');
                    }
                });
            }
        });
    }

    /**
     * Get active options for a given category scoped to company/tenant/location.
     */
    public static function getOptions(string $category, ?int $companyId = null, ?int $tenantId = null, ?int $locationId = null): Collection
    {
        return static::active()
            ->ofCategory($category)
            ->forScope($companyId, $tenantId, $locationId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }
}

