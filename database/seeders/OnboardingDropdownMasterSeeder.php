<?php

namespace Database\Seeders;

use App\Models\OnboardingDropdownMaster;
use Illuminate\Database\Seeder;

class OnboardingDropdownMasterSeeder extends Seeder
{
    public function run(): void
    {
        $baselineData = [
            'title' => [
                ['name' => 'Mr.', 'code' => 'MR', 'display_order' => 1],
                ['name' => 'Ms.', 'code' => 'MS', 'display_order' => 2],
                ['name' => 'Mrs.', 'code' => 'MRS', 'display_order' => 3],
                ['name' => 'Dr.', 'code' => 'DR', 'display_order' => 4],
            ],
            'gender' => [
                ['name' => 'Male', 'code' => 'MALE', 'display_order' => 1],
                ['name' => 'Female', 'code' => 'FEMALE', 'display_order' => 2],
                ['name' => 'Other', 'code' => 'OTHER', 'display_order' => 3],
            ],
            'blood_group' => [
                ['name' => 'A+', 'code' => 'A_POS', 'display_order' => 1],
                ['name' => 'A-', 'code' => 'A_NEG', 'display_order' => 2],
                ['name' => 'B+', 'code' => 'B_POS', 'display_order' => 3],
                ['name' => 'B-', 'code' => 'B_NEG', 'display_order' => 4],
                ['name' => 'AB+', 'code' => 'AB_POS', 'display_order' => 5],
                ['name' => 'AB-', 'code' => 'AB_NEG', 'display_order' => 6],
                ['name' => 'O+', 'code' => 'O_POS', 'display_order' => 7],
                ['name' => 'O-', 'code' => 'O_NEG', 'display_order' => 8],
            ],
            'nationality' => [
                ['name' => 'Indian', 'code' => 'IND', 'display_order' => 1],
                ['name' => 'Nepalese', 'code' => 'NPL', 'display_order' => 2],
                ['name' => 'Bhutanese', 'code' => 'BTN', 'display_order' => 3],
                ['name' => 'Other National', 'code' => 'OTH', 'display_order' => 4],
            ],
            'disability_category' => [
                ['name' => 'Locomotor Disability / Orthopedic', 'code' => 'DIS_LOCO', 'display_order' => 1],
                ['name' => 'Visual Impairment (Blind / Low Vision)', 'code' => 'DIS_VIS', 'display_order' => 2],
                ['name' => 'Hearing Impairment (Deaf / Hard of Hearing)', 'code' => 'DIS_HEAR', 'display_order' => 3],
                ['name' => 'Speech and Language Disability', 'code' => 'DIS_SPEECH', 'display_order' => 4],
                ['name' => 'Intellectual / Learning Disability', 'code' => 'DIS_INTEL', 'display_order' => 5],
                ['name' => 'Multiple Disabilities', 'code' => 'DIS_MULTI', 'display_order' => 6],
            ],
            'employee_grade' => [
                ['name' => 'E1 - Entry Executive', 'code' => 'E1', 'display_order' => 1],
                ['name' => 'E2 - Executive', 'code' => 'E2', 'display_order' => 2],
                ['name' => 'M1 - Middle Management', 'code' => 'M1', 'display_order' => 3],
                ['name' => 'M2 - Senior Manager', 'code' => 'M2', 'display_order' => 4],
                ['name' => 'W1 - Technical Worker', 'code' => 'W1', 'display_order' => 5],
                ['name' => 'W2 - Shop Floor Operator', 'code' => 'W2', 'display_order' => 6],
            ],
            'division' => [
                ['name' => 'Manufacturing Operations', 'code' => 'DIV_MFG', 'display_order' => 1],
                ['name' => 'Corporate Administration', 'code' => 'DIV_CORP', 'display_order' => 2],
                ['name' => 'Supply Chain & Logistics', 'code' => 'DIV_SCM', 'display_order' => 3],
                ['name' => 'Quality Assurance & Testing', 'code' => 'DIV_QA', 'display_order' => 4],
            ],
            'plant_unit' => [
                ['name' => 'Unit-1 (Main Plant)', 'code' => 'UNIT_1', 'display_order' => 1],
                ['name' => 'Unit-2 (Fabrication Shop)', 'code' => 'UNIT_2', 'display_order' => 2],
                ['name' => 'Unit-3 (Assembly & Paint)', 'code' => 'UNIT_3', 'display_order' => 3],
                ['name' => 'Corporate Head Office', 'code' => 'UNIT_HO', 'display_order' => 4],
            ],
            'project' => [
                ['name' => 'General Operations', 'code' => 'PRJ_GEN', 'display_order' => 1],
                ['name' => 'Project Alpha (Heavy Machining)', 'code' => 'PRJ_ALPHA', 'display_order' => 2],
                ['name' => 'Plant Modernization FY26', 'code' => 'PRJ_MOD', 'display_order' => 3],
            ],
            'onboarding_channel' => [
                ['name' => 'Direct Workforce', 'code' => 'Direct', 'display_order' => 1],
                ['name' => 'Contractor Manpower', 'code' => 'Contractor', 'display_order' => 2],
                ['name' => 'Internal Referral', 'code' => 'Referral', 'display_order' => 3],
                ['name' => 'Staffing Agency', 'code' => 'Agency', 'display_order' => 4],
            ],
            'skill_classification' => [
                ['name' => 'Skilled', 'code' => 'Skilled', 'display_order' => 1],
                ['name' => 'Semi-Skilled', 'code' => 'Semi-Skilled', 'display_order' => 2],
                ['name' => 'Unskilled', 'code' => 'Unskilled', 'display_order' => 3],
                ['name' => 'Highly Skilled Specialist', 'code' => 'Highly Skilled', 'display_order' => 4],
            ],
            'employment_type' => [
                ['name' => 'Permanent', 'code' => 'Permanent', 'display_order' => 1],
                ['name' => 'Intern', 'code' => 'Intern', 'display_order' => 2],
                ['name' => 'Contract', 'code' => 'Contract', 'display_order' => 3],
                ['name' => 'Probationary', 'code' => 'Probationary', 'display_order' => 4],
                ['name' => 'Trainee', 'code' => 'Trainee', 'display_order' => 5],
            ],
            'employment_status' => [
                ['name' => 'Active', 'code' => 'Active', 'display_order' => 1],
                ['name' => 'On Probation', 'code' => 'Probation', 'display_order' => 2],
                ['name' => 'Internship Active', 'code' => 'Intern', 'display_order' => 3],
            ],
            'payment_mode' => [
                ['name' => 'Bank Transfer (NEFT/RTGS)', 'code' => 'Bank Transfer', 'display_order' => 1],
                ['name' => 'Company Cheque', 'code' => 'Cheque', 'display_order' => 2],
                ['name' => 'Cash Voucher', 'code' => 'Cash', 'display_order' => 3],
            ],
            'marital_status' => [
                ['name' => 'Single', 'code' => 'Single', 'display_order' => 1],
                ['name' => 'Married', 'code' => 'Married', 'display_order' => 2],
                ['name' => 'Divorced', 'code' => 'Divorced', 'display_order' => 3],
                ['name' => 'Widowed', 'code' => 'Widowed', 'display_order' => 4],
            ],
            'relationship_type' => [
                ['name' => 'Spouse', 'code' => 'Spouse', 'display_order' => 1],
                ['name' => 'Father', 'code' => 'Father', 'display_order' => 2],
                ['name' => 'Mother', 'code' => 'Mother', 'display_order' => 3],
                ['name' => 'Son', 'code' => 'Son', 'display_order' => 4],
                ['name' => 'Daughter', 'code' => 'Daughter', 'display_order' => 5],
                ['name' => 'Brother', 'code' => 'Brother', 'display_order' => 6],
                ['name' => 'Sister', 'code' => 'Sister', 'display_order' => 7],
                ['name' => 'Guardian', 'code' => 'Guardian', 'display_order' => 8],
                ['name' => 'Friend', 'code' => 'Friend', 'display_order' => 9],
                ['name' => 'Other', 'code' => 'Other', 'display_order' => 10],
            ],
            'religion' => [
                ['name' => 'Hinduism', 'code' => 'HINDU', 'display_order' => 1],
                ['name' => 'Islam', 'code' => 'ISLAM', 'display_order' => 2],
                ['name' => 'Christianity', 'code' => 'CHRISTIAN', 'display_order' => 3],
                ['name' => 'Sikhism', 'code' => 'SIKH', 'display_order' => 4],
                ['name' => 'Buddhism', 'code' => 'BUDDHIST', 'display_order' => 5],
                ['name' => 'Jainism', 'code' => 'JAIN', 'display_order' => 6],
                ['name' => 'Other Religion', 'code' => 'OTHER', 'display_order' => 7],
            ],
            'caste_category' => [
                ['name' => 'General (Open)', 'code' => 'GEN', 'display_order' => 1],
                ['name' => 'OBC (Other Backward Classes)', 'code' => 'OBC', 'display_order' => 2],
                ['name' => 'SC (Scheduled Caste)', 'code' => 'SC', 'display_order' => 3],
                ['name' => 'ST (Scheduled Tribe)', 'code' => 'ST', 'display_order' => 4],
                ['name' => 'EWS (Economically Weaker Section)', 'code' => 'EWS', 'display_order' => 5],
            ],
            'bank_name' => [
                ['name' => 'State Bank of India', 'code' => 'SBI', 'display_order' => 1],
                ['name' => 'HDFC Bank', 'code' => 'HDFC', 'display_order' => 2],
                ['name' => 'ICICI Bank', 'code' => 'ICICI', 'display_order' => 3],
                ['name' => 'Axis Bank', 'code' => 'AXIS', 'display_order' => 4],
                ['name' => 'Bank of Baroda', 'code' => 'BOB', 'display_order' => 5],
                ['name' => 'Punjab National Bank', 'code' => 'PNB', 'display_order' => 6],
                ['name' => 'Kotak Mahindra Bank', 'code' => 'KOTAK', 'display_order' => 7],
                ['name' => 'Canara Bank', 'code' => 'CANARA', 'display_order' => 8],
                ['name' => 'Union Bank of India', 'code' => 'UNION', 'display_order' => 9],
                ['name' => 'IndusInd Bank', 'code' => 'INDUS', 'display_order' => 10],
            ],
            'bank_account_type' => [
                ['name' => 'Corporate Salary Account', 'code' => 'Salary', 'display_order' => 1],
                ['name' => 'Individual Savings Account', 'code' => 'Savings', 'display_order' => 2],
                ['name' => 'Current Business Account', 'code' => 'Current', 'display_order' => 3],
            ],
            'qualification_level' => [
                ['name' => 'Bachelor\'s Degree (B.E./B.Tech/B.Sc/B.Com)', 'code' => 'Bachelor\'s Degree', 'display_order' => 1],
                ['name' => 'Master\'s Degree (M.Tech/MBA/M.Sc)', 'code' => 'Master\'s Degree', 'display_order' => 2],
                ['name' => 'Polytechnic Diploma', 'code' => 'Diploma', 'display_order' => 3],
                ['name' => 'ITI / Vocational Certificate', 'code' => 'ITI / Vocational', 'display_order' => 4],
                ['name' => 'Higher Secondary (12th / HSC)', 'code' => '12th / HSC', 'display_order' => 5],
                ['name' => 'Secondary School (10th / SSC)', 'code' => '10th / SSC', 'display_order' => 6],
                ['name' => 'Doctorate / Ph.D.', 'code' => 'Doctorate', 'display_order' => 7],
            ],
            'degree_name' => [
                ['name' => 'B.E. Mechanical Engineering', 'code' => 'BE_MECH', 'display_order' => 1],
                ['name' => 'B.Tech Computer Science & Eng.', 'code' => 'BTECH_CSE', 'display_order' => 2],
                ['name' => 'B.Tech Electrical & Electronics', 'code' => 'BTECH_EEE', 'display_order' => 3],
                ['name' => 'Diploma in Mechanical Engineering', 'code' => 'DIP_MECH', 'display_order' => 4],
                ['name' => 'Diploma in Electrical Engineering', 'code' => 'DIP_ELEC', 'display_order' => 5],
                ['name' => 'ITI Machinist / Fitter', 'code' => 'ITI_FITTER', 'display_order' => 6],
                ['name' => 'ITI Welder / Fabricator', 'code' => 'ITI_WELDER', 'display_order' => 7],
                ['name' => 'Master of Business Administration (MBA)', 'code' => 'MBA', 'display_order' => 8],
                ['name' => 'Bachelor of Commerce (B.Com)', 'code' => 'BCOM', 'display_order' => 9],
            ],
            'board_university' => [
                ['name' => 'Savitribai Phule Pune University', 'code' => 'SPPU', 'display_order' => 1],
                ['name' => 'University of Mumbai', 'code' => 'MU', 'display_order' => 2],
                ['name' => 'Maharashtra State Board of Technical Education (MSBTE)', 'code' => 'MSBTE', 'display_order' => 3],
                ['name' => 'National Council for Vocational Training (NCVT)', 'code' => 'NCVT', 'display_order' => 4],
                ['name' => 'Central Board of Secondary Education (CBSE)', 'code' => 'CBSE', 'display_order' => 5],
                ['name' => 'Maharashtra State Board (HSC/SSC)', 'code' => 'MSBSHSE', 'display_order' => 6],
                ['name' => 'Visvesvaraya Technological University (VTU)', 'code' => 'VTU', 'display_order' => 7],
                ['name' => 'Gujarat Technological University (GTU)', 'code' => 'GTU', 'display_order' => 8],
            ],
            'skill_category' => [
                ['name' => 'Technical Operations', 'code' => 'Technical', 'display_order' => 1],
                ['name' => 'Plant Machinery', 'code' => 'Operational', 'display_order' => 2],
                ['name' => 'Industrial Safety & PPE', 'code' => 'Safety', 'display_order' => 3],
                ['name' => 'Quality Assurance & Testing', 'code' => 'Quality Control', 'display_order' => 4],
                ['name' => 'Supervisory & Leadership', 'code' => 'Leadership', 'display_order' => 5],
            ],
            'skill_name' => [
                ['name' => 'CNC Machine Operation', 'code' => 'CNC_OP', 'display_order' => 1],
                ['name' => 'TIG / MIG Precision Welding', 'code' => 'TIG_WELD', 'display_order' => 2],
                ['name' => 'PLC Automation & Scada', 'code' => 'PLC_AUTO', 'display_order' => 3],
                ['name' => 'Quality Metrology & CMM Inspection', 'code' => 'QC_METRO', 'display_order' => 4],
                ['name' => 'Forklift & Crane Rigging', 'code' => 'FORK_RIG', 'display_order' => 5],
                ['name' => '5S & Kaizen Lean Manufacturing', 'code' => 'LEAN_5S', 'display_order' => 6],
                ['name' => 'Hydraulics & Pneumatics Maintenance', 'code' => 'HYD_MAINT', 'display_order' => 7],
                ['name' => 'Plant EHS & Fire Fighting', 'code' => 'EHS_FIRE', 'display_order' => 8],
            ],
            'skill_proficiency' => [
                ['name' => 'Intermediate (Autonomous Worker)', 'code' => 'Intermediate', 'display_order' => 1],
                ['name' => 'Beginner (Under Supervision)', 'code' => 'Beginner', 'display_order' => 2],
                ['name' => 'Advanced (Senior Technician)', 'code' => 'Advanced', 'display_order' => 3],
                ['name' => 'Expert (Master Craftsman / Trainer)', 'code' => 'Expert', 'display_order' => 4],
            ],
            'visa_type' => [
                ['name' => 'Employment / Work Visa (Class E)', 'code' => 'VISA_EMP', 'display_order' => 1],
                ['name' => 'Business Visa (Class B)', 'code' => 'VISA_BIZ', 'display_order' => 2],
                ['name' => 'Permanent Resident / OCI', 'code' => 'VISA_PR', 'display_order' => 3],
                ['name' => 'Project Specific Work Permit', 'code' => 'VISA_PERMIT', 'display_order' => 4],
            ],
            'country' => [
                ['name' => 'India', 'code' => 'IN', 'display_order' => 1],
                ['name' => 'United States', 'code' => 'US', 'display_order' => 2],
                ['name' => 'United Arab Emirates', 'code' => 'AE', 'display_order' => 3],
                ['name' => 'United Kingdom', 'code' => 'GB', 'display_order' => 4],
                ['name' => 'Germany', 'code' => 'DE', 'display_order' => 5],
                ['name' => 'Singapore', 'code' => 'SG', 'display_order' => 6],
                ['name' => 'Japan', 'code' => 'JP', 'display_order' => 7],
                ['name' => 'Nepal', 'code' => 'NP', 'display_order' => 8],
            ],
            'document_type' => [
                ['name' => 'Aadhaar Card', 'code' => 'Aadhaar Card', 'display_order' => 1, 'metadata' => ['category' => 'Identity']],
                ['name' => 'PAN Card', 'code' => 'PAN Card', 'display_order' => 2, 'metadata' => ['category' => 'Identity']],
                ['name' => 'Passport', 'code' => 'Passport', 'display_order' => 3, 'metadata' => ['category' => 'Visa & Immigration']],
                ['name' => 'Voter ID Card', 'code' => 'Voter ID Card', 'display_order' => 4, 'metadata' => ['category' => 'Identity']],
                ['name' => 'Driving Licence', 'code' => 'Driving Licence', 'display_order' => 5, 'metadata' => ['category' => 'Identity']],
                ['name' => 'Address Proof (Utility / Rent)', 'code' => 'Address Proof', 'display_order' => 6, 'metadata' => ['category' => 'Identity']],
                ['name' => 'Profile Photo (High Res)', 'code' => 'Profile Photo', 'display_order' => 7, 'metadata' => ['category' => 'Identity']],
                ['name' => 'Educational Degree / Certificate', 'code' => 'Educational Degree / Certificate', 'display_order' => 8, 'metadata' => ['category' => 'Educational']],
                ['name' => 'Secondary School Certificate (10th/12th)', 'code' => 'Secondary School Certificate (10th/12th)', 'display_order' => 9, 'metadata' => ['category' => 'Educational']],
                ['name' => 'Resume / CV', 'code' => 'Resume / CV', 'display_order' => 10, 'metadata' => ['category' => 'Employment']],
                ['name' => 'Experience / Relieving Letter', 'code' => 'Experience / Relieving Letter', 'display_order' => 11, 'metadata' => ['category' => 'Employment']],
                ['name' => 'Appointment Letter', 'code' => 'Appointment Letter', 'display_order' => 12, 'metadata' => ['category' => 'Employment']],
                ['name' => 'Offer Letter', 'code' => 'Offer Letter', 'display_order' => 13, 'metadata' => ['category' => 'Employment']],
                ['name' => 'Bank Passbook / Cancelled Cheque', 'code' => 'Bank Passbook / Cancelled Cheque', 'display_order' => 14, 'metadata' => ['category' => 'Banking']],
                ['name' => 'Visa / Work Permit', 'code' => 'Visa / Work Permit', 'display_order' => 15, 'metadata' => ['category' => 'Visa & Immigration']],
                ['name' => 'Medical Fitness Certificate', 'code' => 'Medical Fitness Certificate', 'display_order' => 16, 'metadata' => ['category' => 'Health']],
                ['name' => 'Other Document', 'code' => 'Other Document', 'display_order' => 17, 'metadata' => ['category' => 'Other']],
            ],
            'uniform_size' => [
                ['name' => 'S (Small - 36/38)', 'code' => 'S', 'display_order' => 1],
                ['name' => 'M (Medium - 38/40)', 'code' => 'M', 'display_order' => 2],
                ['name' => 'L (Large - 40/42)', 'code' => 'L', 'display_order' => 3],
                ['name' => 'XL (Extra Large - 42/44)', 'code' => 'XL', 'display_order' => 4],
                ['name' => 'XXL (Double Extra Large - 44/46)', 'code' => 'XXL', 'display_order' => 5],
                ['name' => '3XL (Triple Extra Large - 46/48)', 'code' => '3XL', 'display_order' => 6],
            ],
            'parking_type' => [
                ['name' => 'No Parking Required', 'code' => 'No', 'display_order' => 1],
                ['name' => 'Two Wheeler Bay (Motorcycle / Scooter)', 'code' => 'Two Wheeler', 'display_order' => 2],
                ['name' => 'Four Wheeler Bay (Sedan / SUV)', 'code' => 'Four Wheeler', 'display_order' => 3],
            ],
        ];

        foreach ($baselineData as $category => $items) {
            foreach ($items as $item) {
                OnboardingDropdownMaster::updateOrCreate(
                    [
                        'category' => $category,
                        'code' => $item['code'],
                        'tenant_id' => null,
                        'company_id' => null,
                    ],
                    [
                        'name' => $item['name'],
                        'display_order' => $item['display_order'],
                        'description' => $item['description'] ?? null,
                        'is_active' => true,
                        'metadata' => $item['metadata'] ?? null,
                    ]
                );
            }
        }
    }
}

