<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndianDataService
{
    private static array $femaleFirstNames = [
        'Sunita', 'Anjali', 'Priyanka', 'Moushumi', 'Aparna', 'Rupa', 'Tanushree',
        'Sreemoyee', 'Malati', 'Rekha', 'Anita', 'Shreya', 'Pratima', 'Mamata',
        'Swapna', 'Madhumita', 'Arpita', 'Kakali', 'Payel', 'Soma', 'Debjani',
        'Rumki', 'Jharna', 'Supriya', 'Kalyani', 'Nandita', 'Lipika', 'Manju',
    ];

    private static array $maleFirstNames = [
        'Rajesh', 'Amit', 'Sourav', 'Debabrata', 'Bikram', 'Subhas', 'Pradip',
        'Tapas', 'Sanjay', 'Rahul', 'Anupam', 'Anirban', 'Alok', 'Swapan',
        'Tarun', 'Gautam', 'Arup', 'Biswajit', 'Partha', 'Abhijit', 'Dipankar',
        'Subrata', 'Chandan', 'Shibaji', 'Koushik', 'Manas', 'Prasenjit', 'Sanjoy',
    ];

    private static array $surnames = [
        'Banerjee', 'Chatterjee', 'Mukherjee', 'Ghosh', 'Mondal', 'Das', 'Roy',
        'Sen', 'Dutta', 'Bhattacharya', 'Chakraborty', 'Paul', 'Sarkar', 'Bhowmick',
        'Majumder', 'Ganguly', 'Samanta', 'Haldar', 'Biswas', 'Karmakar', 'Pramanik',
        'Naskar', 'Adhikari', 'Mahato', 'Bauri', 'Bagdi', 'Kundu', 'Mitra',
    ];

    private static array $paras = [
        'Netaji Pally', 'Rabindra Sarani', 'Station Road', 'Vivekananda Nagar',
        'College Para', 'Milan Pally', 'Shanti Nagar', 'Bazar Road', 'Nutanganj',
        'Dakshinpara', 'Uttarpara', 'Madhya Pally', 'Hospital Road', 'School Danga',
    ];

    private static array $banks = [
        ['name' => 'State Bank of India', 'ifsc' => 'SBIN0001234'],
        ['name' => 'Punjab National Bank', 'ifsc' => 'PUNB0123400'],
        ['name' => 'Bank of Baroda', 'ifsc' => 'BARB0KOLKAT'],
        ['name' => 'Canara Bank', 'ifsc' => 'CNRB0001234'],
        ['name' => 'Union Bank of India', 'ifsc' => 'UBIN0531234'],
        ['name' => 'UCO Bank', 'ifsc' => 'UCBA0000456'],
        ['name' => 'Indian Bank', 'ifsc' => 'IDIB000K123'],
    ];

    private static array $rationTypes = ['AAY', 'PHH', 'SPHH', 'RKSY1', 'RKSY2'];

    private static array $castes = ['General', 'SC', 'ST', 'OBC'];

    private static array $religions = ['Hinduism', 'Islam', 'Christianity', 'Buddhism'];

    /**
     * Generate a complete realistic Indian Family dataset with members.
     *
     * @param  string|null  $targetAreaType  'Urban' or 'Rural'
     * @return array<string, mixed>
     */
    public static function generateFamily(?int $targetDistrict = null, ?string $targetAreaType = null): array
    {
        $districts = MasterDataService::getDistricts();
        if (empty($districts)) {
            $district = ['id' => 315, 'text' => 'KMC'];
        } else {
            if ($targetDistrict) {
                $filtered = array_values(array_filter($districts, fn ($d) => $d['id'] == $targetDistrict));
                $district = ! empty($filtered) ? $filtered[0] : $districts[array_rand($districts)];
            } else {
                $district = $districts[array_rand($districts)];
            }
        }

        $districtCode = (int) $district['id'];

        // Determine area type
        if ($districtCode === 315) {
            // KMC is always Urban
            $areaType = 'Urban';
        } else {
            $areaType = $targetAreaType ?: (random_int(0, 1) === 1 ? 'Urban' : 'Rural');
        }

        $blockMcCode = null;
        $gpWardCode = null;
        $subDistrictCode = null;
        $ulbCode = null;
        $locationName = '';

        if ($areaType === 'Urban') {
            $subdistricts = MasterDataService::getSubdistricts($districtCode);
            if (! empty($subdistricts)) {
                $sub = $subdistricts[array_rand($subdistricts)];
                $subDistrictCode = (int) $sub['id'];
                $ulbs = MasterDataService::getUlbs($districtCode, $subDistrictCode);
            } else {
                $ulbs = MasterDataService::getUlbs($districtCode);
            }

            if (empty($ulbs)) {
                $ulbs = MasterDataService::getUlbs();
            }

            if (! empty($ulbs)) {
                $ulb = $ulbs[array_rand($ulbs)];
                $ulbCode = (int) $ulb['id'];
                $blockMcCode = $ulbCode;
                $subDistrictCode = (int) ($ulb['sub_district_code'] ?? $subDistrictCode);

                $wards = MasterDataService::getUlbWards($ulbCode);
                if (! empty($wards)) {
                    $ward = $wards[array_rand($wards)];
                    $gpWardCode = (int) $ward['id'];
                    $locationName = ($ulb['text'] ?? 'Municipality').', '.($ward['text'] ?? 'Ward');
                } else {
                    $gpWardCode = random_int(1001, 1050);
                    $locationName = ($ulb['text'] ?? 'Municipality').', Ward '.$gpWardCode;
                }
            } else {
                $blockMcCode = 250299;
                $gpWardCode = 2124;
                $ulbCode = 250299;
                $locationName = 'KMC Area, Ward 24';
            }
        } else {
            // Rural
            $blocks = MasterDataService::getBlocks($districtCode);
            if (! empty($blocks)) {
                $block = $blocks[array_rand($blocks)];
                $blockMcCode = (int) $block['id'];

                $gps = MasterDataService::getGps($districtCode, $blockMcCode);
                if (! empty($gps)) {
                    $gp = $gps[array_rand($gps)];
                    $gpWardCode = (int) $gp['id'];
                    $locationName = ($block['text'] ?? 'Block').' Block, '.($gp['text'] ?? 'GP').' GP';
                } else {
                    $gpWardCode = random_int(10001, 19999);
                    $locationName = ($block['text'] ?? 'Block').' Block, GP '.$gpWardCode;
                }
            } else {
                $blockMcCode = 2744;
                $gpWardCode = 110658;
                $locationName = 'Rural Area, GP 110658';
            }
        }

        $surname = self::$surnames[array_rand(self::$surnames)];
        $para = self::$paras[array_rand(self::$paras)];
        $houseNo = random_int(1, 180).'/'.chr(random_int(65, 68));
        $pincode = random_int(700001, 743999);
        $address = "{$houseNo}, {$para}, {$locationName}, PIN - {$pincode}";

        // Family Master attributes
        $hasRc = (bool) random_int(0, 1);
        $rcHouseholdId = $hasRc ? 'WB'.random_int(10000000, 99999999) : null;
        $totalMembers = random_int(2, 4);

        // Members Generation
        // Rule: "family may contain a female member . if it is and the age is between 25-60 years then the column 'applying_for_annapurna_bhandar' be true"
        // Let's guarantee at least one female aged 25-60 so the Annapurna rule is demonstrated!
        $members = [];

        // Head of family: 65% Female HOF (promotes Annapurna Bhandar), 35% Male HOF
        $hofIsFemale = (random_int(1, 100) <= 65);
        $hofFirstName = $hofIsFemale
            ? self::$femaleFirstNames[array_rand(self::$femaleFirstNames)]
            : self::$maleFirstNames[array_rand(self::$maleFirstNames)];
        $hofName = "{$hofFirstName} {$surname}";
        $hofGender = $hofIsFemale ? 'Female' : 'Male';

        // Age for HOF between 28 and 56
        $hofAge = random_int(28, 56);
        $hofDob = Carbon::now()->subYears($hofAge)->subDays(random_int(1, 350))->format('Y-m-d');

        // Annapurna check for HOF:
        // female and age between 25 and 60
        $hofAnnapurna = ($hofGender === 'Female' && $hofAge >= 25 && $hofAge <= 60);

        $mobile = (string) random_int(6, 9).(string) random_int(100000000, 999999999);
        $bank = self::$banks[array_rand(self::$banks)];
        $caste = self::$castes[array_rand(self::$castes)];
        $religion = self::$religions[array_rand(self::$religions)];

        $hofMember = [
            'is_hof' => true,
            'member_name' => $hofName,
            'gender' => $hofGender,
            'date_of_birth' => $hofDob,
            'relation_with_head_of_family' => 'Self',
            'aadhaar_no' => AadhaarService::generate(),
            'mobile_no' => $mobile,
            'applying_for_annapurna_bhandar' => $hofAnnapurna,
            'bank_name' => $bank['name'],
            'bank_account_no' => (string) random_int(10000000000, 99999999999),
            'ifsc_code' => $bank['ifsc'],
            'epic_no' => 'WB'.random_int(10000000, 99999999),
            'social_category' => $caste,
            'religion' => $religion,
            'has_digital_ration_card' => $hasRc,
            'digital_ration_card_no' => $hasRc ? 'RC'.random_int(10000000, 99999999) : null,
            'digital_ration_card_type' => $hasRc ? self::$rationTypes[array_rand(self::$rationTypes)] : null,
            'lifting_monthly_ration' => $hasRc,
            'has_health_insurance' => true,
            'health_insurance_type' => 'Swasthya Sathi',
            'health_insurance_sum_assured' => 500000.00,
            'health_insurance_annual_premium' => 0.00,
            'pays_income_or_professional_tax' => false,
            'has_pan_card' => (bool) random_int(0, 1),
            'pan_name' => $hofName,
            'pan_no' => 'ABCDE'.random_int(1000, 9999).chr(random_int(65, 90)),
            'is_registered_gst' => false,
            'holds_constitutional_post' => false,
            'is_govt_pensioner' => false,
            'has_three_pucca_rooms' => false,
            'owns_land' => true,
            'landholding_size_decimals' => (float) random_int(2, 25),
            'has_four_wheeler' => false,
            'is_child' => false,
            'highest_educational_qualifications' => '10th/Madhyamik',
        ];

        $members[] = $hofMember;

        // Member 2: Spouse
        if ($totalMembers >= 2) {
            $spouseIsFemale = ! $hofIsFemale;
            $spouseFirstName = $spouseIsFemale
                ? self::$femaleFirstNames[array_rand(self::$femaleFirstNames)]
                : self::$maleFirstNames[array_rand(self::$maleFirstNames)];
            $spouseName = "{$spouseFirstName} {$surname}";
            $spouseGender = $spouseIsFemale ? 'Female' : 'Male';
            $spouseAge = max(24, $hofAge + random_int(-5, 5));
            $spouseDob = Carbon::now()->subYears($spouseAge)->subDays(random_int(1, 350))->format('Y-m-d');

            // If spouse is female and age between 25-60 -> applying_for_annapurna_bhandar = true
            $spouseAnnapurna = ($spouseGender === 'Female' && $spouseAge >= 25 && $spouseAge <= 60);

            $members[] = [
                'is_hof' => false,
                'member_name' => $spouseName,
                'gender' => $spouseGender,
                'date_of_birth' => $spouseDob,
                'relation_with_head_of_family' => 'Spouse',
                'aadhaar_no' => AadhaarService::generate(),
                'mobile_no' => (string) random_int(6, 9).(string) random_int(100000000, 999999999),
                'applying_for_annapurna_bhandar' => $spouseAnnapurna,
                'bank_name' => $bank['name'],
                'bank_account_no' => (string) random_int(10000000000, 99999999999),
                'ifsc_code' => $bank['ifsc'],
                'epic_no' => 'WB'.random_int(10000000, 99999999),
                'social_category' => $caste,
                'religion' => $religion,
                'has_digital_ration_card' => $hasRc,
                'digital_ration_card_no' => $hasRc ? 'RC'.random_int(10000000, 99999999) : null,
                'digital_ration_card_type' => $hasRc ? self::$rationTypes[array_rand(self::$rationTypes)] : null,
                'lifting_monthly_ration' => $hasRc,
                'has_health_insurance' => true,
                'health_insurance_type' => 'Swasthya Sathi',
                'pays_income_or_professional_tax' => false,
                'has_pan_card' => false,
                'is_registered_gst' => false,
                'holds_constitutional_post' => false,
                'is_govt_pensioner' => false,
                'has_three_pucca_rooms' => false,
                'owns_land' => false,
                'has_four_wheeler' => false,
                'is_child' => false,
                'highest_educational_qualifications' => '12th/Uchacha Madhyamik',
            ];
        }

        // Member 3: Child (Son or Daughter)
        if ($totalMembers >= 3) {
            $childIsFemale = (bool) random_int(0, 1);
            $childFirstName = $childIsFemale
                ? self::$femaleFirstNames[array_rand(self::$femaleFirstNames)]
                : self::$maleFirstNames[array_rand(self::$maleFirstNames)];
            $childName = "{$childFirstName} {$surname}";
            $childGender = $childIsFemale ? 'Female' : 'Male';
            $childAge = random_int(6, 17);
            $childDob = Carbon::now()->subYears($childAge)->subDays(random_int(1, 350))->format('Y-m-d');

            // Age < 25, so applying_for_annapurna_bhandar is false
            $members[] = [
                'is_hof' => false,
                'member_name' => $childName,
                'gender' => $childGender,
                'date_of_birth' => $childDob,
                'relation_with_head_of_family' => $childIsFemale ? 'Daughter' : 'Son',
                'aadhaar_no' => AadhaarService::generate(),
                'mobile_no' => $mobile,
                'applying_for_annapurna_bhandar' => false,
                'bank_name' => null,
                'bank_account_no' => null,
                'ifsc_code' => null,
                'epic_no' => null,
                'social_category' => $caste,
                'religion' => $religion,
                'has_digital_ration_card' => $hasRc,
                'digital_ration_card_no' => $hasRc ? 'RC'.random_int(10000000, 99999999) : null,
                'digital_ration_card_type' => $hasRc ? self::$rationTypes[array_rand(self::$rationTypes)] : null,
                'lifting_monthly_ration' => $hasRc,
                'has_health_insurance' => true,
                'health_insurance_type' => 'Swasthya Sathi',
                'pays_income_or_professional_tax' => false,
                'has_pan_card' => false,
                'is_registered_gst' => false,
                'holds_constitutional_post' => false,
                'is_govt_pensioner' => false,
                'has_three_pucca_rooms' => false,
                'owns_land' => false,
                'has_four_wheeler' => false,
                'is_child' => true,
                'school_name' => 'Primary / Secondary Model School',
                'school_grade' => (string) min(12, max(1, $childAge - 5)),
                'school_type' => 'Govt / Govt Aided / Sponsored School',
                'highest_educational_qualifications' => 'Lower than 10th/Madhyamik',
            ];
        }

        // Member 4: Elderly parent / mother (e.g. 62 years -> above 60, not eligible) OR another daughter
        if ($totalMembers >= 4) {
            $isMother = (bool) random_int(0, 1);
            if ($isMother) {
                $mAge = random_int(61, 75);
                $mDob = Carbon::now()->subYears($mAge)->subDays(random_int(1, 350))->format('Y-m-d');
                $mName = self::$femaleFirstNames[array_rand(self::$femaleFirstNames)]." {$surname}";

                // Age > 60, not eligible for Annapurna Bhandar (supposed to be transferred to Old Age Pension)
                $members[] = [
                    'is_hof' => false,
                    'member_name' => $mName,
                    'gender' => 'Female',
                    'date_of_birth' => $mDob,
                    'relation_with_head_of_family' => 'Mother',
                    'aadhaar_no' => AadhaarService::generate(),
                    'mobile_no' => $mobile,
                    'applying_for_annapurna_bhandar' => false,
                    'bank_name' => $bank['name'],
                    'bank_account_no' => (string) random_int(10000000000, 99999999999),
                    'ifsc_code' => $bank['ifsc'],
                    'epic_no' => 'WB'.random_int(10000000, 99999999),
                    'social_category' => $caste,
                    'religion' => $religion,
                    'has_digital_ration_card' => $hasRc,
                    'digital_ration_card_no' => $hasRc ? 'RC'.random_int(10000000, 99999999) : null,
                    'digital_ration_card_type' => $hasRc ? self::$rationTypes[array_rand(self::$rationTypes)] : null,
                    'lifting_monthly_ration' => $hasRc,
                    'has_health_insurance' => true,
                    'health_insurance_type' => 'Swasthya Sathi',
                    'pays_income_or_professional_tax' => false,
                    'has_pan_card' => false,
                    'is_registered_gst' => false,
                    'holds_constitutional_post' => false,
                    'is_govt_pensioner' => false,
                    'has_three_pucca_rooms' => false,
                    'owns_land' => false,
                    'has_four_wheeler' => false,
                    'is_child' => false,
                    'highest_educational_qualifications' => 'Illiterate',
                ];
            } else {
                $child2FirstName = self::$maleFirstNames[array_rand(self::$maleFirstNames)];
                $child2Name = "{$child2FirstName} {$surname}";
                $child2Age = random_int(3, 10);
                $child2Dob = Carbon::now()->subYears($child2Age)->subDays(random_int(1, 350))->format('Y-m-d');
                $members[] = [
                    'is_hof' => false,
                    'member_name' => $child2Name,
                    'gender' => 'Male',
                    'date_of_birth' => $child2Dob,
                    'relation_with_head_of_family' => 'Son',
                    'aadhaar_no' => AadhaarService::generate(),
                    'mobile_no' => $mobile,
                    'applying_for_annapurna_bhandar' => false,
                    'bank_name' => null,
                    'bank_account_no' => null,
                    'ifsc_code' => null,
                    'epic_no' => null,
                    'social_category' => $caste,
                    'religion' => $religion,
                    'has_digital_ration_card' => $hasRc,
                    'digital_ration_card_no' => $hasRc ? 'RC'.random_int(10000000, 99999999) : null,
                    'digital_ration_card_type' => $hasRc ? self::$rationTypes[array_rand(self::$rationTypes)] : null,
                    'lifting_monthly_ration' => $hasRc,
                    'has_health_insurance' => true,
                    'health_insurance_type' => 'Swasthya Sathi',
                    'pays_income_or_professional_tax' => false,
                    'has_pan_card' => false,
                    'is_registered_gst' => false,
                    'holds_constitutional_post' => false,
                    'is_govt_pensioner' => false,
                    'has_three_pucca_rooms' => false,
                    'owns_land' => false,
                    'has_four_wheeler' => false,
                    'is_child' => true,
                    'school_name' => 'Primary Model School',
                    'school_grade' => (string) min(5, max(1, $child2Age - 5)),
                    'school_type' => 'Govt / Govt Aided / Sponsored School',
                    'highest_educational_qualifications' => 'Lower than 10th/Madhyamik',
                ];
            }
        }

        return [
            'family' => [
                'lgd_district_code' => $districtCode,
                'lgd_block_mc_code' => $blockMcCode,
                'lgd_gp_ward_code' => $gpWardCode,
                'area_type' => $areaType,
                'ulb' => $ulbCode,
                'address' => $address,
                'total_family_members' => count($members),
                'has_digital_ration_card' => $hasRc,
                'ration_card_household_id' => $rcHouseholdId,
                'lifting_monthly_ration' => $hasRc,
                'total_annual_family_income' => random_int(60, 180) * 1000,
                'no_of_literate_adults' => 2,
                'no_of_illiterate_adults' => count($members) > 3 ? 1 : 0,
                'is_agreed' => true,
                'application_status' => 'DRAFT',
            ],
            'district_name' => $district['text'] ?? 'District',
            'location_name' => $locationName,
            'members' => $members,
        ];
    }

    /**
     * Bulk seed realistic Indian families with random members directly into PostgreSQL.
     * Supports presets: 10, 20, 25, 50, 75, 100, 150, 200, 250, 450, 500 up to 1000.
     *
     * @param  int  $count  Number of families to seed
     * @param  int|null  $districtCode  Optional target district code
     * @param  string|null  $areaType  Optional area classification ('Urban' or 'Rural')
     * @param  callable|null  $progressCallback  Optional progress callback fn(int $current, int $total)
     * @return array<string, mixed>
     */
    public static function seedBatch(int $count, ?int $districtCode = null, ?string $areaType = null, ?callable $progressCallback = null): array
    {
        $count = max(1, min($count, 1000));
        $chunkSize = 50;
        $totalSeededFamilies = 0;
        $totalSeededMembers = 0;
        $totalAnnapurnaBeneficiaries = 0;
        $insertedSummaries = [];

        $remaining = $count;

        while ($remaining > 0) {
            $currentChunk = min($remaining, $chunkSize);

            DB::transaction(function () use (
                $currentChunk,
                $districtCode,
                $areaType,
                &$totalSeededFamilies,
                &$totalSeededMembers,
                &$totalAnnapurnaBeneficiaries,
                &$insertedSummaries
            ) {
                // Pre-generate UUIDs for families and application IDs in bulk
                $famUuids = DB::select("SELECT dbt_apy.generate_zone_uuid(1) as uuid FROM generate_series(1, {$currentChunk})");
                $appUuids = DB::select("SELECT dbt_apy.generate_zone_uuid(2) as uuid FROM generate_series(1, {$currentChunk})");

                $generatedList = [];
                $totalMembersInChunk = 0;

                for ($i = 0; $i < $currentChunk; $i++) {
                    $gen = self::generateFamily($districtCode, $areaType);
                    $mCount = count($gen['members']);
                    $totalMembersInChunk += $mCount;
                    $generatedList[] = [
                        'gen' => $gen,
                        'mCount' => $mCount,
                        'famUuid' => $famUuids[$i]->uuid,
                        'appUuid' => $appUuids[$i]->uuid,
                    ];
                }

                // Pre-generate all member UUIDs for this chunk in ONE query
                $memberUuids = DB::select("SELECT dbt_apy.generate_zone_uuid(3) as uuid FROM generate_series(1, {$totalMembersInChunk})");
                $uuidPointer = 0;

                $familiesToInsert = [];
                $now = now();

                foreach ($generatedList as $item) {
                    $famData = $item['gen']['family'];
                    $famData['id'] = $item['famUuid'];
                    $famData['tmp_application_id'] = $item['appUuid'];
                    $famData['create_mode'] = 'o';
                    $famData['created_at'] = $now;
                    $famData['updated_at'] = $now;
                    $famData['kafka_updation_timestamp'] = $now;
                    $famData['under_enquiry'] = false;
                    $famData['transfer_out'] = false;
                    $familiesToInsert[] = $famData;

                    $membersData = $item['gen']['members'];
                    $hofUuid = $memberUuids[$uuidPointer]->uuid; // first member is always HOF

                    foreach ($membersData as $m) {
                        $mUuid = $memberUuids[$uuidPointer++]->uuid;

                        if (! empty($m['applying_for_annapurna_bhandar'])) {
                            $totalAnnapurnaBeneficiaries++;
                        }

                        $m['id'] = $mUuid;
                        $m['family_id'] = $item['famUuid'];
                        $m['lgd_district_code'] = $famData['lgd_district_code'];
                        $m['lgd_block_mc_code'] = $famData['lgd_block_mc_code'];
                        $m['lgd_gp_ward_code'] = $famData['lgd_gp_ward_code'];
                        $m['head_of_family_id'] = $hofUuid;
                        $m['application_status'] = 'DRAFT';
                        $m['create_mode'] = 'o';
                        $m['created_at'] = $now;
                        $m['updated_at'] = $now;
                        $m['kafka_updation_timestamp'] = $now;
                        $m['under_enquiry'] = false;
                        $m['july_payment'] = false;
                        $m['govt_self_decl'] = false;
                        $m['govt_fd'] = false;
                        $m['govt_wbhs'] = false;
                        $m['govt_asha'] = false;
                        $m['govt_icds'] = false;
                        $m['three_room_urb'] = false;
                        $m['land_mu_corp'] = false;
                        $m['oth_madrassah'] = false;
                        $m['not_in_asdd_fd'] = true;
                        $m['lb_ben_sir'] = false;
                        $m['govt_fd_not_permanent'] = false;
                        $m['govt_fd_sch'] = false;
                        $m['govt_fd_gpzp'] = false;
                        $m['is_excluded'] = false;
                        $m['aug_prep'] = false;
                        $m['priority_add'] = false;
                        $m['priority_del'] = false;
                        $m['transfer_out'] = false;
                        $m['is_oap'] = false;
                        $m['jm_status'] = false;
                        $m['sep_priority'] = false;

                        DB::table('dbt_apy.family_members')->insert($m);
                    }

                    $totalSeededMembers += count($membersData);
                    $totalSeededFamilies++;

                    if (count($insertedSummaries) < 50) {
                        $insertedSummaries[] = [
                            'family_id' => $item['famUuid'],
                            'district' => $item['gen']['district_name'],
                            'location' => $item['gen']['location_name'],
                            'members_count' => count($membersData),
                            'hof' => $membersData[0]['member_name'] ?? 'HOF',
                        ];
                    }
                }

                DB::table('dbt_apy.families')->insert($familiesToInsert);
            });

            $remaining -= $currentChunk;

            if ($progressCallback !== null) {
                $progressCallback($totalSeededFamilies, $count);
            }
        }

        return [
            'success' => true,
            'message' => "Successfully seeded {$totalSeededFamilies} Indian families ({$totalSeededMembers} members)!",
            'count' => $totalSeededFamilies,
            'total_members' => $totalSeededMembers,
            'annapurna_recipients' => $totalAnnapurnaBeneficiaries,
            'families' => $insertedSummaries,
        ];
    }
}
