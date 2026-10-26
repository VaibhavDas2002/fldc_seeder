<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── Sequences ──────────────────────────────────────────────────────────
        DB::statement('CREATE SEQUENCE IF NOT EXISTS dbt_apy.family_members_family_serial_seq START WITH 300000000 INCREMENT BY 1 MINVALUE 300000000 NO MAXVALUE CACHE 1');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS dbt_apy.family_members_kafka_sl_no_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS dbt_apy.accept_reject_info_kafka_sl_no_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS dbt_apy.families_app_serial_seq START WITH 100000000 INCREMENT BY 1 MINVALUE 100000000 NO MAXVALUE CACHE 1');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS dbt_apy.families_kafka_sl_no_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1');

        // ── Function: generate_zone_uuid ───────────────────────────────────────
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION dbt_apy.generate_zone_uuid(id_type integer)
RETURNS uuid
LANGUAGE plpgsql
AS $$
DECLARE
    ts BIGINT;
    rnd BYTEA;
    hexstr TEXT;
    p_zone INTEGER;
BEGIN
    -- Read zone from DB configuration, fallback to 1 if not set
    BEGIN
        p_zone := current_setting('app.zone_id')::INTEGER;
    EXCEPTION WHEN OTHERS THEN
        p_zone := 1;
    END;

    -- Validate version (4 bits)
    IF id_type < 0 OR id_type > 15 THEN
        RAISE EXCEPTION 'Version must be between 0 and 15';
    END IF;

    -- Validate zone (8 bits)
    IF p_zone < 0 OR p_zone > 255 THEN
        RAISE EXCEPTION 'Zone ID must be between 0 and 255';
    END IF;

    -- Current timestamp in milliseconds
    ts := floor(extract(epoch FROM clock_timestamp()) * 1000);

    -- 9 bytes = 18 hex chars
    rnd := gen_random_bytes(9);

    /*
        Layout:
        12 hex chars : timestamp (48 bits)
         1 hex char  : version   (4 bits)
         2 hex chars : zone      (8 bits)
        17 hex chars : random    (68 bits)
        ----------------------------------
        32 hex chars : 128 bits
    */
    hexstr :=
          lpad(to_hex(ts::bigint), 12, '0')
       || to_hex(id_type)
       || lpad(to_hex(p_zone::integer), 2, '0')
       || substr(encode(rnd, 'hex'), 1, 17);

    RETURN (
          substr(hexstr, 1, 8)  || '-'
       || substr(hexstr, 9, 4)  || '-'
       || substr(hexstr,13, 4)  || '-'
       || substr(hexstr,17, 4)  || '-'
       || substr(hexstr,21,12)
    )::uuid;
END;
$$;
SQL
        );

        // ── Function: sync_dup_chk_family_members ──────────────────────────────
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION dbt_apy.sync_dup_chk_family_members()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        IF NEW.application_status = 'SUBMITTED' AND NOT NEW.is_deleted THEN
            INSERT INTO dup_chk.family_members (
                id, member_name, aadhaar_no, mobile_no, bank_account_no, epic_no, digital_ration_card_no
            ) VALUES (
                NEW.id, NEW.member_name, NEW.aadhaar_no, NEW.mobile_no, NEW.bank_account_no, NEW.epic_no, NEW.digital_ration_card_no
            ) ON CONFLICT (id) DO NOTHING;
        END IF;
    ELSIF TG_OP = 'UPDATE' THEN
        IF OLD.aadhaar_no <> NEW.aadhaar_no AND OLD.next_level_role_id > 0 THEN
            UPDATE dup_chk.family_members SET aadhaar_no = NEW.aadhaar_no WHERE id = NEW.id;
        ELSIF NEW.next_level_role_id < 0 OR NEW.is_deleted = TRUE THEN
            DELETE FROM dup_chk.family_members WHERE id = NEW.id;
        END IF;
    END IF;
    RETURN NEW;
END;
$$;
SQL
        );

        // ── Function: sync_family_members_to_consolidation ───────────────────────
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION dbt_apy.sync_family_members_to_consolidation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO consolidation.family_members (
            id, is_hof, member_name, date_of_birth, gender, relation_with_head_of_family, aadhaar_no, mobile_no,
            applying_for_annapurna_bhandar, bank_name, bank_account_no, ifsc_code, epic_no, assembly_constituency_no,
            part_no, social_category, has_digital_ration_card, digital_ration_card_no, digital_ration_card_type,
            lifting_monthly_ration, has_health_insurance, health_insurance_type, health_insurance_sum_assured,
            health_insurance_annual_premium, pays_income_or_professional_tax, has_pan_card, pan_name, pan_no,
            professional_tax_profession, is_registered_gst, gstin, holds_constitutional_post, constitutional_post_member_no,
            is_govt_pensioner, govt_pensioner_member_no, has_three_pucca_rooms, landholding_size_decimals, owns_land,
            has_four_wheeler, vehicle_registration_no, vehicle_model, vehicle_count, caa_application_status,
            caa_application_no, caa_certificate_no, sir2026tribunal_status, sir2026case_details, is_child, school_grade,
            school_name, school_type, school_type_other, vaccination_card_id, vaccination_status,
            vaccination_skip_reason_or_date, literacy_status, highest_educational_qualifications, lgd_district_code,
            lgd_block_mc_code, lgd_gp_ward_code, head_of_family_id, family_id, verification_datetime, verification_remarks,
            verification_doc_path, approval_datetime, approval_remarks, approval_doc_path, next_level_role_id, is_reverted,
            application_status, created_at, created_by, create_mode, created_by_dist_code, created_by_local_body_code,
            updated_at, updated_by, is_deleted, deleted_by, deleted_at, ip_address, insurance_no, kafka_updation_timestamp,
            family_serial, kafka_sl_no, is_disabled, disability_cert_no, disability_percentage, religion, tokenised_aadhaar,
            name_as_per_aadhaar, gender_as_per_aadhaar, date_of_birth_as_per_aadhaar, ration_card_member_id, not_in_asdd,
            appeal_tribunal, verified_by, approved_by, under_enquiry, is_eligible, aadhaar_seeding_status, ekyc_issue,
            detail_status, lb_data_paid_apy, july_payment, paid_aadhaar_no, jks_id, jks_date, govt_self_decl, govt_fd,
            govt_wbhs, govt_asha, govt_icds, three_room_urb, land_mu_corp, oth_madrassah, not_in_asdd_fd, lb_ben_sir,
            govt_fd_not_permanent, ekyc_status, match_type, govt_fd_sch, govt_fd_gpzp, is_excluded, aug_prep, priority_add,
            priority_del, transfer_in, transfer_out, is_claim, is_grievance, is_oap, jm_status, is_not_sir_mark, is_asap,
            sep_priority, jks_phase
        ) VALUES (
            NEW.id, NEW.is_hof, NEW.member_name, NEW.date_of_birth, NEW.gender, NEW.relation_with_head_of_family,
            NEW.aadhaar_no, NEW.mobile_no, NEW.applying_for_annapurna_bhandar, NEW.bank_name, NEW.bank_account_no,
            NEW.ifsc_code, NEW.epic_no, NEW.assembly_constituency_no, NEW.part_no, NEW.social_category,
            NEW.has_digital_ration_card, NEW.digital_ration_card_no, NEW.digital_ration_card_type,
            NEW.lifting_monthly_ration, NEW.has_health_insurance, NEW.health_insurance_type,
            NEW.health_insurance_sum_assured, NEW.health_insurance_annual_premium, NEW.pays_income_or_professional_tax,
            NEW.has_pan_card, NEW.pan_name, NEW.pan_no, NEW.professional_tax_profession, NEW.is_registered_gst, NEW.gstin,
            NEW.holds_constitutional_post, NEW.constitutional_post_member_no, NEW.is_govt_pensioner,
            NEW.govt_pensioner_member_no, NEW.has_three_pucca_rooms, NEW.landholding_size_decimals, NEW.owns_land,
            NEW.has_four_wheeler, NEW.vehicle_registration_no, NEW.vehicle_model, NEW.vehicle_count,
            NEW.caa_application_status, NEW.caa_application_no, NEW.caa_certificate_no, NEW.sir2026tribunal_status,
            NEW.sir2026case_details, NEW.is_child, NEW.school_grade, NEW.school_name, NEW.school_type, NEW.school_type_other,
            NEW.vaccination_card_id, NEW.vaccination_status, NEW.vaccination_skip_reason_or_date, NEW.literacy_status,
            NEW.highest_educational_qualifications, NEW.lgd_district_code, NEW.lgd_block_mc_code, NEW.lgd_gp_ward_code,
            NEW.head_of_family_id, NEW.family_id, NEW.verification_datetime, NEW.verification_remarks,
            NEW.verification_doc_path, NEW.approval_datetime, NEW.approval_remarks, NEW.approval_doc_path,
            NEW.next_level_role_id, NEW.is_reverted, NEW.application_status, NEW.created_at, NEW.created_by, NEW.create_mode,
            NEW.created_by_dist_code, NEW.created_by_local_body_code, NEW.updated_at, NEW.updated_by, NEW.is_deleted,
            NEW.deleted_by, NEW.deleted_at, NEW.ip_address, NEW.insurance_no, NEW.kafka_updation_timestamp,
            NEW.family_serial, NEW.kafka_sl_no, NEW.is_disabled, NEW.disability_cert_no, NEW.disability_percentage,
            NEW.religion, NEW.tokenised_aadhaar, NEW.name_as_per_aadhaar, NEW.gender_as_per_aadhaar,
            NEW.date_of_birth_as_per_aadhaar, NEW.ration_card_member_id, NEW.not_in_asdd, NEW.appeal_tribunal,
            NEW.verified_by, NEW.approved_by, NEW.under_enquiry, NEW.is_eligible, NEW.aadhaar_seeding_status,
            NEW.ekyc_issue, NEW.detail_status, NEW.lb_data_paid_apy, NEW.july_payment, NEW.paid_aadhaar_no, NEW.jks_id,
            NEW.jks_date, NEW.govt_self_decl, NEW.govt_fd, NEW.govt_wbhs, NEW.govt_asha, NEW.govt_icds, NEW.three_room_urb,
            NEW.land_mu_corp, NEW.oth_madrassah, NEW.not_in_asdd_fd, NEW.lb_ben_sir, NEW.govt_fd_not_permanent,
            NEW.ekyc_status, NEW.match_type, NEW.govt_fd_sch, NEW.govt_fd_gpzp, NEW.is_excluded, NEW.aug_prep,
            NEW.priority_add, NEW.priority_del, NEW.transfer_in, NEW.transfer_out, NEW.is_claim, NEW.is_grievance,
            NEW.is_oap, NEW.jm_status, NEW.is_not_sir_mark, NEW.is_asap, NEW.sep_priority, NEW.jks_phase
        ) ON CONFLICT (id, lgd_district_code) DO UPDATE SET
            is_hof = EXCLUDED.is_hof, member_name = EXCLUDED.member_name, date_of_birth = EXCLUDED.date_of_birth,
            gender = EXCLUDED.gender, relation_with_head_of_family = EXCLUDED.relation_with_head_of_family,
            aadhaar_no = EXCLUDED.aadhaar_no, mobile_no = EXCLUDED.mobile_no,
            applying_for_annapurna_bhandar = EXCLUDED.applying_for_annapurna_bhandar, bank_name = EXCLUDED.bank_name,
            bank_account_no = EXCLUDED.bank_account_no, ifsc_code = EXCLUDED.ifsc_code, epic_no = EXCLUDED.epic_no,
            assembly_constituency_no = EXCLUDED.assembly_constituency_no, part_no = EXCLUDED.part_no,
            social_category = EXCLUDED.social_category, has_digital_ration_card = EXCLUDED.has_digital_ration_card,
            digital_ration_card_no = EXCLUDED.digital_ration_card_no,
            digital_ration_card_type = EXCLUDED.digital_ration_card_type,
            lifting_monthly_ration = EXCLUDED.lifting_monthly_ration,
            has_health_insurance = EXCLUDED.has_health_insurance,
            health_insurance_type = EXCLUDED.health_insurance_type,
            health_insurance_sum_assured = EXCLUDED.health_insurance_sum_assured,
            health_insurance_annual_premium = EXCLUDED.health_insurance_annual_premium,
            pays_income_or_professional_tax = EXCLUDED.pays_income_or_professional_tax,
            has_pan_card = EXCLUDED.has_pan_card, pan_name = EXCLUDED.pan_name, pan_no = EXCLUDED.pan_no,
            professional_tax_profession = EXCLUDED.professional_tax_profession,
            is_registered_gst = EXCLUDED.is_registered_gst, gstin = EXCLUDED.gstin,
            holds_constitutional_post = EXCLUDED.holds_constitutional_post,
            constitutional_post_member_no = EXCLUDED.constitutional_post_member_no,
            is_govt_pensioner = EXCLUDED.is_govt_pensioner,
            govt_pensioner_member_no = EXCLUDED.govt_pensioner_member_no,
            has_three_pucca_rooms = EXCLUDED.has_three_pucca_rooms,
            landholding_size_decimals = EXCLUDED.landholding_size_decimals, owns_land = EXCLUDED.owns_land,
            has_four_wheeler = EXCLUDED.has_four_wheeler, vehicle_registration_no = EXCLUDED.vehicle_registration_no,
            vehicle_model = EXCLUDED.vehicle_model, vehicle_count = EXCLUDED.vehicle_count,
            caa_application_status = EXCLUDED.caa_application_status, caa_application_no = EXCLUDED.caa_application_no,
            caa_certificate_no = EXCLUDED.caa_certificate_no, sir2026tribunal_status = EXCLUDED.sir2026tribunal_status,
            sir2026case_details = EXCLUDED.sir2026case_details, is_child = EXCLUDED.is_child,
            school_grade = EXCLUDED.school_grade, school_name = EXCLUDED.school_name, school_type = EXCLUDED.school_type,
            school_type_other = EXCLUDED.school_type_other, vaccination_card_id = EXCLUDED.vaccination_card_id,
            vaccination_status = EXCLUDED.vaccination_status,
            vaccination_skip_reason_or_date = EXCLUDED.vaccination_skip_reason_or_date,
            literacy_status = EXCLUDED.literacy_status,
            highest_educational_qualifications = EXCLUDED.highest_educational_qualifications,
            lgd_block_mc_code = EXCLUDED.lgd_block_mc_code, lgd_gp_ward_code = EXCLUDED.lgd_gp_ward_code,
            head_of_family_id = EXCLUDED.head_of_family_id, family_id = EXCLUDED.family_id,
            verification_datetime = EXCLUDED.verification_datetime, verification_remarks = EXCLUDED.verification_remarks,
            verification_doc_path = EXCLUDED.verification_doc_path, approval_datetime = EXCLUDED.approval_datetime,
            approval_remarks = EXCLUDED.approval_remarks, approval_doc_path = EXCLUDED.approval_doc_path,
            next_level_role_id = EXCLUDED.next_level_role_id, is_reverted = EXCLUDED.is_reverted,
            application_status = EXCLUDED.application_status, created_at = EXCLUDED.created_at,
            created_by = EXCLUDED.created_by, create_mode = EXCLUDED.create_mode,
            created_by_dist_code = EXCLUDED.created_by_dist_code,
            created_by_local_body_code = EXCLUDED.created_by_local_body_code, updated_at = EXCLUDED.updated_at,
            updated_by = EXCLUDED.updated_by, is_deleted = EXCLUDED.is_deleted, deleted_by = EXCLUDED.deleted_by,
            deleted_at = EXCLUDED.deleted_at, ip_address = EXCLUDED.ip_address, insurance_no = EXCLUDED.insurance_no,
            kafka_updation_timestamp = EXCLUDED.kafka_updation_timestamp, family_serial = EXCLUDED.family_serial,
            kafka_sl_no = EXCLUDED.kafka_sl_no, is_disabled = EXCLUDED.is_disabled,
            disability_cert_no = EXCLUDED.disability_cert_no,
            disability_percentage = EXCLUDED.disability_percentage, religion = EXCLUDED.religion,
            tokenised_aadhaar = EXCLUDED.tokenised_aadhaar, name_as_per_aadhaar = EXCLUDED.name_as_per_aadhaar,
            gender_as_per_aadhaar = EXCLUDED.gender_as_per_aadhaar,
            date_of_birth_as_per_aadhaar = EXCLUDED.date_of_birth_as_per_aadhaar,
            ration_card_member_id = EXCLUDED.ration_card_member_id, not_in_asdd = EXCLUDED.not_in_asdd,
            appeal_tribunal = EXCLUDED.appeal_tribunal, verified_by = EXCLUDED.verified_by,
            approved_by = EXCLUDED.approved_by, under_enquiry = EXCLUDED.under_enquiry,
            is_eligible = EXCLUDED.is_eligible, aadhaar_seeding_status = EXCLUDED.aadhaar_seeding_status,
            ekyc_issue = EXCLUDED.ekyc_issue, detail_status = EXCLUDED.detail_status,
            lb_data_paid_apy = EXCLUDED.lb_data_paid_apy, july_payment = EXCLUDED.july_payment,
            paid_aadhaar_no = EXCLUDED.paid_aadhaar_no, jks_id = EXCLUDED.jks_id, jks_date = EXCLUDED.jks_date,
            govt_self_decl = EXCLUDED.govt_self_decl, govt_fd = EXCLUDED.govt_fd, govt_wbhs = EXCLUDED.govt_wbhs,
            govt_asha = EXCLUDED.govt_asha, govt_icds = EXCLUDED.govt_icds, three_room_urb = EXCLUDED.three_room_urb,
            land_mu_corp = EXCLUDED.land_mu_corp, oth_madrassah = EXCLUDED.oth_madrassah,
            not_in_asdd_fd = EXCLUDED.not_in_asdd_fd, lb_ben_sir = EXCLUDED.lb_ben_sir,
            govt_fd_not_permanent = EXCLUDED.govt_fd_not_permanent, ekyc_status = EXCLUDED.ekyc_status,
            match_type = EXCLUDED.match_type, govt_fd_sch = EXCLUDED.govt_fd_sch,
            govt_fd_gpzp = EXCLUDED.govt_fd_gpzp, is_excluded = EXCLUDED.is_excluded, aug_prep = EXCLUDED.aug_prep,
            priority_add = EXCLUDED.priority_add, priority_del = EXCLUDED.priority_del,
            transfer_in = EXCLUDED.transfer_in, transfer_out = EXCLUDED.transfer_out, is_claim = EXCLUDED.is_claim,
            is_grievance = EXCLUDED.is_grievance, is_oap = EXCLUDED.is_oap, jm_status = EXCLUDED.jm_status,
            is_not_sir_mark = EXCLUDED.is_not_sir_mark, is_asap = EXCLUDED.is_asap,
            sep_priority = EXCLUDED.sep_priority, jks_phase = EXCLUDED.jks_phase;
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO consolidation.family_members (
            id, is_hof, member_name, date_of_birth, gender, relation_with_head_of_family, aadhaar_no, mobile_no,
            applying_for_annapurna_bhandar, bank_name, bank_account_no, ifsc_code, epic_no, assembly_constituency_no,
            part_no, social_category, has_digital_ration_card, digital_ration_card_no, digital_ration_card_type,
            lifting_monthly_ration, has_health_insurance, health_insurance_type, health_insurance_sum_assured,
            health_insurance_annual_premium, pays_income_or_professional_tax, has_pan_card, pan_name, pan_no,
            professional_tax_profession, is_registered_gst, gstin, holds_constitutional_post, constitutional_post_member_no,
            is_govt_pensioner, govt_pensioner_member_no, has_three_pucca_rooms, landholding_size_decimals, owns_land,
            has_four_wheeler, vehicle_registration_no, vehicle_model, vehicle_count, caa_application_status,
            caa_application_no, caa_certificate_no, sir2026tribunal_status, sir2026case_details, is_child, school_grade,
            school_name, school_type, school_type_other, vaccination_card_id, vaccination_status,
            vaccination_skip_reason_or_date, literacy_status, highest_educational_qualifications, lgd_district_code,
            lgd_block_mc_code, lgd_gp_ward_code, head_of_family_id, family_id, verification_datetime, verification_remarks,
            verification_doc_path, approval_datetime, approval_remarks, approval_doc_path, next_level_role_id, is_reverted,
            application_status, created_at, created_by, create_mode, created_by_dist_code, created_by_local_body_code,
            updated_at, updated_by, is_deleted, deleted_by, deleted_at, ip_address, insurance_no, kafka_updation_timestamp,
            family_serial, kafka_sl_no, is_disabled, disability_cert_no, disability_percentage, religion, tokenised_aadhaar,
            name_as_per_aadhaar, gender_as_per_aadhaar, date_of_birth_as_per_aadhaar, ration_card_member_id, not_in_asdd,
            appeal_tribunal, verified_by, approved_by, under_enquiry, is_eligible, aadhaar_seeding_status, ekyc_issue,
            detail_status, lb_data_paid_apy, july_payment, paid_aadhaar_no, jks_id, jks_date, govt_self_decl, govt_fd,
            govt_wbhs, govt_asha, govt_icds, three_room_urb, land_mu_corp, oth_madrassah, not_in_asdd_fd, lb_ben_sir,
            govt_fd_not_permanent, ekyc_status, match_type, govt_fd_sch, govt_fd_gpzp, is_excluded, aug_prep, priority_add,
            priority_del, transfer_in, transfer_out, is_claim, is_grievance, is_oap, jm_status, is_not_sir_mark, is_asap,
            sep_priority, jks_phase
        ) VALUES (
            NEW.id, NEW.is_hof, NEW.member_name, NEW.date_of_birth, NEW.gender, NEW.relation_with_head_of_family,
            NEW.aadhaar_no, NEW.mobile_no, NEW.applying_for_annapurna_bhandar, NEW.bank_name, NEW.bank_account_no,
            NEW.ifsc_code, NEW.epic_no, NEW.assembly_constituency_no, NEW.part_no, NEW.social_category,
            NEW.has_digital_ration_card, NEW.digital_ration_card_no, NEW.digital_ration_card_type,
            NEW.lifting_monthly_ration, NEW.has_health_insurance, NEW.health_insurance_type,
            NEW.health_insurance_sum_assured, NEW.health_insurance_annual_premium, NEW.pays_income_or_professional_tax,
            NEW.has_pan_card, NEW.pan_name, NEW.pan_no, NEW.professional_tax_profession, NEW.is_registered_gst, NEW.gstin,
            NEW.holds_constitutional_post, NEW.constitutional_post_member_no, NEW.is_govt_pensioner,
            NEW.govt_pensioner_member_no, NEW.has_three_pucca_rooms, NEW.landholding_size_decimals, NEW.owns_land,
            NEW.has_four_wheeler, NEW.vehicle_registration_no, NEW.vehicle_model, NEW.vehicle_count,
            NEW.caa_application_status, NEW.caa_application_no, NEW.caa_certificate_no, NEW.sir2026tribunal_status,
            NEW.sir2026case_details, NEW.is_child, NEW.school_grade, NEW.school_name, NEW.school_type, NEW.school_type_other,
            NEW.vaccination_card_id, NEW.vaccination_status, NEW.vaccination_skip_reason_or_date, NEW.literacy_status,
            NEW.highest_educational_qualifications, NEW.lgd_district_code, NEW.lgd_block_mc_code, NEW.lgd_gp_ward_code,
            NEW.head_of_family_id, NEW.family_id, NEW.verification_datetime, NEW.verification_remarks,
            NEW.verification_doc_path, NEW.approval_datetime, NEW.approval_remarks, NEW.approval_doc_path,
            NEW.next_level_role_id, NEW.is_reverted, NEW.application_status, NEW.created_at, NEW.created_by, NEW.create_mode,
            NEW.created_by_dist_code, NEW.created_by_local_body_code, NEW.updated_at, NEW.updated_by, NEW.is_deleted,
            NEW.deleted_by, NEW.deleted_at, NEW.ip_address, NEW.insurance_no, NEW.kafka_updation_timestamp,
            NEW.family_serial, NEW.kafka_sl_no, NEW.is_disabled, NEW.disability_cert_no, NEW.disability_percentage,
            NEW.religion, NEW.tokenised_aadhaar, NEW.name_as_per_aadhaar, NEW.gender_as_per_aadhaar,
            NEW.date_of_birth_as_per_aadhaar, NEW.ration_card_member_id, NEW.not_in_asdd, NEW.appeal_tribunal,
            NEW.verified_by, NEW.approved_by, NEW.under_enquiry, NEW.is_eligible, NEW.aadhaar_seeding_status,
            NEW.ekyc_issue, NEW.detail_status, NEW.lb_data_paid_apy, NEW.july_payment, NEW.paid_aadhaar_no, NEW.jks_id,
            NEW.jks_date, NEW.govt_self_decl, NEW.govt_fd, NEW.govt_wbhs, NEW.govt_asha, NEW.govt_icds, NEW.three_room_urb,
            NEW.land_mu_corp, NEW.oth_madrassah, NEW.not_in_asdd_fd, NEW.lb_ben_sir, NEW.govt_fd_not_permanent,
            NEW.ekyc_status, NEW.match_type, NEW.govt_fd_sch, NEW.govt_fd_gpzp, NEW.is_excluded, NEW.aug_prep,
            NEW.priority_add, NEW.priority_del, NEW.transfer_in, NEW.transfer_out, NEW.is_claim, NEW.is_grievance,
            NEW.is_oap, NEW.jm_status, NEW.is_not_sir_mark, NEW.is_asap, NEW.sep_priority, NEW.jks_phase
        ) ON CONFLICT (id, lgd_district_code) DO UPDATE SET
            is_hof = EXCLUDED.is_hof, member_name = EXCLUDED.member_name, date_of_birth = EXCLUDED.date_of_birth,
            gender = EXCLUDED.gender, relation_with_head_of_family = EXCLUDED.relation_with_head_of_family,
            aadhaar_no = EXCLUDED.aadhaar_no, mobile_no = EXCLUDED.mobile_no,
            applying_for_annapurna_bhandar = EXCLUDED.applying_for_annapurna_bhandar, bank_name = EXCLUDED.bank_name,
            bank_account_no = EXCLUDED.bank_account_no, ifsc_code = EXCLUDED.ifsc_code, epic_no = EXCLUDED.epic_no,
            assembly_constituency_no = EXCLUDED.assembly_constituency_no, part_no = EXCLUDED.part_no,
            social_category = EXCLUDED.social_category, has_digital_ration_card = EXCLUDED.has_digital_ration_card,
            digital_ration_card_no = EXCLUDED.digital_ration_card_no,
            digital_ration_card_type = EXCLUDED.digital_ration_card_type,
            lifting_monthly_ration = EXCLUDED.lifting_monthly_ration,
            has_health_insurance = EXCLUDED.has_health_insurance,
            health_insurance_type = EXCLUDED.health_insurance_type,
            health_insurance_sum_assured = EXCLUDED.health_insurance_sum_assured,
            health_insurance_annual_premium = EXCLUDED.health_insurance_annual_premium,
            pays_income_or_professional_tax = EXCLUDED.pays_income_or_professional_tax,
            has_pan_card = EXCLUDED.has_pan_card, pan_name = EXCLUDED.pan_name, pan_no = EXCLUDED.pan_no,
            professional_tax_profession = EXCLUDED.professional_tax_profession,
            is_registered_gst = EXCLUDED.is_registered_gst, gstin = EXCLUDED.gstin,
            holds_constitutional_post = EXCLUDED.holds_constitutional_post,
            constitutional_post_member_no = EXCLUDED.constitutional_post_member_no,
            is_govt_pensioner = EXCLUDED.is_govt_pensioner,
            govt_pensioner_member_no = EXCLUDED.govt_pensioner_member_no,
            has_three_pucca_rooms = EXCLUDED.has_three_pucca_rooms,
            landholding_size_decimals = EXCLUDED.landholding_size_decimals, owns_land = EXCLUDED.owns_land,
            has_four_wheeler = EXCLUDED.has_four_wheeler, vehicle_registration_no = EXCLUDED.vehicle_registration_no,
            vehicle_model = EXCLUDED.vehicle_model, vehicle_count = EXCLUDED.vehicle_count,
            caa_application_status = EXCLUDED.caa_application_status, caa_application_no = EXCLUDED.caa_application_no,
            caa_certificate_no = EXCLUDED.caa_certificate_no, sir2026tribunal_status = EXCLUDED.sir2026tribunal_status,
            sir2026case_details = EXCLUDED.sir2026case_details, is_child = EXCLUDED.is_child,
            school_grade = EXCLUDED.school_grade, school_name = EXCLUDED.school_name, school_type = EXCLUDED.school_type,
            school_type_other = EXCLUDED.school_type_other, vaccination_card_id = EXCLUDED.vaccination_card_id,
            vaccination_status = EXCLUDED.vaccination_status,
            vaccination_skip_reason_or_date = EXCLUDED.vaccination_skip_reason_or_date,
            literacy_status = EXCLUDED.literacy_status,
            highest_educational_qualifications = EXCLUDED.highest_educational_qualifications,
            lgd_block_mc_code = EXCLUDED.lgd_block_mc_code, lgd_gp_ward_code = EXCLUDED.lgd_gp_ward_code,
            head_of_family_id = EXCLUDED.head_of_family_id, family_id = EXCLUDED.family_id,
            verification_datetime = EXCLUDED.verification_datetime, verification_remarks = EXCLUDED.verification_remarks,
            verification_doc_path = EXCLUDED.verification_doc_path, approval_datetime = EXCLUDED.approval_datetime,
            approval_remarks = EXCLUDED.approval_remarks, approval_doc_path = EXCLUDED.approval_doc_path,
            next_level_role_id = EXCLUDED.next_level_role_id, is_reverted = EXCLUDED.is_reverted,
            application_status = EXCLUDED.application_status, created_at = EXCLUDED.created_at,
            created_by = EXCLUDED.created_by, create_mode = EXCLUDED.create_mode,
            created_by_dist_code = EXCLUDED.created_by_dist_code,
            created_by_local_body_code = EXCLUDED.created_by_local_body_code, updated_at = EXCLUDED.updated_at,
            updated_by = EXCLUDED.updated_by, is_deleted = EXCLUDED.is_deleted, deleted_by = EXCLUDED.deleted_by,
            deleted_at = EXCLUDED.deleted_at, ip_address = EXCLUDED.ip_address, insurance_no = EXCLUDED.insurance_no,
            kafka_updation_timestamp = EXCLUDED.kafka_updation_timestamp, family_serial = EXCLUDED.family_serial,
            kafka_sl_no = EXCLUDED.kafka_sl_no, is_disabled = EXCLUDED.is_disabled,
            disability_cert_no = EXCLUDED.disability_cert_no,
            disability_percentage = EXCLUDED.disability_percentage, religion = EXCLUDED.religion,
            tokenised_aadhaar = EXCLUDED.tokenised_aadhaar, name_as_per_aadhaar = EXCLUDED.name_as_per_aadhaar,
            gender_as_per_aadhaar = EXCLUDED.gender_as_per_aadhaar,
            date_of_birth_as_per_aadhaar = EXCLUDED.date_of_birth_as_per_aadhaar,
            ration_card_member_id = EXCLUDED.ration_card_member_id, not_in_asdd = EXCLUDED.not_in_asdd,
            appeal_tribunal = EXCLUDED.appeal_tribunal, verified_by = EXCLUDED.verified_by,
            approved_by = EXCLUDED.approved_by, under_enquiry = EXCLUDED.under_enquiry,
            is_eligible = EXCLUDED.is_eligible, aadhaar_seeding_status = EXCLUDED.aadhaar_seeding_status,
            ekyc_issue = EXCLUDED.ekyc_issue, detail_status = EXCLUDED.detail_status,
            lb_data_paid_apy = EXCLUDED.lb_data_paid_apy, july_payment = EXCLUDED.july_payment,
            paid_aadhaar_no = EXCLUDED.paid_aadhaar_no, jks_id = EXCLUDED.jks_id, jks_date = EXCLUDED.jks_date,
            govt_self_decl = EXCLUDED.govt_self_decl, govt_fd = EXCLUDED.govt_fd, govt_wbhs = EXCLUDED.govt_wbhs,
            govt_asha = EXCLUDED.govt_asha, govt_icds = EXCLUDED.govt_icds, three_room_urb = EXCLUDED.three_room_urb,
            land_mu_corp = EXCLUDED.land_mu_corp, oth_madrassah = EXCLUDED.oth_madrassah,
            not_in_asdd_fd = EXCLUDED.not_in_asdd_fd, lb_ben_sir = EXCLUDED.lb_ben_sir,
            govt_fd_not_permanent = EXCLUDED.govt_fd_not_permanent, ekyc_status = EXCLUDED.ekyc_status,
            match_type = EXCLUDED.match_type, govt_fd_sch = EXCLUDED.govt_fd_sch,
            govt_fd_gpzp = EXCLUDED.govt_fd_gpzp, is_excluded = EXCLUDED.is_excluded, aug_prep = EXCLUDED.aug_prep,
            priority_add = EXCLUDED.priority_add, priority_del = EXCLUDED.priority_del,
            transfer_in = EXCLUDED.transfer_in, transfer_out = EXCLUDED.transfer_out, is_claim = EXCLUDED.is_claim,
            is_grievance = EXCLUDED.is_grievance, is_oap = EXCLUDED.is_oap, jm_status = EXCLUDED.jm_status,
            is_not_sir_mark = EXCLUDED.is_not_sir_mark, is_asap = EXCLUDED.is_asap,
            sep_priority = EXCLUDED.sep_priority, jks_phase = EXCLUDED.jks_phase;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        DELETE FROM consolidation.family_members WHERE id = OLD.id AND lgd_district_code = OLD.lgd_district_code;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS dbt_apy.sync_family_members_to_consolidation() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS dbt_apy.sync_dup_chk_family_members() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS dbt_apy.generate_zone_uuid(integer) CASCADE');

        DB::statement('DROP SEQUENCE IF EXISTS dbt_apy.families_kafka_sl_no_seq CASCADE');
        DB::statement('DROP SEQUENCE IF EXISTS dbt_apy.families_app_serial_seq CASCADE');
        DB::statement('DROP SEQUENCE IF EXISTS dbt_apy.accept_reject_info_kafka_sl_no_seq CASCADE');
        DB::statement('DROP SEQUENCE IF EXISTS dbt_apy.family_members_kafka_sl_no_seq CASCADE');
        DB::statement('DROP SEQUENCE IF EXISTS dbt_apy.family_members_family_serial_seq CASCADE');
    }
};
