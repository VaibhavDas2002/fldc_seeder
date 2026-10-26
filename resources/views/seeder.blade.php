<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Annapurna Consolidated · DBT APY Data Seeder & Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/seeder.css') }}">
    <!-- Tailwind CSS v4 Standalone Engine -->
    <script src="{{ asset('js/tailwindcss-v4.js') }}"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-saffron-500: #ff9933;
            --color-saffron-600: #e07b12;
            --color-india-green: #138808;
            --color-india-navy: #000080;
        }
    </style>
    @livewireStyles
</head>
<body>
    <!-- Top Global App Bar -->
    <header class="app-header bg-slate-900/90 border-b border-slate-800 backdrop-blur-md sticky top-0 z-40">
        <div class="header-container flex items-center justify-between max-w-7xl mx-auto px-4 py-3">
            <div class="brand-section flex items-center gap-3">
                <div class="brand-icon w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-slate-950 shadow-md shadow-amber-500/20">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-title flex items-center gap-2">
                        <span class="text-base font-bold text-slate-100 font-heading">Annapurna Consolidated</span>
                        <span class="badge badge-accent inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">DBT APY</span>
                    </div>
                    <p class="brand-subtitle text-xs text-slate-400">Direct Benefit Transfer · Family & Member Master Registration Portal</p>
                </div>
            </div>

            <div class="header-meta flex items-center gap-3">
                <div class="db-status-pill inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700">
                    <span class="pulse-dot w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="db-name">PostgreSQL · <strong class="text-slate-100 font-mono">annapurna_consolidated</strong></span>
                    <span class="schema-tag text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded">dbt_apy</span>
                </div>
                <div class="auth-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-300 border border-emerald-500/30">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>No Auth Required · Direct Access</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-wrapper max-w-7xl mx-auto px-4 py-6">
        <!-- Live Metrics Ribbon -->
        <section class="stats-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center gap-4 shadow-lg hover:border-slate-700 transition-all">
                <div class="stat-icon icon-family w-12 h-12 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center border border-sky-500/20">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="stat-value text-2xl font-extrabold text-slate-100 font-mono tracking-tight" id="stat-total-families">{{ number_format($stats['total_families']) }}</div>
                    <div class="stat-label text-xs text-slate-400 mt-0.5">Total Families (dbt_apy.families)</div>
                </div>
            </div>

            <div class="stat-card bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center gap-4 shadow-lg hover:border-slate-700 transition-all">
                <div class="stat-icon icon-members w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <div class="stat-value text-2xl font-extrabold text-slate-100 font-mono tracking-tight" id="stat-total-members">{{ number_format($stats['total_members']) }}</div>
                    <div class="stat-label text-xs text-slate-400 mt-0.5">Total Members (dbt_apy.family_members)</div>
                </div>
            </div>

            <div class="stat-card highlight-card bg-gradient-to-br from-slate-900 via-slate-900 to-amber-950/30 border border-amber-500/40 rounded-2xl p-5 flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                <div class="stat-icon icon-annapurna w-12 h-12 rounded-xl bg-amber-500/15 text-amber-300 flex items-center justify-center border border-amber-500/30">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div>
                    <div class="stat-value text-accent text-2xl font-extrabold text-amber-300 font-mono tracking-tight" id="stat-annapurna">{{ number_format($stats['annapurna_recipients']) }}</div>
                    <div class="stat-label text-xs text-amber-300/80 mt-0.5">Annapurna Bhandar (Females 25-60)</div>
                </div>
            </div>

            <div class="stat-card bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex items-center gap-4 shadow-lg hover:border-slate-700 transition-all">
                <div class="stat-icon icon-districts w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </div>
                <div>
                    <div class="stat-value text-2xl font-extrabold text-slate-100 font-mono tracking-tight" id="stat-districts">{{ number_format($stats['districts_count']) }}</div>
                    <div class="stat-label text-xs text-slate-400 mt-0.5">Districts Active in DB</div>
                </div>
            </div>
        </section>

        <!-- Rule Banner -->
        <div class="rule-banner bg-gradient-to-r from-amber-950/40 via-slate-900/70 to-slate-900/50 border border-amber-500/30 rounded-2xl p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-3.5 text-sm shadow-lg">
            <div class="rule-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 whitespace-nowrap">
                <span class="sparkle">✨</span> Business Rule Applied
            </div>
            <div class="rule-text text-slate-300 text-xs sm:text-sm leading-relaxed">
                <strong>Annapurna Bhandar Scheme Rule:</strong> If a family contains a <strong>Female member aged between 25 and 60 years</strong>, the database column <code>applying_for_annapurna_bhandar</code> is automatically set to <code>true</code>. All demographic and identity records strictly conform to <strong>Indian Origin standards</strong>.
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-header flex items-center gap-2 p-1.5 bg-slate-900/80 border border-slate-800 rounded-2xl mb-6 overflow-x-auto shadow-md">
            <button type="button" class="tab-btn active flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all cursor-pointer whitespace-nowrap" data-tab="tab-manual">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span>Family Registration Form</span>
            </button>
            <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all cursor-pointer whitespace-nowrap text-slate-400 hover:text-slate-200" data-tab="tab-generator">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <span>Quick Indian Data Seeder</span>
            </button>
            <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all cursor-pointer whitespace-nowrap text-slate-400 hover:text-slate-200" data-tab="tab-records" id="records-tab-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>Database Records Explorer</span>
            </button>
        </div>

        <!-- TAB 1: MANUAL REGISTRATION FORM -->
        <div class="tab-content active" id="tab-manual">
            <form id="family-form" autocomplete="off">
                <!-- Action Bar Inside Form -->
                <div class="form-header-bar">
                    <div>
                        <h2 class="section-title">New Family Application Entry</h2>
                        <p class="section-subtitle">Data inserted directly into <code>dbt_apy.families</code> & <code>dbt_apy.family_members</code></p>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn btn-secondary" id="btn-prefill-sample">
                            <span class="icon">🎲</span> Pre-fill Sample Indian Family
                        </button>
                        <button type="button" class="btn btn-ghost" id="btn-reset-form">
                            <span class="icon">↺</span> Reset Form
                        </button>
                    </div>
                </div>

                <!-- SECTION 1: LGD HIERARCHY -->
                <div class="card form-section">
                    <div class="card-header">
                        <div class="step-num">1</div>
                        <div>
                            <h3 class="card-title">Local Government Directory (LGD) Hierarchy</h3>
                            <p class="card-subtitle">Select jurisdiction path: District → Urban (Sub-Division/Municipality/Ward) or Rural (Block/Gram Panchayat)</p>
                        </div>
                    </div>

                    <div class="grid grid-3">
                        <!-- District -->
                        <div class="form-group">
                            <label class="form-label required" for="lgd_district_code">1. District</label>
                            <select id="lgd_district_code" name="lgd_district_code" class="form-control" required>
                                <option value="">-- Select West Bengal District --</option>
                                @foreach($districts as $d)
                                    <option value="{{ $d['id'] }}" {{ $d['id'] == 315 ? 'selected' : '' }}>
                                        {{ $d['text'] }} (Code: {{ $d['id'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Area Type -->
                        <div class="form-group">
                            <label class="form-label required">2. Area Classification</label>
                            <div class="radio-pill-group">
                                <label class="radio-pill">
                                    <input type="radio" name="area_type" value="Urban" checked>
                                    <span>🏛️ Urban (Municipality)</span>
                                </label>
                                <label class="radio-pill">
                                    <input type="radio" name="area_type" value="Rural">
                                    <span>🌾 Rural (Panchayat)</span>
                                </label>
                            </div>
                        </div>

                        <!-- LGD Summary Breadcrumb -->
                        <div class="form-group lgd-preview-box">
                            <label class="form-label">Active LGD Route</label>
                            <div class="lgd-badge" id="lgd-path-display">
                                <span>District: KMC</span> &rarr; <span>Urban</span>
                            </div>
                        </div>
                    </div>

                    <!-- URBAN FIELDS -->
                    <div class="urban-fields-container" id="urban-fields">
                        <div class="hierarchy-divider">
                            <span>Urban Governance Hierarchy: Sub-Division &rarr; Municipality (ULB) &rarr; Ward</span>
                        </div>
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label class="form-label" for="sub_division_id">Sub-Division</label>
                                <select id="sub_division_id" class="form-control">
                                    <option value="">-- Select Sub-Division (Optional) --</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="municipality_id">Municipality / Urban Local Body</label>
                                <select id="municipality_id" name="lgd_block_mc_code" class="form-control" required>
                                    <option value="">-- Select Municipality / ULB --</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="ward_id">Ward Number</label>
                                <select id="ward_id" name="lgd_gp_ward_code" class="form-control" required>
                                    <option value="">-- Select Ward --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- RURAL FIELDS -->
                    <div class="rural-fields-container hidden" id="rural-fields">
                        <div class="hierarchy-divider">
                            <span>Rural Governance Hierarchy: Block &rarr; Gram Panchayat (GP)</span>
                        </div>
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label required" for="block_id">Development Block</label>
                                <select id="block_id" class="form-control">
                                    <option value="">-- Select Block --</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label required" for="gp_id">Gram Panchayat (GP)</label>
                                <select id="gp_id" class="form-control">
                                    <option value="">-- Select Gram Panchayat --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: FAMILY MASTER DETAILS -->
                <div class="card form-section">
                    <div class="card-header">
                        <div class="step-num">2</div>
                        <div>
                            <h3 class="card-title">Family Master Information</h3>
                            <p class="card-subtitle">Household address, socioeconomic particulars, and ration card details</p>
                        </div>
                    </div>

                    <div class="grid grid-3">
                        <div class="form-group span-2">
                            <label class="form-label required" for="address">Residential Address (Indian Origin)</label>
                            <input type="text" id="address" name="address" class="form-control"
                                   placeholder="e.g. 14/B, Netaji Subhas Road, Near Station, Kolkata" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pincode">PIN Code</label>
                            <input type="text" id="pincode" name="pincode" class="form-control font-mono"
                                   placeholder="6-digit PIN e.g. 700029" maxlength="6">
                        </div>
                    </div>

                    <div class="grid grid-4 mt-3">
                        <div class="form-group">
                            <label class="form-label">Digital Ration Card?</label>
                            <div class="checkbox-pill">
                                <input type="checkbox" id="has_digital_ration_card" name="has_digital_ration_card" value="1" checked>
                                <label for="has_digital_ration_card">Yes, Has Ration Card</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ration_card_household_id">Ration Card Household ID</label>
                            <input type="text" id="ration_card_household_id" name="ration_card_household_id"
                                   class="form-control font-mono" placeholder="e.g. WB82910394">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Monthly Ration Lifting</label>
                            <div class="checkbox-pill">
                                <input type="checkbox" id="lifting_monthly_ration" name="lifting_monthly_ration" value="1" checked>
                                <label for="lifting_monthly_ration">Lifting Monthly Ration</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="total_annual_family_income">Annual Household Income (₹)</label>
                            <input type="number" id="total_annual_family_income" name="total_annual_family_income"
                                   class="form-control" value="120000" step="5000">
                        </div>
                    </div>

                    <div class="grid grid-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="no_of_literate_adults">Literate Adults Count</label>
                            <input type="number" id="no_of_literate_adults" name="no_of_literate_adults" class="form-control" value="2" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="no_of_illiterate_adults">Illiterate Adults Count</label>
                            <input type="number" id="no_of_illiterate_adults" name="no_of_illiterate_adults" class="form-control" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Applicant Declaration</label>
                            <div class="checkbox-pill required-pill">
                                <input type="checkbox" id="is_agreed" name="is_agreed" value="1" checked required>
                                <label for="is_agreed">All details provided are true (is_agreed)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: FAMILY MEMBERS (DYNAMIC) -->
                <div class="card form-section">
                    <div class="card-header justify-between">
                        <div class="flex items-center gap-3">
                            <div class="step-num">3</div>
                            <div>
                                <h3 class="card-title">Family Members (dbt_apy.family_members)</h3>
                                <p class="card-subtitle">Add members. Automatic Annapurna Bhandar eligibility check runs on every edit.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add-member">
                            <span class="icon">+</span> Add Family Member
                        </button>
                    </div>

                    <!-- Members Container -->
                    <div id="members-container" class="members-list">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="submit-bar">
                    <div class="submit-summary">
                        <span id="member-count-badge" class="badge badge-neutral">1 Member</span>
                        <span id="annapurna-count-badge" class="badge badge-accent">1 Annapurna Eligible</span>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="btn btn-secondary" id="btn-quick-fill-current">
                            <span class="icon">✨</span> Generate Indian Names for Members
                        </button>
                        <button type="submit" class="btn btn-accent btn-lg" id="btn-submit-form">
                            <span class="icon">🚀</span> Insert Family into Database
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB 2: BATCH SEEDER -->
        <div class="tab-content" id="tab-generator">
            <div class="card">
                <div class="card-header">
                    <div class="brand-icon">⚡</div>
                    <div>
                        <h2 class="card-title">Automated Indian Origin Data Seeder</h2>
                        <p class="card-subtitle">Bulk generate realistic Indian families with proper LGD hierarchy, verified Aadhaar, and strict Annapurna Bhandar rule logic.</p>
                    </div>
                </div>

                <div class="batch-controls-box">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label class="form-label" for="batch-district">Target District (or All)</label>
                            <select id="batch-district" class="form-control">
                                <option value="">-- Random Indian / WB District --</option>
                                @foreach($districts as $d)
                                    <option value="{{ $d['id'] }}">{{ $d['text'] }} ({{ $d['id'] }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="batch-area-type">Area Classification</label>
                            <select id="batch-area-type" class="form-control">
                                <option value="">Mixed (Urban & Rural)</option>
                                <option value="Urban">Urban Only (Municipalities / Wards)</option>
                                <option value="Rural">Rural Only (Blocks / Gram Panchayats)</option>
                            </select>
                        </div>

                        <div class="form-group span-3" style="grid-column: 1 / -1;">
                            <label class="form-label" for="batch-count">Number of Families to Generate (with Random Members)</label>
                            <div class="count-selector" id="batch-count-presets">
                                <button type="button" class="btn-count" data-count="10">10 Families</button>
                                <button type="button" class="btn-count" data-count="20">20 Families</button>
                                <button type="button" class="btn-count active" data-count="25">25 Families</button>
                                <button type="button" class="btn-count" data-count="50">50 Families</button>
                                <button type="button" class="btn-count" data-count="75">75 Families</button>
                                <button type="button" class="btn-count" data-count="100">100 Families</button>
                                <button type="button" class="btn-count" data-count="150">150 Families</button>
                                <button type="button" class="btn-count" data-count="200">200 Families</button>
                                <button type="button" class="btn-count" data-count="250">250 Families</button>
                                <button type="button" class="btn-count" data-count="450">450 Families</button>
                                <button type="button" class="btn-count" data-count="500">500 Families</button>
                            </div>
                            <div class="custom-count-wrapper" style="margin-top: 12px; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 13px; color: var(--text-muted);">Or enter custom quantity:</span>
                                <input type="number" id="custom-batch-count" class="form-control" style="width: 140px; height: 38px;" min="1" max="1000" placeholder="e.g. 50">
                                <span style="font-size: 12px; color: var(--text-muted);">(Supports up to 1,000 families)</span>
                            </div>
                        </div>
                    </div>

                    <div class="batch-cta-row">
                        <button type="button" class="btn btn-accent btn-lg" id="btn-run-batch">
                            <span class="icon">⚡</span> Run Batch Seeder to Database
                        </button>
                        <span class="batch-hint">Ensures 100% database schema compliance, sets UUIDs, triggers, and relations.</span>
                    </div>
                </div>

                <div id="batch-results-container" class="batch-results-container hidden">
                    <h3 class="results-title">Seeding Execution Results</h3>
                    <div class="batch-stat-summary" id="batch-summary-stats"></div>
                    <div class="table-container">
                        <table class="data-table" id="batch-table">
                            <thead>
                                <tr>
                                    <th>Family UUID</th>
                                    <th>Head of Family (HOF)</th>
                                    <th>District & Location</th>
                                    <th>Members</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="batch-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: RECORDS EXPLORER (POWERED BY LIVEWIRE DATATABLE) -->
        <div class="tab-content" id="tab-records">
            <livewire:family-data-table />
        </div>
    </main>

    <!-- Modal for Viewing Detailed Family Record -->
    <div class="modal-backdrop" id="family-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modal-title">Family Details</h3>
                    <p class="modal-subtitle" id="modal-subtitle">UUID</p>
                </div>
                <button type="button" class="modal-close" id="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body" id="modal-content">
                <!-- Injected via JS -->
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Scripts -->
    <script>
        window.APP_DATA = {
            csrfToken: "{{ csrf_token() }}",
            districts: @json($districts),
            masterData: @json($masterData),
            initialStats: @json($stats)
        };
    </script>
    <script src="{{ asset('js/seeder.js') }}"></script>
    @livewireScripts
</body>
</html>
