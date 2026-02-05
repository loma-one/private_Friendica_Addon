(function() {
    const monsterPattern = /\[url=(.*?)\]\[img=(.*?)\](.*?)\[\/img\]\[\/url\]/gi;

    const cleanupOldEntries = () => {
        const now = Date.now();
        const twelveHours = 12 * 60 * 60 * 1000;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith('qp_')) {
                try {
                    const data = JSON.parse(localStorage.getItem(key));
                    if (data && data.timestamp && (now - data.timestamp > twelveHours)) {
                        localStorage.removeItem(key);
                    }
                } catch (e) { localStorage.removeItem(key); }
            }
        }
    };

    const simplify = (text) => {
        if (!text) return text;
        return text.replace(monsterPattern, (match, urlPart, imgPart, existingDesc) => {
            const fileName = imgPart.split('/').pop();
            const storageKey = `qp_${fileName}`;

            localStorage.setItem(storageKey, JSON.stringify({
                url: urlPart,
                img: imgPart,
                timestamp: Date.now()
            }));

            let userDesc = existingDesc.trim() || "Bildbeschreibung";
            return `[img]${fileName}|${userDesc}[/img]`;
        });
    };

    const reconstruct = (text) => {
        if (!text) return text;
        return text.replace(/\[img\](.*?)\|(.*?)\[\/img\]/g, (match, fileName, desc) => {
            const data = localStorage.getItem(`qp_${fileName}`);
            if (data) {
                const parsed = JSON.parse(data);
                const finalDesc = (desc === "Bildbeschreibung") ? "" : desc;
                return `[url=${parsed.url}][img=${parsed.img}]${finalDesc}[/img][/url]`;
            }
            return match;
        });
    };

    const applySimplify = (textarea) => {
        const current = textarea.value;
        const simple = simplify(current);
        if (current !== simple) {
            // Speichert die Cursor-Position
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;

            textarea.value = simple;

            // Setzt den Cursor wieder an die ursprüngliche Stelle
            // (verhindert das Springen ans Textende)
            textarea.setSelectionRange(start, end);
        }
    };

    if (typeof jQuery !== 'undefined') {
        const originalVal = jQuery.fn.val;
        jQuery.fn.val = function(value) {
            if (arguments.length === 0 && this.is('textarea')) {
                return reconstruct(originalVal.call(this));
            }
            if (arguments.length > 0 && this.is('textarea')) {
                return originalVal.call(this, simplify(value));
            }
            return originalVal.apply(this, arguments);
        };
    }

    // Sofort-Reaktion bei Drag & Drop
    document.addEventListener('drop', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            setTimeout(() => applySimplify(e.target), 150);
        }
    }, true);

    // Sofort-Reaktion bei Button-Klicks und Tastendruck
    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            applySimplify(e.target);
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest(
            '#wall-submit-preview, #profile-jot-submit, #wall-submit-submit, #jot-submit, ' +
            '[id^="comment-edit-submit-"], [id^="comment-edit-preview-link-"]'
        );

        if (btn) {
            document.querySelectorAll('textarea').forEach(textarea => {
                textarea.value = reconstruct(textarea.value);
                if (btn.id.includes('preview')) {
                    setTimeout(() => applySimplify(textarea), 1000);
                }
            });
        }
    }, true);

    // Das Intervall läuft jetzt IMMER, auch wenn das Feld aktiv ist.
    // applySimplify sorgt dafür, dass der Cursor nicht springt.
    setInterval(() => {
        document.querySelectorAll('textarea').forEach(textarea => {
            applySimplify(textarea);
        });
    }, 1500);

    cleanupOldEntries();
})();
