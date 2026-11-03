{{--
  Enterprise User Profile Drawer / Modal
  Displays Personal, Employment & Account Telemetry adhering to strict RBAC & zero salary exposure
--}}
<div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-xl" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header with Identity Banner -->
            <div class="modal-header border-0 p-4 text-white position-relative" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow"
                         style="width: 58px; height: 58px; font-size: 20px; background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%); border: 3px solid rgba(255, 255, 255, 0.2);">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="userProfileModalLabel">{{ auth()->user()->name }}</h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge" style="background-color: rgba(255, 255, 255, 0.15); font-size: 11px; font-weight: 500;">
                                {{ auth()->user()->role?->label ?? 'User' }}
                            </span>
                            <span class="badge bg-success-subtle text-success" style="font-size: 10.5px;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i>{{ auth()->user()->is_active ? 'Active & Authorized' : 'Deactivated' }}
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body with Categorized Profiles -->
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    <!-- SECTION 1: Personal Information -->
                    <div class="col-md-6">
                        <div class="card card-enterprise h-100 p-3 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-person-bounding-box text-primary fs-5"></i>
                                <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">Personal Identity</span>
                            </div>
                            <div class="d-flex flex-column gap-2" style="font-size: 12.5px;">
                                <div>
                                    <div class="text-muted small">Full Legal Name</div>
                                    <div class="fw-semibold text-dark">{{ auth()->user()->name }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Corporate Email</div>
                                    <div class="fw-semibold text-dark">{{ auth()->user()->email }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Registered Phone</div>
                                    <div class="fw-semibold text-dark">{{ auth()->user()->phone ?? (auth()->user()->employee?->phone ?? 'Not Registered') }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Assigned Department</div>
                                    <div class="fw-semibold text-dark">{{ auth()->user()->employee?->department?->name ?? 'General Administration' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Employment Information -->
                    <div class="col-md-6">
                        <div class="card card-enterprise h-100 p-3 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-briefcase text-teal fs-5" style="color: #0D9488;"></i>
                                <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">Employment Profile</span>
                            </div>
                            <div class="d-flex flex-column gap-2" style="font-size: 12.5px;">
                                <div>
                                    <div class="text-muted small">Employee ID / Code</div>
                                    <div class="fw-semibold text-dark code-font">{{ auth()->user()->employee_code ?? (auth()->user()->employee?->employee_code ?? 'EMP-' . str_pad(auth()->id(), 4, '0', STR_PAD_LEFT)) }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Legal Employer</div>
                                    <div class="fw-semibold text-dark">{{ $activeCompany?->name ?? (auth()->user()->company?->name ?? 'Vishal Enterprise') }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Plant / Base Location</div>
                                    <div class="fw-semibold text-dark">{{ auth()->user()->employee?->location?->name ?? 'Corporate Head Office' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Date of Joining</div>
                                    <div class="fw-semibold text-dark">
                                        {{ auth()->user()->employee?->date_of_joining ? \Carbon\Carbon::parse(auth()->user()->employee->date_of_joining)->format('d M Y') : 'Active Since System Setup' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Account & Security Information -->
                    <div class="col-12">
                        <div class="card card-enterprise p-3 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-shield-lock text-warning fs-5"></i>
                                <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">Security & Access Telemetry</span>
                            </div>
                            <div class="row g-2" style="font-size: 12px;">
                                <div class="col-md-4">
                                    <span class="text-muted d-block">Access Role:</span>
                                    <span class="badge bg-dark">{{ auth()->user()->role?->name ?? 'user' }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted d-block">Multi-Company Clearance:</span>
                                    <span class="badge {{ auth()->user()->can_switch_company ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ auth()->user()->can_switch_company ? 'Enabled (Multi-Tenant)' : 'Single Company' }}
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted d-block">Compensation Visibility:</span>
                                    <span class="badge bg-secondary-subtle text-secondary">Strict Page 94 RBAC Masked</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer p-3 bg-white border-top d-flex justify-content-between">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Data managed via Enterprise HR & Personnel records.
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

