(() => {
    "use strict";

    const config = window.timelinefilterConfig;
    if (!config || (!config.words.length && !config.hashtags.length && !config.accounts.length)) {
        return;
    }

    const escapeRx = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const wordRegexes = config.words.map(w => new RegExp(`(?<![\\p{L}\\p{N}])${escapeRx(w)}(?![\\p{L}\\p{N}])`, 'ui'));
    const hashRegexes = config.hashtags.map(h => new RegExp(`(?<![\\p{L}\\p{N}_])#${escapeRx(h)}(?![\\p{L}\\p{N}_])`, 'ui'));

    const matchesAccount = (text, links, account) => {
        const accLower = account.toLowerCase();
        if (text.toLowerCase().includes("@" + accLower)) {
            return true;
        }

        const profilePaths = ["/profile/", "/users/", "/@", "/channel/"];

        if (accLower.includes("@")) {
            const [uName, uDom] = accLower.split("@");
            return links.some(a => {
                const href = (a.getAttribute("href") || "").toLowerCase();
                return href.includes(uDom) && profilePaths.some(p => href.includes(p + uName));
            });
        }

        return links.some(a => {
            const href = (a.getAttribute("href") || "").toLowerCase();
            return profilePaths.some(p => href.includes(p + accLower));
        });
    };

    const filterPost = post => {
        if (!post || post.nodeType !== 1 || post.dataset.tfFiltered) {
            return;
        }

        if (post.classList.contains('thread-wrapper') || post.classList.contains('wall-item-container')) {
            return;
        }

        post.dataset.tfFiltered = "true";

        const text = post.textContent;
        const links = Array.from(post.querySelectorAll("a[href]"));

        if (wordRegexes.some(rx => rx.test(text)) || hashRegexes.some(rx => rx.test(text))) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        if (config.hashtags.length && links.some(a => {
            const href = (a.getAttribute("href") || "").toLowerCase();
            return config.hashtags.some(h => new RegExp(`tag[/=]${escapeRx(h)}(?![\\p{L}\\p{N}_])`, 'i').test(href));
        })) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        if (config.accounts.length && config.accounts.some(acc => matchesAccount(text, links, acc))) {
            post.style.setProperty("display", "none", "important");
        }
    };

    document.querySelectorAll(config.selector).forEach(filterPost);

    const target = document.getElementById("threads-location") || document.body;
    new MutationObserver(mutations => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType !== 1) continue;
                if (node.matches?.(config.selector)) {
                    filterPost(node);
                }
                node.querySelectorAll?.(config.selector).forEach(filterPost);
            }
        }
    }).observe(target, { childList: true, subtree: true });
})();
