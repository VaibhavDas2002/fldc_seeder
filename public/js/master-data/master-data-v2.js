(function () {
    window.masterDataV2 = window.masterDataV2 || {};

    const files = [
        'castes.js',
        'spouse.js',
        'districts.js',
        'assemblies.js',
        'blocks.js',
        'gps.js',
        'ulbs.js',
        'ulb_wards.js',
        'police_stations.js',
        'rural_urban.js',
        'dynamic-form.js',
    ];

    // Auto-detect base path directly from the script tag URL without needing window.assetBaseUrl
    let scriptSrc = document.currentScript ? document.currentScript.src : '';
    let basePath = scriptSrc ? scriptSrc.substring(0, scriptSrc.lastIndexOf('/') + 1) : '/js/master-data/';
    let loaded = 0;

    files.forEach(file => {
        const script = document.createElement('script');
        script.src = basePath + file + '?v=' + Date.now();
        script.onload = () => {
            loaded++;
            if (loaded === files.length) {
                window.dispatchEvent(new Event('masterdata:ready'));
            }
        };
        document.head.appendChild(script);
    });
})();
