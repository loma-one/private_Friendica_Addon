(() => {
    "use strict";

    const config = window.timelinefilterConfig;
    if (!config || (!config.words.length && !config.hashtags.length && !config.accounts.length)) return;

    const escapeRx = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const wordRx = config.words.map(w => new RegExp('(?<![\\p{L}\\p{N}])' + escapeRx(w) + '(?![\\p{L}\\p{N}])', 'ui'));
    const hashRx = config.hashtags.map(h => new RegExp('(?<![\\p{L}\\p{N}_])#' + escapeRx(h) + '(?![\\p{L}\\p{N}_])', 'ui'));

    const filterPost = post => {
        if (!post || post.nodeType !== 1 || post.dataset.tfFiltered) return;
        post.dataset.tfFiltered = "true";

        const text = post.textContent;

        if (wordRx.some(rx => rx.test(text)) || hashRx.some(rx => rx.test(text))) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        const links = Array.from(post.querySelectorAll("a[href]"));

        if (config.hashtags.length && links.some(a => {
            const href = a.getAttribute("href").toLowerCase();
            return config.hashtags.some(h => new RegExp('tag[/=]' + escapeRx(h) + '(?![\\p{L}\\p{N}_])', 'i').test(href));
        })) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        if (config.accounts.length) {
            const hasAccount = config.accounts.some(acc => {
                const accLower = acc.toLowerCase();

                if (text.toLowerCase().includes("@" + accLower)) return true;

                if (accLower.includes("@")) {
                    const [uName, uDom] = accLower.split("@");
                    return links.some(a => {
                        const href = a.getAttribute("href").toLowerCase();
                        return href.includes(uDom) && (
                            href.includes("/profile/" + uName) ||
                            href.includes("/users/" + uName) ||
                            href.includes("/@" + uName) ||
                            href.includes("/channel/" + uName)
                        );
                    });
                } else {
                    return links.some(a => {
                        const href = a.getAttribute("href").toLowerCase();
                        return href.includes("/profile/" + accLower) ||
                               href.includes("/users/" + accLower) ||
                               href.includes("/@" + accLower) ||
                               href.includes("/channel/" + accLower);
                    });
                }
            });

            if (hasAccount) {
                post.style.setProperty("display", "none", "important");
            }
        }
    };

    document.querySelectorAll(config.selector).forEach(filterPost);

    const target = document.getElementById("threads-location") || document.body;
    new MutationObserver(mutations => {
        for (const m of mutations) {
            m.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.matches?.(config.selector)) filterPost(node);
                else node.querySelectorAll?.(config.selector).forEach(filterPost);
            });
        }
    }).observe(target, { childList: true, subtree: true });
})();
