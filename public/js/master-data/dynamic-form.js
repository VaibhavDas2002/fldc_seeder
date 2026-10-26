window.initMasterData = function () {

    if (!window.masterDataV2 || !window.masterDataV2.districts) return;
    if (!window.Livewire) return;

    const md = window.masterDataV2;

    const addressSets = [
        { prefix: '', district: 'district_id', assembly: 'assemblie', urban: 'rural_urban', localbody: 'blockurban', gp: 'gpward' },
        { prefix: 'cur_', district: 'cur_district_id', assembly: 'cur_assemblie', urban: 'cur_rural_urban', localbody: 'cur_blockurban', gp: 'cur_gpward' }
    ];

    addressSets.forEach(set => {
        const districtSelect = document.querySelector(`select[name="${set.district}"]`);
        const assemblie = document.querySelector(`select[name="${set.assembly}"]`);
        const urban = document.querySelector(`select[name="${set.urban}"]`);
        const localbody = document.querySelector(`select[name="${set.localbody}"]`);
        const gpward = document.querySelector(`select[name="${set.gp}"]`);

        if (!districtSelect) return;

        const root = districtSelect.closest('[wire\\:id]');
        if (!root) return;

        const component = Livewire.find(root.getAttribute('wire:id'));
        if (!component) return;

        /* ================= DISTRICT ================= */
        if (!districtSelect.dataset.loaded) {
            fillSelect(districtSelect, md.districts);
            districtSelect.dataset.loaded = "1";
        }
        restoreSelected(districtSelect, component);

        /* ================= ASSEMBLIE ================= */
        if (assemblie && districtSelect.value) {
            if (!assemblie.dataset.loaded && md.assemblies) {
                fillSelect(
                    assemblie,
                    md.assemblies.filter(a => a.district_code == districtSelect.value)
                );
                assemblie.dataset.loaded = "1";
            }
            restoreSelected(assemblie, component);
        }

        /* ================= RURAL / URBAN ================= */
        if (urban && !urban.dataset.loaded) {
            fillSelect(urban, [
                { id: 1, text: "Urban" },
                { id: 2, text: "Rural" }
            ]);
            urban.dataset.loaded = "1";
        }
        restoreSelected(urban, component);

        /* ================= BLOCK ================= */
        if (urban?.value && districtSelect.value) {
            if (!localbody.dataset.loaded) {
                if (urban.value == 2 && md.blocks) {
                    fillSelect(localbody, md.blocks.filter(b => b.district_code == districtSelect.value));
                }
                if (urban.value == 1 && md.ulbs) {
                    fillSelect(localbody, md.ulbs.filter(u => u.district_code == districtSelect.value));
                }
                localbody.dataset.loaded = "1";
            }
            restoreSelected(localbody, component);
        }

        /* ================= GP / WARD ================= */
        if (localbody?.value) {
            if (!gpward.dataset.loaded) {
                if (urban.value == 2 && md.gps) {
                    fillSelect(gpward, md.gps.filter(g => g.district_code == districtSelect.value && g.block_code == localbody.value));
                }
                if (urban.value == 1 && md.ulb_wards) {
                    fillSelect(gpward, md.ulb_wards.filter(w => w.urban_body_code == localbody.value));
                }
                gpward.dataset.loaded = "1";
            }
            restoreSelected(gpward, component);
        }

        /* ================= EVENTS ================= */
        districtSelect.onchange = () => {
            // if (districtSelect.disabled) return;
            clearSelect(assemblie);
            clearSelect(localbody);
            clearSelect(gpward);
            component.set('formData.' + set.district, districtSelect.value);
        };

        if (assemblie) {
            assemblie.onchange = () => {
                // if (assemblie.disabled) return;
                component.set('formData.' + set.assembly, assemblie.value);
            };
        }

        if (urban) {
            urban.onchange = () => {
                // if (urban.disabled) return;
                clearSelect(localbody);
                clearSelect(gpward);
                component.set('formData.' + set.urban, urban.value);
            };
        }

        if (localbody) {
            localbody.onchange = () => {
                // if (localbody.disabled) return;
                clearSelect(gpward);
                component.set('formData.' + set.localbody, localbody.value);
            };
        }

        if (gpward) {
            gpward.onchange = () => {
                // if (gpward.disabled) return;
                component.set('formData.' + set.gp, gpward.value);
            };
        }
    });
};

/* ================= HELPERS ================= */

function clearSelect(select) {
    if (!select) return;
    select.innerHTML = '<option value="">-- Select --</option>';
    delete select.dataset.loaded;
}

function fillSelect(select, list) {
    if (!select) return;
    clearSelect(select);

    list.forEach(row => {
        const opt = document.createElement('option');
        opt.value = row.id;
        opt.textContent = row.text;
        select.appendChild(opt);
    });
}

function restoreSelected(select, component) {
    if (!select || !component) return;

    const key = select.dataset.wire;
    if (!key) return;

    const value = component.get('formData.' + key);
    if (!value) return;

    const exists = [...select.options].some(
        opt => String(opt.value) === String(value)
    );

    if (exists) {
        select.value = value;
    }
}

/* ================= AUTO INIT ================= */
document.addEventListener('livewire:load', () => {
    window.initMasterData();
});

const observer = new MutationObserver(() => {
    window.initMasterData();
});
observer.observe(document.body, { childList: true, subtree: true });

