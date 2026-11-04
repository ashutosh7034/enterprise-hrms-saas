<?php

namespace Database\Seeders;

use App\Models\SubscriptionFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed available subscription plans and their module feature entitlements.
     * Idempotent: Can be run multiple times safely.
     */
    public function run(): void
    {
        $allFeatures = [
            'employee_management' => 'Workforce & Onboarding',
            'attendance_management' => 'Time & Attendance Engine',
            'leave_management' => 'Leave Policies & Sandwich Rules',
            'payroll_management' => 'Payroll & Statutory Compliance',
            'asset_management' => 'Asset Lifecycle & Recovery',
            'document_management' => 'Document Generation & ESS',
            'reports' => 'Enterprise Analytics & Exports',
            'recruitment' => 'Talent Acquisition & Job Openings',
            'performance_management' => 'Performance & Appraisals',
            'employee_self_service' => 'Employee Self Service (ESS)',
            'manager_self_service' => 'Manager Self Service (MSS)',
        ];

        // 1. Starter Tier
        $starter = SubscriptionPlan::updateOrCreate(
            ['plan_code' => 'PLAN-STARTER'],
            [
                'name' => 'Starter Tier',
                'description' => 'Essential workforce, time tracking, and leave management for small businesses.',
                'billing_interval' => 'monthly',
                'price' => 15000.00,
                'currency' => 'INR',
                'employee_limit' => 50,
                'company_limit' => 1,
                'status' => 'active',
            ]
        );
        $starterEnabled = [
            'employee_management',
            'attendance_management',
            'leave_management',
            'employee_self_service',
            'manager_self_service',
            'reports',
        ];
        foreach ($allFeatures as $code => $name) {
            SubscriptionFeature::updateOrCreate(
                ['subscription_plan_id' => $starter->id, 'feature_code' => $code],
                [
                    'feature_name' => $name,
                    'is_enabled' => in_array($code, $starterEnabled),
                ]
            );
        }

        // 2. Professional Tier
        $pro = SubscriptionPlan::updateOrCreate(
            ['plan_code' => 'PLAN-PRO'],
            [
                'name' => 'Professional Tier',
                'description' => 'Comprehensive HRMS with payroll, assets, and document engine for growing mid-sized organizations.',
                'billing_interval' => 'monthly',
                'price' => 35000.00,
                'currency' => 'INR',
                'employee_limit' => 250,
                'company_limit' => 3,
                'status' => 'active',
            ]
        );
        $proEnabled = [
            'employee_management',
            'attendance_management',
            'leave_management',
            'payroll_management',
            'asset_management',
            'document_management',
            'employee_self_service',
            'manager_self_service',
            'reports',
        ];
        foreach ($allFeatures as $code => $name) {
            SubscriptionFeature::updateOrCreate(
                ['subscription_plan_id' => $pro->id, 'feature_code' => $code],
                [
                    'feature_name' => $name,
                    'is_enabled' => in_array($code, $proEnabled),
                ]
            );
        }

        // 3. Enterprise Tier
        $enterprise = SubscriptionPlan::updateOrCreate(
            ['plan_code' => 'PLAN-ENTERPRISE'],
            [
                'name' => 'Enterprise Tier',
                'description' => 'Full-suite workforce, multi-company group management, and unlimited compliance modules for large enterprises.',
                'billing_interval' => 'yearly',
                'price' => 120000.00,
                'currency' => 'INR',
                'employee_limit' => 5000,
                'company_limit' => 20,
                'status' => 'active',
            ]
        );
        foreach ($allFeatures as $code => $name) {
            SubscriptionFeature::updateOrCreate(
                ['subscription_plan_id' => $enterprise->id, 'feature_code' => $code],
                [
                    'feature_name' => $name,
                    'is_enabled' => true,
                ]
            );
        }
    }
}

