(() => {
    'use strict';

    let menu = null;
    let timer = null;

    const getMenu = () => {
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'wiki-ac-menu';
            document.body.appendChild(menu);
        }
        return menu;
    };

    const hideMenu = () => {
        if (menu) {
            menu.style.display = 'none';
        }
    };

    document.addEventListener('input', (e) => {
        const el = e.target;
        if (!el.matches('#profile-jot-text, textarea[name="body"], .comment-box textarea')) {
            return;
        }

        const pos = el.selectionStart;
        const val = el.value;

        // BBCode Autocomplete: [w -> [wiki][/wiki]
        if (pos >= 2 && val.substring(pos - 2, pos).toLowerCase() === '[w') {
            if (val.substring(pos, pos + 11) !== 'iki][/wiki]') {
                el.setRangeText('wiki][/wiki]', pos - 1, pos, 'end');
                const targetPos = pos + 4;
                el.setSelectionRange(targetPos, targetPos);
                hideMenu();
                return;
            }
        }

        // Wikipedia-Suche ermitteln
        const pre = val.substring(0, pos);
        const post = val.substring(pos);

        const matchPre = pre.match(/\[wiki(?:=([a-z]{2,3}))?\]([^\[\]]*)$/i);
        const matchPost = post.match(/^([^\[\]]*?)\[\/wiki\]/i);

        if (!matchPre || !matchPost) {
            hideMenu();
            return;
        }

        const lang = matchPre[1] || 'de';
        const query = (matchPre[2] + matchPost[1]).trim();

        if (query.length < 2) {
            hideMenu();
            return;
        }

        const startPos = pos - matchPre[2].length;
        const endPos = pos + matchPost[1].length;

        clearTimeout(timer);
        timer = setTimeout(async () => {
            try {
                const res = await fetch(`https://${lang}.wikipedia.org/w/api.php?action=opensearch&search=${encodeURIComponent(query)}&limit=5&namespace=0&format=json&origin=*`);
                const data = await res.json();
                const items = data[1] || [];

                if (!items.length) {
                    hideMenu();
                    return;
                }

                const acMenu = getMenu();
                acMenu.replaceChildren();

                items.forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'wiki-ac-item';
                    row.textContent = item;

                    row.addEventListener('mousedown', (evt) => {
                        evt.preventDefault();
                        el.setRangeText(item, startPos, endPos, 'end');
                        el.focus();
                        hideMenu();
                    });

                    acMenu.appendChild(row);
                });

                const rect = el.getBoundingClientRect();
                acMenu.style.top = `${rect.bottom + window.scrollY}px`;
                acMenu.style.left = `${rect.left + window.scrollX}px`;
                acMenu.style.minWidth = `${Math.max(rect.width * 0.4, 180)}px`;
                acMenu.style.display = 'block';
            } catch {
                hideMenu();
            }
        }, 200);
    });

    document.addEventListener('blur', (e) => {
        if (e.target.tagName === 'TEXTAREA') {
            setTimeout(hideMenu, 150);
        }
    }, true);
})();
