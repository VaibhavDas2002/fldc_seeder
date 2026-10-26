<div class="livewire-datatable-wrapper">
    <!-- Live Notification Banner -->
    @if($feedbackMessage)
        <div class="feedback-toast flex items-center justify-between gap-3 p-4 mb-4 rounded-xl shadow-lg border {{ $feedbackType === 'success' ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-200' : ($feedbackType === 'error' ? 'bg-rose-950/80 border-rose-500/40 text-rose-200' : 'bg-sky-950/80 border-sky-500/40 text-sky-200') }}">
            <span class="font-medium text-sm">{{ $feedbackMessage }}</span>
            <button type="button" wire:click="$set('feedbackMessage', '')" class="toast-dismiss text-lg leading-none opacity-70 hover:opacity-100 cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- DataTable Toolbar & Filters -->
    <div class="dt-toolbar card bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-2xl backdrop-blur-md">
        <div class="dt-toolbar-top flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="dt-search-box relative flex-1 flex items-center min-w-[280px]">
                <span class="search-icon absolute left-3.5 text-slate-400 pointer-events-none text-sm">🔍</span>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       class="form-control font-medium w-full pl-10 pr-9 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 shadow-inner"
                       placeholder="Search by Member Name, Address, Aadhaar, Mobile, or UUID...">
                @if($search)
                    <button type="button" wire:click="$set('search', '')" class="search-clear absolute right-3 text-slate-400 hover:text-slate-100 text-lg leading-none cursor-pointer">&times;</button>
                @endif
            </div>

            <div class="dt-quick-actions flex items-center gap-2.5 flex-wrap">
                <div class="bulk-seed-group flex items-center gap-2 bg-slate-950/60 p-1.5 rounded-xl border border-slate-800/80">
                    <select wire:model.live="bulkSeedCount" class="form-control font-mono px-3 py-1.5 h-9 text-xs font-semibold bg-slate-900 border border-slate-700/90 rounded-lg text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 cursor-pointer">
                        <option value="1">1 Family</option>
                        <option value="10">10 Families</option>
                        <option value="20">20 Families</option>
                        <option value="25">25 Families</option>
                        <option value="50">50 Families</option>
                        <option value="75">75 Families</option>
                        <option value="100">100 Families</option>
                        <option value="150">150 Families</option>
                        <option value="200">200 Families</option>
                        <option value="250">250 Families</option>
                        <option value="450">450 Families</option>
                        <option value="500">500 Families</option>
                    </select>

                    <button type="button" wire:click="bulkSeed" wire:loading.attr="disabled" class="btn btn-accent btn-sm inline-flex items-center gap-2 px-3.5 py-1.5 h-9 text-xs font-bold rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 shadow-md shadow-amber-500/20 active:scale-95 transition-all cursor-pointer" title="Bulk Seed selected count into PostgreSQL">
                        <span wire:loading.remove wire:target="bulkSeed">⚡ Bulk Insert ({{ $bulkSeedCount }})</span>
                        <span wire:loading wire:target="bulkSeed">⏳ Inserting {{ $bulkSeedCount }} to DB...</span>
                    </button>
                </div>

                <button type="button" wire:click="collapseAll" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 px-3 py-1.5 h-9 text-xs font-semibold rounded-lg bg-slate-800/90 hover:bg-slate-700/90 border border-slate-700 text-slate-200 transition-all cursor-pointer" title="Collapse All Rows">
                    ⊟ Collapse All
                </button>
            </div>
        </div>

        <div class="dt-filters-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-800/80">
            <div class="filter-item">
                <label class="filter-label text-xs font-semibold tracking-wide uppercase text-slate-400 mb-1.5 block">Filter District</label>
                <select wire:model.live="districtFilter" class="form-control w-full px-3 py-2 text-sm bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 cursor-pointer">
                    <option value="">All Districts ({{ count($districts) }})</option>
                    @foreach($districts as $d)
                        <option value="{{ $d['id'] }}">{{ $d['text'] }} ({{ $d['id'] }})</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label text-xs font-semibold tracking-wide uppercase text-slate-400 mb-1.5 block">Area Classification</label>
                <select wire:model.live="areaTypeFilter" class="form-control w-full px-3 py-2 text-sm bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 cursor-pointer">
                    <option value="">All Areas (Urban & Rural)</option>
                    <option value="Urban">🏛️ Urban Only (Municipality/Ward)</option>
                    <option value="Rural">🌾 Rural Only (Block/GP)</option>
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label text-xs font-semibold tracking-wide uppercase text-slate-400 mb-1.5 block">Annapurna Bhandar Rule</label>
                <select wire:model.live="annapurnaFilter" class="form-control w-full px-3 py-2 text-sm bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 cursor-pointer">
                    <option value="">All Families</option>
                    <option value="eligible_only">✨ Has Female Beneficiary (25-60 yrs)</option>
                    <option value="none">⚪ No Eligible Members</option>
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label text-xs font-semibold tracking-wide uppercase text-slate-400 mb-1.5 block">Per Page</label>
                <select wire:model.live="perPage" class="form-control font-mono w-full px-3 py-2 text-sm bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/50 cursor-pointer">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>
        </div>

        <!-- Active Filter Pills & Summary -->
        <div class="dt-status-ribbon flex flex-wrap items-center justify-between gap-3 mt-4 pt-3 border-t border-slate-800/60 text-xs text-slate-400">
            <div class="dt-count-text flex items-center gap-2">
                <span>Showing <strong class="text-slate-200">{{ $families->firstItem() ?? 0 }}</strong> to <strong class="text-slate-200">{{ $families->lastItem() ?? 0 }}</strong> of <strong class="text-slate-200">{{ $families->total() }}</strong> families</span>
                <span class="livewire-pill inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">Livewire Reactivity Active</span>
            </div>
            <div class="dt-filter-pills flex items-center gap-2 flex-wrap">
                @if($search)
                    <span class="badge badge-accent inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/40">Search: "{{ $search }}" <button type="button" wire:click="$set('search', '')" class="hover:text-white cursor-pointer">&times;</button></span>
                @endif
                @if($districtFilter)
                    <span class="badge badge-neutral inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">Dist: {{ $districtFilter }} <button type="button" wire:click="$set('districtFilter', '')" class="hover:text-white cursor-pointer">&times;</button></span>
                @endif
                @if($areaTypeFilter)
                    <span class="badge badge-neutral inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">Area: {{ $areaTypeFilter }} <button type="button" wire:click="$set('areaTypeFilter', '')" class="hover:text-white cursor-pointer">&times;</button></span>
                @endif
                @if($annapurnaFilter)
                    <span class="badge badge-accent inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/40">Filter: {{ $annapurnaFilter }} <button type="button" wire:click="$set('annapurnaFilter', '')" class="hover:text-white cursor-pointer">&times;</button></span>
                @endif
            </div>
        </div>
    </div>

    <!-- The DataTable -->
    <div class="table-container dt-table-card overflow-x-auto rounded-2xl border border-slate-800/90 bg-slate-950/70 shadow-2xl backdrop-blur-md mt-6 relative">
        <div wire:loading.flex class="dt-loading-overlay absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-slate-950/80 backdrop-blur-sm text-cyan-300 font-semibold">
            <div class="dt-loading-spinner w-8 h-8 rounded-full border-2 border-cyan-400 border-t-transparent animate-spin"></div>
            <span>Updating PostgreSQL Data...</span>
        </div>

        <table class="data-table w-full text-left border-collapse text-sm text-slate-300">
            <thead class="bg-slate-900/95 border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 font-bold">
                <tr>
                    <th class="py-3.5 px-3 text-center w-11">#</th>
                    <th class="py-3.5 px-3 cursor-pointer hover:text-slate-100 transition-colors" wire:click="sortBy('id')">
                        Family UUID
                        @if($sortField === 'id') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                    </th>
                    <th class="py-3.5 px-3 cursor-pointer hover:text-slate-100 transition-colors" wire:click="sortBy('lgd_district_code')">
                        LGD District
                        @if($sortField === 'lgd_district_code') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                    </th>
                    <th class="py-3.5 px-3">Area</th>
                    <th class="py-3.5 px-3">Residential Address</th>
                    <th class="py-3.5 px-3 cursor-pointer hover:text-slate-100 transition-colors" wire:click="sortBy('total_family_members')">
                        Members
                        @if($sortField === 'total_family_members') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                    </th>
                    <th class="py-3.5 px-3">Annapurna Scheme Beneficiaries</th>
                    <th class="py-3.5 px-3 cursor-pointer hover:text-slate-100 transition-colors" wire:click="sortBy('created_at')">
                        Registered At
                        @if($sortField === 'created_at') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                    </th>
                    <th class="py-3.5 px-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($families as $family)
                    @php
                        $isExpanded = isset($expandedFamilies[$family->id]);
                        $annapurnaCount = $family->members->where('applying_for_annapurna_bhandar', true)->count();
                    @endphp
                    <tr class="family-master-row transition-colors {{ $isExpanded ? 'row-expanded bg-slate-900/60' : 'hover:bg-slate-900/40' }}" wire:key="family-{{ $family->id }}">
                        <!-- Expand Toggle Button -->
                        <td class="py-3 px-3 text-center">
                            <button type="button"
                                    wire:click="toggleExpand('{{ $family->id }}')"
                                    class="btn-expand-row w-7 h-7 inline-flex items-center justify-center rounded-lg text-xs font-bold transition-all {{ $isExpanded ? 'expanded bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-800/80 text-slate-400 hover:text-slate-200 border border-slate-700' }}"
                                    title="{{ $isExpanded ? 'Collapse Members' : 'Expand Members' }}">
                                {{ $isExpanded ? '▼' : '▶' }}
                            </button>
                        </td>

                        <!-- UUID -->
                        <td class="py-3 px-3">
                            <code class="font-mono text-cyan-400 text-xs bg-cyan-950/30 px-2 py-1 rounded border border-cyan-800/30" title="{{ $family->id }}">
                                {{ substr($family->id, 0, 8) }}...{{ substr($family->id, -4) }}
                            </code>
                        </td>

                        <!-- LGD -->
                        <td class="py-3 px-3">
                            <div class="lgd-mini-block leading-tight">
                                <strong class="text-slate-200 text-xs">Code: {{ $family->lgd_district_code }}</strong>
                                <span class="lgd-sub-codes block text-[11px] text-slate-500 font-mono mt-0.5">MC: {{ $family->lgd_block_mc_code }} | GP: {{ $family->lgd_gp_ward_code }}</span>
                            </div>
                        </td>

                        <!-- Area -->
                        <td class="py-3 px-3">
                            <span class="badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $family->area_type === 'Urban' ? 'badge-accent bg-amber-500/15 text-amber-300 border border-amber-500/30' : 'badge-neutral bg-slate-800 text-slate-400 border border-slate-700' }}">
                                {{ $family->area_type === 'Urban' ? '🏛️ Urban' : '🌾 Rural' }}
                            </span>
                        </td>

                        <!-- Address -->
                        <td class="py-3 px-3 max-w-[260px]" title="{{ $family->address }}">
                            <div class="table-address-truncate truncate text-slate-300 text-xs font-medium">
                                {{ $family->address ?? 'Not Specified' }}
                            </div>
                        </td>

                        <!-- Total Members -->
                        <td class="py-3 px-3">
                            <button type="button" wire:click="toggleExpand('{{ $family->id }}')" class="badge badge-neutral hover-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800/90 text-slate-300 border border-slate-700 hover:border-slate-500 transition-colors cursor-pointer">
                                {{ $family->total_family_members }} Members {{ $isExpanded ? '▲' : '▼' }}
                            </button>
                        </td>

                        <!-- Annapurna Bhandar Beneficiaries -->
                        <td class="py-3 px-3">
                            @if($annapurnaCount > 0)
                                <span class="badge badge-accent inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-sm shadow-emerald-500/10">
                                    ✨ {{ $annapurnaCount }} Female (25-60)
                                </span>
                            @else
                                <span class="badge badge-neutral inline-flex items-center px-2 py-0.5 rounded-full text-xs text-slate-500 bg-slate-800/50 border border-slate-800">
                                    None
                                </span>
                            @endif
                        </td>

                        <!-- Registered At -->
                        <td class="py-3 px-3 text-xs text-slate-400 font-mono">
                            {{ $family->created_at ? \Carbon\Carbon::parse($family->created_at)->diffForHumans() : 'Just now' }}
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-3 text-right">
                            <div class="dt-row-actions inline-flex items-center gap-1.5 justify-end">
                                <button type="button"
                                        wire:click="viewFamily('{{ $family->id }}')"
                                        class="btn btn-secondary btn-xs px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer"
                                        title="Inspect Details">
                                    Inspect
                                </button>
                                <button type="button"
                                        wire:click="deleteFamily('{{ $family->id }}', {{ $family->lgd_district_code }})"
                                        wire:confirm="Are you sure you want to delete family {{ substr($family->id, 0, 8) }}... and all its members from PostgreSQL?"
                                        class="btn btn-danger btn-xs px-2 py-1 text-xs font-semibold rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-800/50 transition-colors cursor-pointer"
                                        title="Delete Family">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Expanded Nested Row: Family Members -->
                    @if($isExpanded)
                        <tr class="family-members-subrow bg-slate-950/90 border-b border-slate-800/80" wire:key="members-{{ $family->id }}">
                            <td colspan="9" class="p-4 sm:p-5">
                                <div class="members-expansion-box bg-slate-900/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl">
                                    <div class="members-box-header flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-slate-200">Family Members Master Roster</span>
                                            <span class="text-xs text-slate-500 font-mono">({{ count($family->members) }} Total registered in dbt_apy.family_members)</span>
                                        </div>
                                        <div class="annapurna-rule-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-500/10 border border-amber-500/20 text-amber-300">
                                            <span>Rule: Female 25-60 yrs ➔ applying_for_annapurna_bhandar = true</span>
                                        </div>
                                    </div>

                                    <div class="table-container overflow-x-auto rounded-xl border border-slate-800/80 bg-slate-950/80">
                                        <table class="data-table member-sub-table w-full text-left border-collapse text-xs text-slate-300">
                                            <thead class="bg-slate-900 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400 font-bold">
                                                <tr>
                                                    <th class="py-2.5 px-3">Role</th>
                                                    <th class="py-2.5 px-3">Member Name (Indian Origin)</th>
                                                    <th class="py-2.5 px-3">Relation</th>
                                                    <th class="py-2.5 px-3">Gender</th>
                                                    <th class="py-2.5 px-3">DOB / Age</th>
                                                    <th class="py-2.5 px-3">Aadhaar Number (Verhoeff)</th>
                                                    <th class="py-2.5 px-3">Mobile</th>
                                                    <th class="py-2.5 px-3">Annapurna Bhandar Column</th>
                                                    <th class="py-2.5 px-3">Bank & IFSC</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-800/60">
                                                @foreach($family->members as $member)
                                                    @php
                                                        $age = $member->date_of_birth ? \Carbon\Carbon::parse($member->date_of_birth)->age : 'N/A';
                                                    @endphp
                                                    <tr class="{{ $member->applying_for_annapurna_bhandar ? 'annapurna-member-row bg-amber-500/5' : 'hover:bg-slate-900/40' }} transition-colors">
                                                        <td class="py-2 px-3">
                                                            @if($member->is_hof)
                                                                <span class="badge badge-accent inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">HOF (Head)</span>
                                                            @else
                                                                <span class="badge badge-neutral inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">Member</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2 px-3">
                                                            <strong class="text-slate-200">{{ $member->member_name }}</strong>
                                                        </td>
                                                        <td class="py-2 px-3 text-slate-400">{{ $member->relation_with_head_of_family ?? 'Self' }}</td>
                                                        <td class="py-2 px-3">
                                                            <span class="gender-pill inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ strtolower($member->gender) === 'female' ? 'bg-rose-500/15 text-rose-300 border border-rose-500/30' : 'bg-sky-500/15 text-sky-300 border border-sky-500/30' }}">
                                                                {{ $member->gender }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2 px-3">
                                                            <div class="text-slate-300 font-mono">{{ $member->date_of_birth ?? 'N/A' }}</div>
                                                            <small class="text-slate-500">Age: {{ $age }} yrs</small>
                                                        </td>
                                                        <td class="py-2 px-3 font-mono">
                                                            <span class="text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-800/40 text-xs">
                                                                {{ chunk_split($member->aadhaar_no, 4, ' ') }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2 px-3 font-mono text-slate-300 text-xs">
                                                            {{ $member->mobile_no ?? 'N/A' }}
                                                        </td>
                                                        <td class="py-2 px-3">
                                                            @if($member->applying_for_annapurna_bhandar)
                                                                <span class="annapurna-status-badge eligible inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm shadow-amber-500/10">
                                                                    ✨ true (Eligible: Female 25-60)
                                                                </span>
                                                            @else
                                                                <span class="annapurna-status-badge ineligible inline-flex items-center px-2 py-0.5 rounded-full text-xs text-slate-500 bg-slate-800/50 border border-slate-800">
                                                                    false ({{ $member->gender === 'Female' ? 'Age: '.$age : 'Male' }})
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2 px-3">
                                                            <div class="text-xs">
                                                                <strong class="text-slate-200 block truncate max-w-[120px]">{{ $member->bank_name ?? 'N/A' }}</strong>
                                                                <span class="font-mono text-slate-500 text-[11px]">{{ $member->ifsc_code ?? '' }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="dt-empty-state py-12 px-4 text-center">
                            <div class="empty-icon text-5xl mb-3">📭</div>
                            <h3 class="text-lg font-bold text-slate-200 mb-1">No Family Records Found</h3>
                            <p class="text-sm text-slate-400 max-w-md mx-auto">No records matched your search filters. Try clearing your filters or click <strong>Quick Seed</strong>.</p>
                            <div class="flex items-center justify-center gap-3 mt-4">
                                <button type="button" wire:click="quickSeedSample" class="btn btn-secondary btn-sm px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">
                                    ⚡ Quick Seed 1 Family
                                </button>
                                <button type="button" wire:click="bulkSeed(25)" class="btn btn-accent btn-sm px-4 py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 shadow-md shadow-amber-500/20 active:scale-95 transition-all cursor-pointer">
                                    ⚡ Bulk Seed 25 Families
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Livewire Pagination Links -->
    <div class="dt-pagination-wrapper mt-5 flex items-center justify-between text-xs text-slate-400">
        {{ $families->links() }}
    </div>

    <!-- Livewire Modal for Detailed View -->
    @if($viewingFamilyId && $viewingFamilyDetails)
        <div class="modal-backdrop active fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" wire:click.self="closeModal">
            <div class="modal-card bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl max-w-4xl w-full text-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="modal-header flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                    <div>
                        <h3 class="modal-title text-lg font-bold text-slate-100">Family Record #{{ substr($viewingFamilyId, 0, 8) }}</h3>
                        <p class="modal-subtitle font-mono text-xs text-cyan-400 mt-0.5">{{ $viewingFamilyId }}</p>
                    </div>
                    <button type="button" wire:click="closeModal" class="modal-close text-slate-400 hover:text-white text-2xl leading-none cursor-pointer">&times;</button>
                </div>

                <div class="modal-body space-y-5">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 bg-slate-950/60 p-4 rounded-xl border border-slate-800/80 text-xs">
                        <div>LGD District: <strong class="text-slate-100 font-mono">{{ $viewingFamilyDetails['lgd_district_code'] }}</strong></div>
                        <div>Block/MC Code: <strong class="text-slate-100 font-mono">{{ $viewingFamilyDetails['lgd_block_mc_code'] }}</strong></div>
                        <div>GP/Ward Code: <strong class="text-slate-100 font-mono">{{ $viewingFamilyDetails['lgd_gp_ward_code'] }}</strong></div>
                        <div>Area Type: <strong class="text-slate-100">{{ $viewingFamilyDetails['area_type'] }}</strong></div>
                        <div>Total Members: <strong class="text-slate-100">{{ $viewingFamilyDetails['total_family_members'] }}</strong></div>
                        <div>Annual Income: <strong class="text-emerald-400 font-mono">₹{{ number_format($viewingFamilyDetails['total_annual_family_income'] ?? 0) }}</strong></div>
                        <div class="col-span-2">Address: <strong class="text-slate-100">{{ $viewingFamilyDetails['address'] }}</strong></div>
                        <div>Ration Household ID: <strong class="font-mono text-amber-300">{{ $viewingFamilyDetails['ration_card_household_id'] ?? 'N/A' }}</strong></div>
                    </div>

                    <h4 class="text-sm font-bold text-slate-200 tracking-wide uppercase pt-2">
                        Family Members ({{ count($viewingFamilyDetails['members'] ?? []) }})
                    </h4>

                    <div class="members-modal-list space-y-3">
                        @foreach($viewingFamilyDetails['members'] ?? [] as $m)
                            <div class="member-card p-4 rounded-xl border {{ !empty($m['applying_for_annapurna_bhandar']) ? 'bg-amber-500/5 border-amber-500/30' : 'bg-slate-950/60 border-slate-800/80' }}">
                                <div class="member-card-header flex items-center justify-between pb-2 mb-2 border-b border-slate-800/60">
                                    <div class="flex items-center gap-2">
                                        <strong class="text-sm text-slate-100">{{ $m['member_name'] }}</strong>
                                        @if(!empty($m['is_hof']))
                                            <span class="badge badge-accent inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">HOF</span>
                                        @endif
                                        <span class="text-xs text-slate-400">({{ $m['relation_with_head_of_family'] ?? 'Self' }})</span>
                                    </div>
                                    <span class="annapurna-status-badge text-xs font-semibold px-2.5 py-1 rounded-full {{ !empty($m['applying_for_annapurna_bhandar']) ? 'eligible bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'ineligible bg-slate-800 text-slate-500 border border-slate-700' }}">
                                        {{ !empty($m['applying_for_annapurna_bhandar']) ? '✨ Annapurna Eligible (true)' : '⚪ Standard Member (false)' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-slate-400">
                                    <div>Gender: <strong class="text-slate-200">{{ $m['gender'] }}</strong></div>
                                    <div>DOB: <strong class="text-slate-200">{{ $m['date_of_birth'] ?? 'N/A' }}</strong></div>
                                    <div>Aadhaar: <code class="font-mono text-emerald-400">{{ $m['aadhaar_no'] }}</code></div>
                                    <div>Mobile: <strong class="text-slate-200 font-mono">{{ $m['mobile_no'] ?? 'N/A' }}</strong></div>
                                    <div>Bank: <strong class="text-slate-200">{{ $m['bank_name'] ?? 'N/A' }}</strong></div>
                                    <div>Account No: <code class="font-mono text-slate-300">{{ $m['bank_account_no'] ?? 'N/A' }}</code></div>
                                    <div>IFSC: <code class="font-mono text-cyan-400">{{ $m['ifsc_code'] ?? 'N/A' }}</code></div>
                                    <div>Voter ID (EPIC): <code class="font-mono text-amber-300">{{ $m['epic_no'] ?? 'N/A' }}</code></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-4 mt-4 border-t border-slate-800">
                    <button type="button" wire:click="closeModal" class="btn btn-secondary px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
