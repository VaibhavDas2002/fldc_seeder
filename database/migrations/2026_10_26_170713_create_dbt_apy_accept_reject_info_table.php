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
CREATE TABLE IF NOT EXISTS dbt_apy.accept_reject_info (
    id uuid DEFAULT dbt_apy.generate_zone_uuid(8) NOT NULL,
    tmp_application_id uuid,
    op_type character varying(50),
    user_id integer,
    new_data jsonb,
    old_data jsonb,
    remarks character varying(500),
    lgd_district_code integer DEFAULT 0 NOT NULL,
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
    f_member_id uuid,
    kafka_updation_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    app_serial integer,
    family_serial integer,
    family_member_id uuid,
    kafka_sl_no bigint DEFAULT nextval('dbt_apy.accept_reject_info_kafka_sl_no_seq'::regclass) NOT NULL,
    family_id character varying(150),
    member_id character varying(150),
    reject_revert_cause_id character varying(50),
    CONSTRAINT accept_reject_info_pkey PRIMARY KEY (id, lgd_district_code)
);
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS dbt_apy.accept_reject_info CASCADE');
    }
};
