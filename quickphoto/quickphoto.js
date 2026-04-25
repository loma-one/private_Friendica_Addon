(function() {
    const BBCodePattern = /\[url=(.*?)\]\[img=(.*?)\](.*?)\[\/img\]\[\/url\]/gi;
    let throttleTimer;
    let isSubmitting = false;

    const i18nDesc = (window.qp_i18n && window.qp_i18n.imageDesc) ? window.qp_i18n.imageDesc : "Image description";

    const readMetadata = (textarea) => {
        try { return JSON.parse(textarea.getAttribute('data-qp-metadata') || '{}'); }
        catch { return {}; }
    };

    const simplify = (textarea) => {
        if (!textarea || !textarea.value.includes('[url=')) return;

        const current = textarea.value;
        const metadata = readMetadata(textarea);
        let hasChanged = false;

        const simple = current.replace(BBCodePattern, (match, urlPart, imgPart, existingDesc) => {
            const fileName = imgPart.split('/').pop();
            metadata[fileName] = { url: urlPart, img: imgPart };
            hasChanged = true;
            let userDesc = existingDesc.trim() || i18nDesc;
            return `[img]${fileName}|${userDesc}[/img]`;
        });

        if (hasChanged) {
            textarea.setAttribute('data-qp-metadata', JSON.stringify(metadata));
            textarea.value = simple;
            const pos = Math.min(textarea.selectionEnd, simple.length);
            textarea.setSelectionRange(pos, pos);
        }
    };

    const reconstruct = (textarea) => {
        if (!textarea || !textarea.value.includes('[img]')) return textarea.value;

        const metadata = readMetadata(textarea);
        return textarea.value.replace(/\[img\](.*?)\|(.*?)\[\/img\]/g, (match, fileName, desc) => {
            const data = metadata[fileName];
            if (data) {
                const finalDesc = (desc === i18nDesc) ? "" : desc;
                return `[url=${data.url}][img=${data.img}]${finalDesc}[/img][/url]`;
            }
            return match;
        });
    };

    const applySimplify = (textarea) => {
        if (isSubmitting || !textarea || !textarea.value || !textarea.value.includes('[/img]')) return;
        (window.requestIdleCallback || function(cb) { return setTimeout(cb, 1); })(() => {
            if (!isSubmitting) simplify(textarea);
        });
    };

    document.addEventListener('submit', (e) => {
        isSubmitting = true;
        e.target.querySelectorAll('textarea').forEach(t => t.value = reconstruct(t));
    }, true);

    if (typeof jQuery !== 'undefined') {
        const originalVal = jQuery.fn.val;
        jQuery.fn.val = function(value) {
            if (arguments.length === 0 && this.is('textarea')) return reconstruct(this[0]);
            if (arguments.length > 0 && this.is('textarea')) {
                const result = originalVal.apply(this, arguments);
                simplify(this[0]);
                return result;
            }
            return originalVal.apply(this, arguments);
        };
    }

    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            clearTimeout(throttleTimer);
            throttleTimer = setTimeout(() => applySimplify(e.target), 500);
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#wall-submit-preview, [id^="comment-edit-preview-link-"]')) return;
        isSubmitting = false;
        document.querySelectorAll('textarea').forEach(t => setTimeout(() => applySimplify(t), 300));
    }, true);
})();
