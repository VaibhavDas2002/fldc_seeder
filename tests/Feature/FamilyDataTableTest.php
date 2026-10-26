<?php

namespace Tests\Feature;

use App\Livewire\FamilyDataTable;
use App\Models\Family;
use App\Services\AadhaarService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FamilyDataTableTest extends TestCase
{
    /**
     * Test Livewire component renders and displays families.
     */
    public function test_family_data_table_renders(): void
    {
        Livewire::test(FamilyDataTable::class)
            ->assertStatus(200)
            ->assertSee('PostgreSQL')
            ->assertSee('Livewire Reactivity Active');
    }

    /**
     * Test filtering and sorting in Livewire DataTable.
     */
    public function test_livewire_datatable_search_and_filters(): void
    {
        // Insert a specific test family
        $familyUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(1) as uuid')[0]->uuid;
        $tmpAppUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(2) as uuid')[0]->uuid;

        DB::table('dbt_apy.families')->insert([
            'id' => $familyUuid,
            'tmp_application_id' => $tmpAppUuid,
            'lgd_district_code' => 315,
            'lgd_block_mc_code' => 250299,
            'lgd_gp_ward_code' => 2124,
            'area_type' => 'Urban',
            'address' => 'Test Unique Livewire Address 99999',
            'total_family_members' => 1,
            'is_agreed' => true,
            'application_status' => 'DRAFT',
            'create_mode' => 'o',
            'created_at' => now(),
            'updated_at' => now(),
            'kafka_updation_timestamp' => now(),
            'under_enquiry' => false,
            'transfer_out' => false,
        ]);

        $memberUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(3) as uuid')[0]->uuid;
        DB::table('dbt_apy.family_members')->insert([
            'id' => $memberUuid,
            'family_id' => $familyUuid,
            'lgd_district_code' => 315,
            'lgd_block_mc_code' => 250299,
            'lgd_gp_ward_code' => 2124,
            'is_hof' => true,
            'member_name' => 'Kalyani LivewireTestPerson',
            'gender' => 'Female',
            'date_of_birth' => '1995-01-01', // Age ~31 -> Annapurna true
            'relation_with_head_of_family' => 'Self',
            'aadhaar_no' => AadhaarService::generate(),
            'applying_for_annapurna_bhandar' => true,
            'has_health_insurance' => true,
            'pays_income_or_professional_tax' => false,
            'is_registered_gst' => false,
            'holds_constitutional_post' => false,
            'is_govt_pensioner' => false,
            'has_four_wheeler' => false,
            'is_child' => false,
            'application_status' => 'DRAFT',
            'create_mode' => 'o',
            'created_at' => now(),
            'updated_at' => now(),
            'kafka_updation_timestamp' => now(),
            'under_enquiry' => false,
            'july_payment' => false,
            'govt_self_decl' => false,
            'govt_fd' => false,
            'govt_wbhs' => false,
            'govt_asha' => false,
            'govt_icds' => false,
            'three_room_urb' => false,
            'land_mu_corp' => false,
            'oth_madrassah' => false,
            'not_in_asdd_fd' => true,
            'lb_ben_sir' => false,
            'govt_fd_not_permanent' => false,
            'govt_fd_sch' => false,
            'govt_fd_gpzp' => false,
            'is_excluded' => false,
            'aug_prep' => false,
            'priority_add' => false,
            'priority_del' => false,
            'transfer_out' => false,
            'is_oap' => false,
            'jm_status' => false,
            'sep_priority' => false,
        ]);

        Livewire::test(FamilyDataTable::class)
            ->set('search', 'Test Unique Livewire Address 99999')
            ->assertSee('Test Unique Livewire Address 99999')
            ->assertSee('1 Female (25-60)')
            // Test expanding the row to see member details
            ->call('toggleExpand', $familyUuid)
            ->assertSee('Kalyani LivewireTestPerson')
            ->assertSee('✨ true (Eligible: Female 25-60)')
            // Test Annapurna filter
            ->set('annapurnaFilter', 'eligible_only')
            ->assertSee('Test Unique Livewire Address 99999')
            ->set('annapurnaFilter', 'none')
            ->assertSee('No Family Records Found');

        // Clean up
        DB::table('dbt_apy.family_members')->where('family_id', $familyUuid)->delete();
        DB::table('dbt_apy.families')->where('id', $familyUuid)->delete();
    }

    /**
     * Test Quick Seed feature directly in Livewire component.
     */
    public function test_livewire_quick_seed(): void
    {
        $initialCount = Family::count();

        $component = Livewire::test(FamilyDataTable::class)
            ->call('quickSeedSample');

        $this->assertEquals($initialCount + 1, Family::count());

        // Find the newly created family
        $newFamily = Family::orderBy('created_at', 'desc')->first();
        $this->assertNotNull($newFamily);

        // Delete via Livewire component
        $component->call('deleteFamily', $newFamily->id, $newFamily->lgd_district_code);
        $this->assertEquals($initialCount, Family::count());
    }

    /**
     * Test Bulk Seed feature with 10 families directly in Livewire component.
     */
    public function test_livewire_bulk_seed(): void
    {
        $initialCount = Family::count();

        $component = Livewire::test(FamilyDataTable::class)
            ->set('bulkSeedCount', 10)
            ->call('bulkSeed');

        $this->assertEquals($initialCount + 10, Family::count());
        $component->assertSee('Bulk seeded 10 Indian families');
    }
}
