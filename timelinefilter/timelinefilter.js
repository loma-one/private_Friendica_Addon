(() => {
    "use strict";

    const config = window.timelinefilterConfig;

    if (!config || (!(config.words?.length) && !(config.hashtags?.length) && !(config.accounts?.length))) {
        return;
    }

    const escapeRx = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const wordRx = (config.words || []).map(w => new RegExp('(?<![\\p{L}\\p{N}])' + escapeRx(w) + '(?![\\p{L}\\p{N}])', 'ui'));
    const hashRx = (config.hashtags || []).map(h => new RegExp('(?<![\\p{L}\\p{N}_])#' + escapeRx(h) + '(?![\\p{L}\\p{N}_])', 'ui'));

    const filterPost = post => {
        if (!post || post.nodeType !== 1 || post.dataset.tfFiltered) return;
        post.dataset.tfFiltered = "true";

        const text = post.textContent || "";
        const links = Array.from(post.querySelectorAll("a[href]") || []);

        let isAccountMatch = false;
        if (config.accounts?.length) {
            const authorLink = post.querySelector(".wall-item-name-link, .profile-link, .author-name a");

            if (authorLink) {
                const href = (authorLink.getAttribute("href") || "").toLowerCase();
                const title = (authorLink.getAttribute("title") || "").toLowerCase();
                const tContent = (authorLink.textContent || "").toLowerCase();

                isAccountMatch = config.accounts.some(acc => {
                    const clean = acc.toLowerCase().replace(/^@/, '');
                    if (clean.includes("@")) {
                        const [name, dom] = clean.split("@");
                        return (title.includes(dom) && title.includes(name)) ||
                               (href.includes(dom) && href.includes(name)) ||
                               tContent.includes(name);
                    }
                    return href.includes(clean) || title.includes(clean) || tContent.includes(clean);
                });
            }

            if (!isAccountMatch) {
                isAccountMatch = config.accounts.some(acc => {
                    const lower = acc.toLowerCase();
                    if (text.toLowerCase().includes("@" + lower)) return true;

                    const [name, dom] = lower.includes("@") ? lower.split("@") : [lower, null];
                    return links.some(a => {
                        const h = (a.getAttribute("href") || "").toLowerCase();
                        if (dom) {
                            return h.includes(dom) && (h.includes("/profile/" + name) || h.includes("/users/" + name) || h.includes("/@" + name) || h.includes("/channel/" + name));
                        }
                        return h.includes("/profile/" + name) || h.includes("/users/" + name) || h.includes("/@" + name) || h.includes("/channel/" + name);
                    });
                });
            }
        }

        const isWordMatch = wordRx.some(rx => rx.test(text));
        const isHashMatch = hashRx.some(rx => rx.test(text)) ||
            (config.hashtags?.length && links.some(a => {
                const href = (a.getAttribute("href") || "").toLowerCase();
                return config.hashtags.some(h => new RegExp('tag[/=]' + escapeRx(h) + '(?![\\p{L}\\p{N}_])', 'i').test(href));
            }));

        if (!(isAccountMatch || isWordMatch || isHashMatch)) return;

        const isComment = post.classList.contains("wall-item-comment") || post.closest(".wall-item-comment");

        if (isComment) {
            post.style.setProperty("display", "none", "important");
            const wrapper = post.closest(".wall-item-comment");
            if (wrapper) wrapper.style.setProperty("display", "none", "important");
        } else {
            const threadContainer = post.closest(".wall-item-con, .thread-wrapper, .conversation") || post.closest(".wall-item") || post;
            threadContainer.style.setProperty("display", "none", "important");

            threadContainer.querySelectorAll(".wall-item-comment, .comment-fake-form").forEach(el => {
                el.style.setProperty("display", "none", "important");
            });

            let sibling = threadContainer.nextElementSibling;
            while (sibling) {
                if (sibling.nodeType === 1) {
                    if (sibling.matches(".wall-item, .wall-item-con, article.media") && !sibling.classList.contains("wall-item-comment") && !sibling.classList.contains("comment-fake-form")) {
                        break;
                    }
                    if (sibling.matches(".wall-item-comment, .comment-fake-form") || sibling.classList.contains("wall-item-comment") || sibling.classList.contains("comment-fake-form") || sibling.querySelector?.(".wall-item-comment, .comment-fake-form")) {
                        sibling.style.setProperty("display", "none", "important");
                    }
                }
                sibling = sibling.nextElementSibling;
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
