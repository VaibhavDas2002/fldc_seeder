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
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS dbt_apy.families (
    id uuid DEFAULT dbt_apy.generate_zone_uuid(1) NOT NULL,
    tmp_application_id uuid DEFAULT dbt_apy.generate_zone_uuid(2) NOT NULL,
    total_family_members integer DEFAULT 0 NOT NULL,
    lifting_monthly_ration boolean,
    has_digital_ration_card boolean DEFAULT false NOT NULL,
    ration_card_household_id text,
    no_of_illiterate_adults integer,
    no_of_literate_adults integer,
    total_annual_family_income integer,
    address text,
    lgd_district_code integer NOT NULL,
    lgd_block_mc_code integer NOT NULL,
    lgd_gp_ward_code integer NOT NULL,
    area_type text,
    ulb integer,
    is_agreed boolean NOT NULL,
    verification_datetime timestamp without time zone,
    verification_remarks text,
    verification_doc_path text,
    approval_datetime timestamp without time zone,
    approval_remarks text,
    approval_doc_path text,
    next_level_role_id smallint DEFAULT 0,
    is_reverted smallint DEFAULT 0,
    application_status text DEFAULT 'DRAFT'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    created_by character varying(50),
    create_mode character(1) DEFAULT 'o'::bpchar NOT NULL,
    created_by_dist_code smallint,
    created_by_local_body_code integer,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_by character varying(50),
    is_deleted boolean DEFAULT false,
    deleted_by character varying(50),
    deleted_at timestamp without time zone,
    ip_address text,
    kafka_updation_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    app_serial integer DEFAULT nextval('dbt_apy.families_app_serial_seq'::regclass),
    kafka_sl_no bigint DEFAULT nextval('dbt_apy.families_kafka_sl_no_seq'::regclass) NOT NULL,
    enquiry_remarks text,
    enquiry_datetime timestamp without time zone,
    enquiry_conducted_by integer,
    under_enquiry boolean DEFAULT false NOT NULL,
    is_eligible boolean,
    aadhaar_seeding_status character varying(4),
    ekyc_issue character varying(50),
    detail_status smallint,
    jks_id character varying(30),
    jks_date date,
    transfer_in integer,
    transfer_out boolean DEFAULT false NOT NULL,
    is_claim boolean DEFAULT false,
    is_grievance boolean DEFAULT false,
    jks_phase smallint,
    CONSTRAINT families_pkey PRIMARY KEY (id, lgd_district_code),
    CONSTRAINT uq_application_identity UNIQUE (tmp_application_id, lgd_district_code)
);
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS dbt_apy.families CASCADE');
    }
};
