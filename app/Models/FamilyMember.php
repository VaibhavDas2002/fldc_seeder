<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Family Member Model representing dbt_apy.family_members table.
 */
class FamilyMember extends Model
{
    use HasFactory;

    protected $table = 'dbt_apy.family_members';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_hof' => 'boolean',
        'applying_for_annapurna_bhandar' => 'boolean',
        'has_digital_ration_card' => 'boolean',
        'lifting_monthly_ration' => 'boolean',
        'has_health_insurance' => 'boolean',
        'health_insurance_sum_assured' => 'decimal:2',
        'health_insurance_annual_premium' => 'decimal:2',
        'pays_income_or_professional_tax' => 'boolean',
        'has_pan_card' => 'boolean',
        'is_registered_gst' => 'boolean',
        'holds_constitutional_post' => 'boolean',
        'is_govt_pensioner' => 'boolean',
        'has_three_pucca_rooms' => 'boolean',
        'owns_land' => 'boolean',
        'has_four_wheeler' => 'boolean',
        'is_child' => 'boolean',
        'is_disabled' => 'boolean',
        'not_in_asdd' => 'boolean',
        'under_enquiry' => 'boolean',
        'is_eligible' => 'boolean',
        'july_payment' => 'boolean',
        'govt_self_decl' => 'boolean',
        'govt_fd' => 'boolean',
        'govt_wbhs' => 'boolean',
        'govt_asha' => 'boolean',
        'govt_icds' => 'boolean',
        'three_room_urb' => 'boolean',
        'land_mu_corp' => 'boolean',
        'oth_madrassah' => 'boolean',
        'not_in_asdd_fd' => 'boolean',
        'lb_ben_sir' => 'boolean',
        'govt_fd_not_permanent' => 'boolean',
        'govt_fd_sch' => 'boolean',
        'govt_fd_gpzp' => 'boolean',
        'is_excluded' => 'boolean',
        'aug_prep' => 'boolean',
        'priority_add' => 'boolean',
        'priority_del' => 'boolean',
        'transfer_out' => 'boolean',
        'is_claim' => 'boolean',
        'is_grievance' => 'boolean',
        'is_oap' => 'boolean',
        'jm_status' => 'boolean',
        'is_not_sir_mark' => 'boolean',
        'is_asap' => 'boolean',
        'sep_priority' => 'boolean',
        'lgd_district_code' => 'integer',
        'lgd_block_mc_code' => 'integer',
        'lgd_gp_ward_code' => 'integer',
    ];

    /**
     * Relationship to family master.
     *
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id', 'id');
    }
}
