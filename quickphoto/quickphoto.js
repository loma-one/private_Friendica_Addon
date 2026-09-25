(function() {
    if (window._qpInitialized) return;
    window._qpInitialized = true;

    const BBCodePattern = /\[url=(.*?)\]\[img=(.*?)\](.*?)\[\/img\]\[\/url\]/gi;
    const shorthandPattern = /\[img\](.*?)\|([\s\S]*?)\[\/img\]/gi;
    let throttleTimer;
    let isSubmitting = false;

    window._qpMetadataCache = window._qpMetadataCache || {};
    const i18nDesc = window.qp_i18n?.imageDesc || "Image description";

    ['pjax:end', 'page-changed', 'popstate'].forEach(evt => {
        document.addEventListener(evt, () => {
            isSubmitting = false;
            window._qpMetadataCache = {};
            document.querySelectorAll('.qp-edit-bar').forEach(bar => {
                if (bar.previousElementSibling?.tagName !== 'TEXTAREA') bar.remove();
            });
        });
    });

    const getOrCreateEditBar = (textarea) => {
        let bar = textarea.parentNode.querySelector('.qp-edit-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'qp-edit-bar';
            bar.style.cssText = 'display:none; align-items:center; gap:10px; margin-top:8px; padding:8px; width:100%; box-sizing:border-box; border:1px solid var(--border-color, #ccc); border-radius:4px;';
            bar.innerHTML = `
                <div style="flex-shrink:0; width:48px; height:48px; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid var(--border-color, #ddd); border-radius:4px;">
                    <img class="qp-preview-thumb" src="" alt="Preview" style="width:48px; height:48px; object-fit:cover; display:block;">
                </div>
                <div style="flex-grow:1;">
                    <input type="text" class="qp-alt-input" placeholder="${i18nDesc}" style="width:100%; padding:6px 10px; border:1px solid var(--border-color, #ccc); border-radius:4px; box-sizing:border-box; color:inherit;">
                </div>`;
            textarea.parentNode.insertBefore(bar, textarea.nextSibling);

            const input = bar.querySelector('.qp-alt-input');

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    textarea.focus();
                    bar.style.display = 'none';
                }
            });

            input.addEventListener('input', (e) => {
                const { imgPath, openTag, closeTag } = bar.dataset;
                if (!imgPath) return;

                const text = textarea.value;
                const newDesc = e.target.value.replace(/[\[\]]/g, '');
                const newTag = `[img]${imgPath}|${newDesc || i18nDesc}[/img]`;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;

                textarea.value = text.substring(0, parseInt(openTag, 10)) + newTag + text.substring(parseInt(closeTag, 10) + 6);
                textarea.setSelectionRange(start, end);
            });
        }
        return bar;
    };

    const checkCursorContext = (textarea) => {
        if (isSubmitting) return;

        const text = textarea.value;
        const bar = textarea.parentNode.querySelector('.qp-edit-bar');

        if (!text.includes('[img]')) {
            if (bar) bar.style.display = 'none';
            return;
        }

        const pos = textarea.selectionStart;
        const openTag = text.lastIndexOf('[img]', pos);
        const closeTag = text.indexOf('[/img]', pos);

        if (openTag !== -1 && closeTag !== -1 && pos >= openTag && pos <= (closeTag + 6)) {
            const tagContent = text.substring(openTag + 5, closeTag);

            if (tagContent.includes('|')) {
                const [imgPath, ...descParts] = tagContent.split('|');
                const currentDesc = descParts.join('|');
                const editBar = getOrCreateEditBar(textarea);
                const img = editBar.querySelector('.qp-preview-thumb');
                const input = editBar.querySelector('.qp-alt-input');

                Object.assign(editBar.dataset, { imgPath, openTag, closeTag });
                editBar.style.display = 'flex';

                const isValidUrl = /^(https?:\/\/|\/|data:)/i.test(imgPath.trim());
                img.src = isValidUrl ? imgPath.trim() : '';
                img.parentElement.style.display = isValidUrl ? 'flex' : 'none';

                if (document.activeElement !== input) {
                    input.value = (currentDesc === i18nDesc) ? '' : currentDesc;
                }
                return;
            }
        }

        if (bar) bar.style.display = 'none';
    };

    const simplify = (textarea) => {
        if (isSubmitting || !textarea.value.includes('[url=')) return;

        const current = textarea.value;
        const simple = current.replace(BBCodePattern, (match, urlPart, imgPart, existingDesc) => {
            window._qpMetadataCache[imgPart] = { url: urlPart, img: imgPart };
            const userDesc = existingDesc.trim();
            return `[img]${imgPart}|${userDesc && userDesc !== i18nDesc ? userDesc : i18nDesc}[/img]`;
        });

        if (current !== simple) {
            const { selectionStart: start, selectionEnd: end } = textarea;
            textarea.value = simple;
            textarea.setSelectionRange(start, end);
        }
    };

    const reconstruct = (textarea) => {
        return textarea.value.replace(shorthandPattern, (match, imgPath, desc) => {
            const metadata = window._qpMetadataCache[imgPath];
            if (metadata) {
                return `[url=${metadata.url}][img=${metadata.img}]${desc === i18nDesc ? '' : desc}[/img][/url]`;
            }
            return match;
        });
    };

    document.addEventListener('focusin', (e) => {
        if (e.target.tagName === 'TEXTAREA') simplify(e.target);
    });

    document.addEventListener('submit', (e) => {
        isSubmitting = true;
        e.target.querySelectorAll('textarea').forEach(textarea => {
            textarea.value = reconstruct(textarea);
        });
    }, true);

    document.addEventListener('keyup', (e) => {
        if (e.target.tagName === 'TEXTAREA') checkCursorContext(e.target);
    });

    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'TEXTAREA') checkCursorContext(e.target);
    });

    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            clearTimeout(throttleTimer);
            throttleTimer = setTimeout(() => {
                simplify(e.target);
                checkCursorContext(e.target);
            }, 500);
        }
    });
})();
