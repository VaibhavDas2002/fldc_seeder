/**
 * Annapurna Consolidated · DBT APY Seeder Script
 */
(function () {
    'use strict';

    // Verhoeff Algorithm for Client-side Aadhaar Validation
    const verhoeffD = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
    ];
    const verhoeffP = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
    ];
    const verhoeffInv = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    function validateAadhaar(num) {
        num = String(num).replace(/\D/g, '');
        if (num.length !== 12) return false;
        let c = 0;
        let arr = num.split('').map(Number).reverse();
        for (let i = 0; i < arr.length; i++) {
            c = verhoeffD[c][verhoeffP[i % 8][arr[i]]];
        }
        return c === 0;
    }

    function generateAadhaar() {
        let digits = [Math.floor(Math.random() * 8) + 2];
        for (let i = 0; i < 10; i++) {
            digits.push(Math.floor(Math.random() * 10));
        }
        let c = 0;
        let rev = [...digits].reverse();
        for (let i = 0; i < rev.length; i++) {
            c = verhoeffD[c][verhoeffP[(i + 1) % 8][rev[i]]];
        }
        digits.push(verhoeffInv[c]);
        return digits.join('');
    }

    // Calculate age from Date string YYYY-MM-DD
    function calculateAge(dobStr) {
        if (!dobStr) return 0;
        const dob = new Date(dobStr);
        if (isNaN(dob.getTime())) return 0;
        const diffMs = Date.now() - dob.getTime();
        const ageDt = new Date(diffMs);
        return Math.abs(ageDt.getUTCFullYear() - 1970);
    }

    // Toast notifications
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icons = { success: '✓', error: '✕', info: 'ℹ' };
        toast.innerHTML = `
            <span style="font-weight:bold; font-size:16px;">${icons[type] || '•'}</span>
            <div style="flex:1;">${message}</div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // DOM Elements
    const districtSelect = document.getElementById('lgd_district_code');
    const areaTypeRadios = document.querySelectorAll('input[name="area_type"]');
    const urbanContainer = document.getElementById('urban-fields');
    const ruralContainer = document.getElementById('rural-fields');
    const subDivisionSelect = document.getElementById('sub_division_id');
    const municipalitySelect = document.getElementById('municipality_id');
    const wardSelect = document.getElementById('ward_id');
    const blockSelect = document.getElementById('block_id');
    const gpSelect = document.getElementById('gp_id');
    const lgdPathDisplay = document.getElementById('lgd-path-display');
    const membersContainer = document.getElementById('members-container');
    const btnAddMember = document.getElementById('btn-add-member');
    const familyForm = document.getElementById('family-form');
    const btnPrefillSample = document.getElementById('btn-prefill-sample');
    const btnResetForm = document.getElementById('btn-reset-form');
    const btnQuickFillCurrent = document.getElementById('btn-quick-fill-current');

    let memberIndexCounter = 0;

    // Tab Switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const target = document.getElementById(btn.dataset.tab);
            if (target) target.classList.add('active');

            if (btn.dataset.tab === 'tab-records') {
                loadRecords();
            }
        });
    });

    // Area Type Toggle Handler
    function handleAreaTypeChange() {
        const selectedArea = document.querySelector('input[name="area_type"]:checked')?.value || 'Urban';
        if (selectedArea === 'Urban') {
            urbanContainer.classList.remove('hidden');
            ruralContainer.classList.add('hidden');

            municipalitySelect.setAttribute('name', 'lgd_block_mc_code');
            municipalitySelect.setAttribute('required', 'required');
            wardSelect.setAttribute('name', 'lgd_gp_ward_code');
            wardSelect.setAttribute('required', 'required');

            blockSelect.removeAttribute('name');
            blockSelect.removeAttribute('required');
            gpSelect.removeAttribute('name');
            gpSelect.removeAttribute('required');
        } else {
            urbanContainer.classList.add('hidden');
            ruralContainer.classList.remove('hidden');

            blockSelect.setAttribute('name', 'lgd_block_mc_code');
            blockSelect.setAttribute('required', 'required');
            gpSelect.setAttribute('name', 'lgd_gp_ward_code');
            gpSelect.setAttribute('required', 'required');

            municipalitySelect.removeAttribute('name');
            municipalitySelect.removeAttribute('required');
            wardSelect.removeAttribute('name');
            wardSelect.removeAttribute('required');
        }
        updateLgdPathDisplay();
        loadLgdOptionsForCurrentDistrict();
    }

    areaTypeRadios.forEach(radio => radio.addEventListener('change', handleAreaTypeChange));

    function updateLgdPathDisplay() {
        const dText = districtSelect.options[districtSelect.selectedIndex]?.text || 'No District';
        const area = document.querySelector('input[name="area_type"]:checked')?.value || 'Urban';
        let detail = '';

        if (area === 'Urban') {
            const mText = municipalitySelect.options[municipalitySelect.selectedIndex]?.text || '';
            const wText = wardSelect.options[wardSelect.selectedIndex]?.text || '';
            detail = mText ? ` → ${mText}` : '';
            if (wText) detail += ` → ${wText}`;
        } else {
            const bText = blockSelect.options[blockSelect.selectedIndex]?.text || '';
            const gText = gpSelect.options[gpSelect.selectedIndex]?.text || '';
            detail = bText ? ` → ${bText}` : '';
            if (gText) detail += ` → ${gText}`;
        }

        lgdPathDisplay.innerHTML = `<span>District: <strong>${dText.split('(')[0].trim()}</strong></span> → <span>${area}</span>${detail}`;
    }

    // Load LGD Cascading Options
    async function loadLgdOptionsForCurrentDistrict() {
        const districtId = districtSelect.value;
        if (!districtId) return;

        const area = document.querySelector('input[name="area_type"]:checked')?.value || 'Urban';

        if (area === 'Urban') {
            // Load Subdistricts
            try {
                const res = await fetch(`/api/lgd/subdistricts/${districtId}`);
                const subdistricts = await res.json();
                subDivisionSelect.innerHTML = '<option value="">-- Select Sub-Division (Optional) --</option>';
                subdistricts.forEach(s => {
                    subDivisionSelect.innerHTML += `<option value="${s.id}">${s.text}</option>`;
                });
            } catch (e) {
                console.error(e);
            }

            // Load Municipalities (ULBs)
            try {
                const res = await fetch(`/api/lgd/ulbs/${districtId}`);
                const ulbs = await res.json();
                municipalitySelect.innerHTML = '<option value="">-- Select Municipality / ULB --</option>';
                ulbs.forEach(u => {
                    municipalitySelect.innerHTML += `<option value="${u.id}" data-sub="${u.sub_district_code}">${u.text}</option>`;
                });

                if (ulbs.length > 0) {
                    municipalitySelect.selectedIndex = 1;
                    municipalitySelect.dispatchEvent(new Event('change'));
                }
            } catch (e) {
                console.error(e);
            }
        } else {
            // Load Blocks
            try {
                const res = await fetch(`/api/lgd/blocks/${districtId}`);
                const blocks = await res.json();
                blockSelect.innerHTML = '<option value="">-- Select Block --</option>';
                blocks.forEach(b => {
                    blockSelect.innerHTML += `<option value="${b.id}">${b.text}</option>`;
                });

                if (blocks.length > 0) {
                    blockSelect.selectedIndex = 1;
                    blockSelect.dispatchEvent(new Event('change'));
                }
            } catch (e) {
                console.error(e);
            }
        }
        updateLgdPathDisplay();
    }

    districtSelect.addEventListener('change', () => {
        loadLgdOptionsForCurrentDistrict();
    });

    subDivisionSelect.addEventListener('change', async () => {
        const districtId = districtSelect.value;
        const subId = subDivisionSelect.value;
        if (!districtId) return;

        try {
            const url = subId ? `/api/lgd/ulbs/${districtId}/${subId}` : `/api/lgd/ulbs/${districtId}`;
            const res = await fetch(url);
            const ulbs = await res.json();
            municipalitySelect.innerHTML = '<option value="">-- Select Municipality / ULB --</option>';
            ulbs.forEach(u => {
                municipalitySelect.innerHTML += `<option value="${u.id}">${u.text}</option>`;
            });
            if (ulbs.length > 0) {
                municipalitySelect.selectedIndex = 1;
                municipalitySelect.dispatchEvent(new Event('change'));
            }
        } catch (e) {
            console.error(e);
        }
        updateLgdPathDisplay();
    });

    municipalitySelect.addEventListener('change', async () => {
        const ulbId = municipalitySelect.value;
        wardSelect.innerHTML = '<option value="">-- Select Ward --</option>';
        if (!ulbId) {
            updateLgdPathDisplay();
            return;
        }

        try {
            const res = await fetch(`/api/lgd/wards/${ulbId}`);
            const wards = await res.json();
            if (wards.length === 0) {
                // Generate a dummy ward if data not listed
                wardSelect.innerHTML += `<option value="101" selected>Ward No. 1</option>`;
            } else {
                wards.forEach(w => {
                    wardSelect.innerHTML += `<option value="${w.id}">${w.text}</option>`;
                });
                wardSelect.selectedIndex = 1;
            }
        } catch (e) {
            console.error(e);
        }
        updateLgdPathDisplay();
    });

    wardSelect.addEventListener('change', updateLgdPathDisplay);

    blockSelect.addEventListener('change', async () => {
        const districtId = districtSelect.value;
        const blockId = blockSelect.value;
        gpSelect.innerHTML = '<option value="">-- Select Gram Panchayat --</option>';
        if (!districtId || !blockId) {
            updateLgdPathDisplay();
            return;
        }

        try {
            const res = await fetch(`/api/lgd/gps/${districtId}/${blockId}`);
            const gps = await res.json();
            if (gps.length === 0) {
                gpSelect.innerHTML += `<option value="201" selected>Gram Panchayat 1</option>`;
            } else {
                gps.forEach(g => {
                    gpSelect.innerHTML += `<option value="${g.id}">${g.text}</option>`;
                });
                gpSelect.selectedIndex = 1;
            }
        } catch (e) {
            console.error(e);
        }
        updateLgdPathDisplay();
    });

    gpSelect.addEventListener('change', updateLgdPathDisplay);

    // ==========================================
    // MEMBER MANAGEMENT & ANNAPURNA ELIGIBILITY
    // ==========================================

    function checkMemberEligibility(memberCard) {
        const genderSelect = memberCard.querySelector('.member-gender');
        const dobInput = memberCard.querySelector('.member-dob');
        const badge = memberCard.querySelector('.annapurna-status-badge');

        const gender = genderSelect ? genderSelect.value : '';
        const dobStr = dobInput ? dobInput.value : '';
        const age = calculateAge(dobStr);

        // Strict Annapurna rule:
        // "family may contain a female member . if it is and the age is between 25-60 years then the column 'applying_for_annapurna_bhandar' be true"
        const isEligible = (gender === 'Female' && age >= 25 && age <= 60);

        if (isEligible) {
            badge.className = 'annapurna-status-badge eligible';
            badge.innerHTML = `✨ Annapurna Bhandar: Eligible (true · Age ${age})`;
            memberCard.classList.add('is-annapurna-eligible');
        } else {
            badge.className = 'annapurna-status-badge ineligible';
            const reason = gender !== 'Female' ? 'Not Female' : (age < 25 ? `Age ${age} < 25` : (age > 60 ? `Age ${age} > 60` : 'Not Eligible'));
            badge.innerHTML = `⚪ Annapurna Bhandar: False (${reason})`;
            memberCard.classList.remove('is-annapurna-eligible');
        }

        updateOverallSummary();
    }

    function updateOverallSummary() {
        const cards = document.querySelectorAll('.member-card');
        const total = cards.length;
        let eligible = 0;

        cards.forEach(card => {
            if (card.classList.contains('is-annapurna-eligible')) {
                eligible++;
            }
        });

        const countBadge = document.getElementById('member-count-badge');
        const annapurnaBadge = document.getElementById('annapurna-count-badge');

        if (countBadge) countBadge.textContent = `${total} Member${total === 1 ? '' : 's'}`;
        if (annapurnaBadge) {
            annapurnaBadge.textContent = `${eligible} Annapurna Eligible`;
            if (eligible > 0) {
                annapurnaBadge.className = 'badge badge-accent';
            } else {
                annapurnaBadge.className = 'badge badge-neutral';
            }
        }
    }

    function addMemberCard(data = {}) {
        const index = memberIndexCounter++;
        const isHof = data.is_hof !== undefined ? data.is_hof : (document.querySelectorAll('.member-card').length === 0);
        const name = data.member_name || '';
        const gender = data.gender || (isHof ? 'Female' : 'Male');
        const dob = data.date_of_birth || (gender === 'Female' ? '1990-06-15' : '1988-03-20');
        const aadhaar = data.aadhaar_no || generateAadhaar();
        const mobile = data.mobile_no || '98' + Math.floor(10000000 + Math.random() * 90000000);
        const relation = data.relation_with_head_of_family || (isHof ? 'Self' : 'Spouse');
        const bankName = data.bank_name || 'State Bank of India';
        const bankAcc = data.bank_account_no || '38' + Math.floor(100000000 + Math.random() * 900000000);
        const ifsc = data.ifsc_code || 'SBIN0001234';
        const epic = data.epic_no || 'WB' + Math.floor(10000000 + Math.random() * 90000000);
        const caste = data.social_category || 'General';

        const card = document.createElement('div');
        card.className = 'member-card';
        card.dataset.index = index;

        card.innerHTML = `
            <div class="member-card-header">
                <div class="member-card-title">
                    <span class="member-num-label">Member #${index + 1}</span>
                    <span class="member-hof-tag">${isHof ? '<span class="badge badge-accent">Head of Family (HOF)</span>' : ''}</span>
                </div>
                <div class="member-card-badges">
                    <span class="annapurna-status-badge">Checking eligibility...</span>
                    ${index > 0 ? `<button type="button" class="btn-remove-member" title="Remove Member">✕ Remove</button>` : ''}
                </div>
            </div>

            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label required">Full Legal Name (Indian Origin)</label>
                    <input type="text" name="members[${index}][member_name]" class="form-control member-name-input"
                           value="${name}" placeholder="e.g. Sunita Banerjee" required>
                </div>

                <div class="form-group">
                    <label class="form-label required">Gender</label>
                    <select name="members[${index}][gender]" class="form-control member-gender" required>
                        <option value="Female" ${gender === 'Female' ? 'selected' : ''}>Female (মহিলা)</option>
                        <option value="Male" ${gender === 'Male' ? 'selected' : ''}>Male (পুরুষ)</option>
                        <option value="Others" ${gender === 'Others' ? 'selected' : ''}>Others (অন্যান্য)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Date of Birth</label>
                    <input type="date" name="members[${index}][date_of_birth]" class="form-control member-dob"
                           value="${dob}" required>
                </div>
            </div>

            <div class="grid grid-4 mt-3">
                <div class="form-group">
                    <label class="form-label required">12-Digit Indian Aadhaar</label>
                    <input type="text" name="members[${index}][aadhaar_no]" class="form-control font-mono member-aadhaar"
                           value="${aadhaar}" maxlength="12" placeholder="12 digits" required>
                    <div class="aadhaar-status">Verhoeff Checked</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Relation to HOF</label>
                    <select name="members[${index}][relation_with_head_of_family]" class="form-control">
                        <option value="Self" ${relation === 'Self' ? 'selected' : ''}>Self (নিজে)</option>
                        <option value="Spouse" ${relation === 'Spouse' ? 'selected' : ''}>Spouse (স্বামী / স্ত্রী)</option>
                        <option value="Son" ${relation === 'Son' ? 'selected' : ''}>Son (পুত্র)</option>
                        <option value="Daughter" ${relation === 'Daughter' ? 'selected' : ''}>Daughter (কন্যা)</option>
                        <option value="Mother" ${relation === 'Mother' ? 'selected' : ''}>Mother (মা)</option>
                        <option value="Father" ${relation === 'Father' ? 'selected' : ''}>Father (বাবা)</option>
                        <option value="Brother" ${relation === 'Brother' ? 'selected' : ''}>Brother (ভাই)</option>
                        <option value="Sister" ${relation === 'Sister' ? 'selected' : ''}>Sister (বোন)</option>
                        <option value="Others" ${relation === 'Others' ? 'selected' : ''}>Others (অন্যান্য)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Indian Mobile Number</label>
                    <input type="tel" name="members[${index}][mobile_no]" class="form-control font-mono"
                           value="${mobile}" maxlength="10" placeholder="10-digit mobile">
                </div>

                <div class="form-group">
                    <label class="form-label">Social Category</label>
                    <select name="members[${index}][social_category]" class="form-control">
                        <option value="General" ${caste === 'General' ? 'selected' : ''}>General</option>
                        <option value="SC" ${caste === 'SC' ? 'selected' : ''}>Scheduled Caste (SC)</option>
                        <option value="ST" ${caste === 'ST' ? 'selected' : ''}>Scheduled Tribe (ST)</option>
                        <option value="OBC" ${caste === 'OBC' ? 'selected' : ''}>OBC</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-3 mt-3">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="members[${index}][bank_name]" class="form-control"
                           value="${bankName}">
                </div>

                <div class="form-group">
                    <label class="form-label">Bank Account No.</label>
                    <input type="text" name="members[${index}][bank_account_no]" class="form-control font-mono"
                           value="${bankAcc}">
                </div>

                <div class="form-group">
                    <label class="form-label">Bank IFSC Code</label>
                    <input type="text" name="members[${index}][ifsc_code]" class="form-control font-mono"
                           value="${ifsc}" maxlength="11">
                </div>
            </div>

            <div class="grid grid-4 mt-3">
                <div class="form-group">
                    <label class="form-label">EPIC / Voter Card</label>
                    <input type="text" name="members[${index}][epic_no]" class="form-control font-mono"
                           value="${epic}">
                </div>

                <div class="form-group">
                    <label class="form-label">Head of Family?</label>
                    <div class="checkbox-pill">
                        <input type="radio" name="hof_selection" value="${index}" ${isHof ? 'checked' : ''} class="hof-radio">
                        <label>Mark as Head (is_hof)</label>
                        <input type="hidden" name="members[${index}][is_hof]" value="${isHof ? '1' : '0'}" class="hof-hidden">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Health Insurance</label>
                    <div class="checkbox-pill">
                        <input type="checkbox" name="members[${index}][has_health_insurance]" value="1" checked>
                        <label>Swasthya Sathi</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Four Wheeler Own</label>
                    <div class="checkbox-pill">
                        <input type="checkbox" name="members[${index}][has_four_wheeler]" value="1">
                        <label>Has 4-Wheeler</label>
                    </div>
                </div>
            </div>
        `;

        membersContainer.appendChild(card);

        // Bind Events
        const genderSelect = card.querySelector('.member-gender');
        const dobInput = card.querySelector('.member-dob');
        const aadhaarInput = card.querySelector('.member-aadhaar');
        const aadhaarStatus = card.querySelector('.aadhaar-status');
        const btnRemove = card.querySelector('.btn-remove-member');
        const hofRadio = card.querySelector('.hof-radio');

        genderSelect.addEventListener('change', () => checkMemberEligibility(card));
        dobInput.addEventListener('input', () => checkMemberEligibility(card));

        function validateCurrentAadhaar() {
            const val = aadhaarInput.value.replace(/\D/g, '');
            if (val.length === 12) {
                const valid = validateAadhaar(val);
                aadhaarStatus.className = `aadhaar-status ${valid ? 'valid' : 'invalid'}`;
                aadhaarStatus.textContent = valid ? '✓ Valid Indian Aadhaar (Verhoeff Pass)' : '✕ Invalid Verhoeff Checksum';
            } else {
                aadhaarStatus.className = 'aadhaar-status';
                aadhaarStatus.textContent = `${val.length}/12 digits entered`;
            }
        }

        aadhaarInput.addEventListener('input', validateCurrentAadhaar);
        validateCurrentAadhaar();

        if (btnRemove) {
            btnRemove.addEventListener('click', () => {
                card.remove();
                renumberMembers();
                updateOverallSummary();
            });
        }

        // HOF radio mutual exclusivity
        hofRadio.addEventListener('change', () => {
            document.querySelectorAll('.hof-hidden').forEach(h => h.value = '0');
            document.querySelectorAll('.member-hof-tag').forEach(t => t.innerHTML = '');
            card.querySelector('.hof-hidden').value = '1';
            card.querySelector('.member-hof-tag').innerHTML = '<span class="badge badge-accent">Head of Family (HOF)</span>';
        });

        checkMemberEligibility(card);
        renumberMembers();
    }

    function renumberMembers() {
        const cards = document.querySelectorAll('.member-card');
        cards.forEach((c, idx) => {
            const label = c.querySelector('.member-num-label');
            if (label) label.textContent = `Member #${idx + 1}`;
        });
    }

    btnAddMember.addEventListener('click', () => {
        addMemberCard({ is_hof: false });
    });

    // ==========================================
    // PREFILL SAMPLE INDIAN FAMILY
    // ==========================================

    async function prefillSampleFamily() {
        btnPrefillSample.disabled = true;
        btnPrefillSample.innerHTML = '<span class="icon">⏳</span> Generating...';

        try {
            const districtId = districtSelect.value;
            const area = document.querySelector('input[name="area_type"]:checked')?.value || 'Urban';
            const res = await fetch(`/api/family/sample?district_code=${districtId}&area_type=${area}`);
            const json = await res.json();

            if (json.success && json.data) {
                const data = json.data;
                const fam = data.family;

                // Set address
                const addrInput = document.getElementById('address');
                if (addrInput) addrInput.value = fam.address;

                const incInput = document.getElementById('total_annual_family_income');
                if (incInput) incInput.value = fam.total_annual_family_income;

                const rcIdInput = document.getElementById('ration_card_household_id');
                if (rcIdInput) rcIdInput.value = fam.ration_card_household_id || '';

                // Clear existing members
                membersContainer.innerHTML = '';
                memberIndexCounter = 0;

                // Add sample members
                data.members.forEach(m => addMemberCard(m));

                showToast(`Loaded Indian sample family (${data.members.length} members) with Annapurna rules applied!`, 'success');
            }
        } catch (e) {
            showToast('Failed to fetch sample: ' + e.message, 'error');
        } finally {
            btnPrefillSample.disabled = false;
            btnPrefillSample.innerHTML = '<span class="icon">🎲</span> Pre-fill Sample Indian Family';
        }
    }

    btnPrefillSample.addEventListener('click', prefillSampleFamily);

    // Reset Form
    btnResetForm.addEventListener('click', () => {
        if (confirm('Are you sure you want to clear this form?')) {
            familyForm.reset();
            membersContainer.innerHTML = '';
            memberIndexCounter = 0;
            addMemberCard({ is_hof: true, gender: 'Female' });
            handleAreaTypeChange();
        }
    });

    // Quick Indian names generator
    btnQuickFillCurrent.addEventListener('click', () => {
        const bengaliFemales = ['Sunita Banerjee', 'Anjali Chatterjee', 'Priyanka Ghosh', 'Moushumi Mukherjee', 'Aparna Das'];
        const bengaliMales = ['Rajesh Mondal', 'Amit Roy', 'Sourav Sen', 'Debabrata Dutta', 'Bikram Bhattacharya'];

        document.querySelectorAll('.member-card').forEach((card, idx) => {
            const gender = card.querySelector('.member-gender').value;
            const nameInput = card.querySelector('.member-name-input');
            if (gender === 'Female') {
                nameInput.value = bengaliFemales[idx % bengaliFemales.length];
            } else {
                nameInput.value = bengaliMales[idx % bengaliMales.length];
            }
        });
        showToast('Updated member names with authentic Indian origin names.', 'info');
    });

    // ==========================================
    // FORM SUBMISSION (INSERT INTO DB)
    // ==========================================

    familyForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('btn-submit-form');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="icon">⏳</span> Inserting into PostgreSQL...';

        const formData = new FormData(familyForm);

        try {
            const res = await fetch('/api/family/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.APP_DATA.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok && data.success) {
                const benef = data.data.annapurna_beneficiaries;
                const benefMsg = benef.length > 0
                    ? ` (${benef.length} Annapurna Beneficiary: ${benef.join(', ')})`
                    : '';

                showToast(`Success! Family ${data.data.family_id} inserted into dbt_apy tables${benefMsg}`, 'success');

                // Update metric counters
                incrementMetrics(1, data.data.total_members, benef.length);

                // Optional: prompt to view records tab
                setTimeout(() => {
                    if (confirm('Family registered successfully! Would you like to view it in the Live Records Explorer?')) {
                        document.querySelector('[data-tab="tab-records"]').click();
                    }
                }, 800);
            } else {
                showToast(`Error: ${data.message || 'Validation failed'}`, 'error');
            }
        } catch (err) {
            showToast('Network error: ' + err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="icon">🚀</span> Insert Family into Database';
        }
    });

    function incrementMetrics(famCount, memberCount, annapurnaCount) {
        const famEl = document.getElementById('stat-total-families');
        const memEl = document.getElementById('stat-total-members');
        const annEl = document.getElementById('stat-annapurna');

        if (famEl) {
            const cur = parseInt(famEl.textContent.replace(/,/g, ''), 10) || 0;
            famEl.textContent = (cur + famCount).toLocaleString();
        }
        if (memEl) {
            const cur = parseInt(memEl.textContent.replace(/,/g, ''), 10) || 0;
            memEl.textContent = (cur + memberCount).toLocaleString();
        }
        if (annEl) {
            const cur = parseInt(annEl.textContent.replace(/,/g, ''), 10) || 0;
            annEl.textContent = (cur + annapurnaCount).toLocaleString();
        }
    }

    // ==========================================
    // BATCH SEEDER TAB
    // ==========================================

    let selectedBatchCount = 25;
    const customCountInput = document.getElementById('custom-batch-count');

    document.querySelectorAll('.btn-count').forEach(b => {
        b.addEventListener('click', () => {
            document.querySelectorAll('.btn-count').forEach(btn => btn.classList.remove('active'));
            b.classList.add('active');
            selectedBatchCount = parseInt(b.dataset.count, 10);
            if (customCountInput) {
                customCountInput.value = '';
            }
        });
    });

    if (customCountInput) {
        customCountInput.addEventListener('input', () => {
            const val = parseInt(customCountInput.value, 10);
            if (!isNaN(val) && val > 0) {
                document.querySelectorAll('.btn-count').forEach(btn => btn.classList.remove('active'));
                selectedBatchCount = Math.min(1000, val);
            }
        });
    }

    const btnRunBatch = document.getElementById('btn-run-batch');
    const batchResults = document.getElementById('batch-results-container');
    const batchSummary = document.getElementById('batch-summary-stats');
    const batchTableBody = document.getElementById('batch-table-body');

    btnRunBatch.addEventListener('click', async () => {
        btnRunBatch.disabled = true;
        btnRunBatch.innerHTML = '<span class="icon">⏳</span> Seeding Database in Real-Time...';

        const district = document.getElementById('batch-district').value;
        const areaType = document.getElementById('batch-area-type').value;

        try {
            const res = await fetch('/api/family/batch-seed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.APP_DATA.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    count: selectedBatchCount,
                    district_code: district || null,
                    area_type: areaType || null
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                showToast(`Successfully seeded ${data.count} Indian families into dbt_apy tables!`, 'success');
                batchResults.classList.remove('hidden');

                batchSummary.innerHTML = `
                    <div class="stat-card" style="padding:10px 16px;">
                        <strong style="color:var(--accent-blue);">${data.count}</strong> Families Seeded
                    </div>
                    <div class="stat-card" style="padding:10px 16px;">
                        <strong style="color:#c084fc;">${data.total_members}</strong> Members Added
                    </div>
                    <div class="stat-card" style="padding:10px 16px;">
                        <strong style="color:var(--accent-saffron);">${data.annapurna_recipients}</strong> Annapurna Beneficiaries
                    </div>
                `;

                batchTableBody.innerHTML = '';
                data.families.forEach(f => {
                    batchTableBody.innerHTML += `
                        <tr>
                            <td><code>${f.family_id}</code></td>
                            <td><strong>${f.hof}</strong></td>
                            <td>${f.district} · <span style="font-size:11px; color:var(--text-secondary);">${f.location}</span></td>
                            <td><span class="badge badge-neutral">${f.members_count} Members</span></td>
                            <td><span class="badge badge-success">✓ Saved to dbt_apy</span></td>
                        </tr>
                    `;
                });

                incrementMetrics(data.count, data.total_members, data.annapurna_recipients);
            } else {
                showToast(`Batch Error: ${data.message || 'Seeding failed'}`, 'error');
            }
        } catch (e) {
            showToast('Batch execution error: ' + e.message, 'error');
        } finally {
            btnRunBatch.disabled = false;
            btnRunBatch.innerHTML = '<span class="icon">⚡</span> Run Batch Seeder to Database';
        }
    });

    // ==========================================
    // DATABASE RECORDS EXPLORER
    // ==========================================

    const recordsTableBody = document.getElementById('records-table-body');
    const recordsSearch = document.getElementById('records-search');
    const recordsDistrictFilter = document.getElementById('records-district-filter');
    const btnRefreshRecords = document.getElementById('btn-refresh-records');
    const recordsCountLabel = document.getElementById('records-count-label');

    let allLoadedRecords = [];

    async function loadRecords() {
        if (!recordsTableBody) return;
        recordsTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-muted">Fetching live records from PostgreSQL...</td></tr>`;

        const search = recordsSearch ? recordsSearch.value : '';
        const dist = recordsDistrictFilter ? recordsDistrictFilter.value : '';

        try {
            const res = await fetch(`/api/family/records?search=${encodeURIComponent(search)}&district_code=${encodeURIComponent(dist)}`);
            const json = await res.json();

            if (json.success) {
                allLoadedRecords = json.data || [];
                renderRecordsTable(allLoadedRecords);
            } else {
                recordsTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-muted">Error loading records: ${json.message}</td></tr>`;
            }
        } catch (e) {
            recordsTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-muted">Network error: ${e.message}</td></tr>`;
        }
    }

    function renderRecordsTable(records) {
        recordsCountLabel.textContent = `Showing ${records.length} family record${records.length === 1 ? '' : 's'}`;

        if (records.length === 0) {
            recordsTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-muted">No records found. Use the Registration form or Batch Seeder to add families.</td></tr>`;
            return;
        }

        recordsTableBody.innerHTML = '';
        records.forEach(r => {
            const annapurnaCount = (r.members || []).filter(m => m.applying_for_annapurna_bhandar).length;
            const annapurnaLabel = annapurnaCount > 0
                ? `<span class="badge badge-accent">✨ ${annapurnaCount} Eligible</span>`
                : `<span class="badge badge-neutral">0 Eligible</span>`;

            const dateStr = r.created_at ? new Date(r.created_at).toLocaleString() : 'N/A';
            const shortId = r.id ? r.id.substring(0, 8) + '...' : '';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <button type="button" class="btn btn-secondary btn-sm btn-view-family" data-id="${r.id}" title="View Details">
                        👁 View
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm btn-del-family" data-id="${r.id}" data-dist="${r.lgd_district_code}" style="color:var(--accent-rose);" title="Delete Family">
                        🗑
                    </button>
                </td>
                <td><code title="${r.id}">${shortId}</code></td>
                <td>Dist: <strong>${r.lgd_district_code}</strong></td>
                <td><span class="badge ${r.area_type === 'Urban' ? 'badge-neutral' : 'badge-neutral'}">${r.area_type || 'N/A'}</span></td>
                <td style="max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${r.address || ''}">
                    ${r.address || 'Not specified'}
                </td>
                <td><span class="badge badge-neutral">${r.total_family_members || (r.members ? r.members.length : 0)} Members</span></td>
                <td>${annapurnaLabel}</td>
                <td><span class="badge badge-success">${r.application_status || 'DRAFT'}</span></td>
                <td style="font-size:11px; color:var(--text-secondary);">${dateStr}</td>
            `;

            recordsTableBody.appendChild(row);
        });

        // Bind Row Buttons
        document.querySelectorAll('.btn-view-family').forEach(btn => {
            btn.addEventListener('click', () => {
                const fam = allLoadedRecords.find(x => x.id === btn.dataset.id);
                if (fam) showFamilyModal(fam);
            });
        });

        document.querySelectorAll('.btn-del-family').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (confirm('Delete this family and all linked members from the database?')) {
                    const id = btn.dataset.id;
                    const dist = btn.dataset.dist;
                    try {
                        const res = await fetch(`/api/family/${id}/${dist}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': window.APP_DATA.csrfToken
                            }
                        });
                        const j = await res.json();
                        if (j.success) {
                            showToast('Record deleted.', 'info');
                            loadRecords();
                        }
                    } catch (e) {
                        showToast('Error deleting: ' + e.message, 'error');
                    }
                }
            });
        });
    }

    if (btnRefreshRecords) btnRefreshRecords.addEventListener('click', loadRecords);
    if (recordsSearch) recordsSearch.addEventListener('input', () => debounce(loadRecords, 300)());
    if (recordsDistrictFilter) recordsDistrictFilter.addEventListener('change', loadRecords);

    // Modal
    const modal = document.getElementById('family-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalContent = document.getElementById('modal-content');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtitle = document.getElementById('modal-subtitle');

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', () => modal.classList.remove('active'));
    }

    function showFamilyModal(fam) {
        modalTitle.textContent = `Family Master Record: ${fam.address || 'Address'}`;
        modalSubtitle.textContent = `ID: ${fam.id} | tmp_application_id: ${fam.tmp_application_id || 'N/A'}`;

        let membersHtml = '';
        (fam.members || []).forEach((m, i) => {
            const isEligible = m.applying_for_annapurna_bhandar;
            membersHtml += `
                <div class="member-card ${isEligible ? 'is-annapurna-eligible' : ''}" style="margin-bottom:12px;">
                    <div class="member-card-header">
                        <div>
                            <strong>#${i + 1} ${m.member_name}</strong>
                            ${m.is_hof ? '<span class="badge badge-accent" style="margin-left:6px;">HOF</span>' : ''}
                            <span style="font-size:12px; color:var(--text-secondary); margin-left:6px;">(${m.relation_with_head_of_family || 'Relation'})</span>
                        </div>
                        <span class="annapurna-status-badge ${isEligible ? 'eligible' : 'ineligible'}">
                            ${isEligible ? '✨ Annapurna Eligible (true)' : '⚪ Standard Member (false)'}
                        </span>
                    </div>
                    <div class="grid grid-4" style="font-size:12px;">
                        <div>Gender: <strong>${m.gender || 'N/A'}</strong></div>
                        <div>DOB: <strong>${m.date_of_birth || 'N/A'}</strong></div>
                        <div>Aadhaar: <code class="font-mono">${m.aadhaar_no || 'N/A'}</code></div>
                        <div>Mobile: <strong>${m.mobile_no || 'N/A'}</strong></div>
                        <div>Bank: <strong>${m.bank_name || 'N/A'}</strong></div>
                        <div>Account: <code class="font-mono">${m.bank_account_no || 'N/A'}</code></div>
                        <div>IFSC: <code class="font-mono">${m.ifsc_code || 'N/A'}</code></div>
                        <div>Voter ID (EPIC): <code class="font-mono">${m.epic_no || 'N/A'}</code></div>
                    </div>
                </div>
            `;
        });

        modalContent.innerHTML = `
            <div style="background:rgba(0,0,0,0.2); padding:14px; border-radius:var(--radius-md); margin-bottom:16px;">
                <div class="grid grid-4" style="font-size:12px;">
                    <div>LGD District: <strong>${fam.lgd_district_code}</strong></div>
                    <div>Block / MC Code: <strong>${fam.lgd_block_mc_code}</strong></div>
                    <div>GP / Ward Code: <strong>${fam.lgd_gp_ward_code}</strong></div>
                    <div>Area Type: <strong>${fam.area_type || 'N/A'}</strong></div>
                    <div>Digital Ration Card: <strong>${fam.has_digital_ration_card ? 'Yes' : 'No'}</strong></div>
                    <div>RC Household ID: <code class="font-mono">${fam.ration_card_household_id || 'N/A'}</code></div>
                    <div>Total Members: <strong>${fam.total_family_members}</strong></div>
                    <div>Annual Income: <strong>₹${Number(fam.total_annual_family_income || 0).toLocaleString()}</strong></div>
                </div>
            </div>

            <h4 style="font-family:var(--font-heading); font-size:15px; margin-bottom:10px;">Family Members (${(fam.members || []).length})</h4>
            ${membersHtml}
        `;

        modal.classList.add('active');
    }

    // Debounce helper
    function debounce(fn, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    // Initialize Default State on Load
    function init() {
        handleAreaTypeChange();
        // Add initial default member (Head of Family, Female 32)
        addMemberCard({
            is_hof: true,
            member_name: 'Sunita Banerjee',
            gender: 'Female',
            date_of_birth: '1992-04-12',
            relation_with_head_of_family: 'Self',
            social_category: 'General',
            bank_name: 'State Bank of India',
            ifsc_code: 'SBIN0001234'
        });
        // Add second member (Spouse, Male 35)
        addMemberCard({
            is_hof: false,
            member_name: 'Rajesh Banerjee',
            gender: 'Male',
            date_of_birth: '1989-11-23',
            relation_with_head_of_family: 'Spouse',
            social_category: 'General',
            bank_name: 'State Bank of India',
            ifsc_code: 'SBIN0001234'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
