(function () {

    let schemeJson = null;

    document.addEventListener('scheme-json-loaded', e => {
        schemeJson = e.detail.json;
        applyDependencies();
    });

    function applyDependencies() {

        if (!schemeJson || !schemeJson.tabs) return;

        schemeJson.tabs.forEach(tab => {
            (tab.fields || []).forEach(field => {

                // 🔑 only dependent fields
                if (!field.dependent_on || !field.dependent_on_values) return;

                const targetName     = field.field_name;
                const controllerName = field.dependent_on;
                const allowedValues  = Object.values(field.dependent_on_values).map(String);

                const target     = document.querySelector(`[name="${targetName}"]`);
                const controller = document.querySelector(`[name="${controllerName}"]`);

                if (!target || !controller) return;

                const wrapper = target.closest('.dep-field');
                if (!wrapper) return;

                wrapper.style.display = 'none';
                target.removeAttribute('required');

                const toggle = () => {
                    const val = String(controller.value || '');

                    if (allowedValues.includes(val)) {
                        wrapper.style.display = '';
                        target.setAttribute('required', 'required');
                    } else {
                        wrapper.style.display = 'none';
                        target.removeAttribute('required');
                        target.value = '';

                        // Livewire model clear
                        const lw = target.closest('[wire\\:id]');
                        if (lw && window.Livewire) {
                            Livewire.find(lw.getAttribute('wire:id'))
                                ?.set(`formData.${targetName}`, null);
                        }
                    }
                };

                controller.removeEventListener('change', toggle);
                controller.addEventListener('change', toggle);

                toggle(); // initial check
            });
        });
    }

    /* Tab / rerender safety */
    document.addEventListener('livewire:load', () => {
        Livewire.hook('message.processed', applyDependencies);
    });

})();
