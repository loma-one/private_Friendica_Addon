(function () {
	'use strict';

	var FREEZE_CLASS = 'gifhover-freeze';
	var APNG_CLASS = 'gifhover-apng';
	var WRAPPER_CLASS = 'gifhover-wrap';
	var CANVAS_CLASS = 'gifhover-canvas';
	var HIDDEN_CLASS = 'gifhover-hidden';
	var PLAYING_CLASS = 'gifhover-playing';
	var ALLOCATED_CLASS = 'gifhover-allocated';

	function isLoaded(img) {
		return img.complete && img.naturalWidth > 0;
	}

	function hasHoverCapability() {
		return window.matchMedia && window.matchMedia('(hover: hover)').matches;
	}

	function setPlaying(img, playing) {
		var wrap = img.closest('.' + WRAPPER_CLASS);
		if (!wrap) {
			return;
		}
		img.classList.toggle(HIDDEN_CLASS, !playing);
		wrap.classList.toggle(PLAYING_CLASS, playing);
	}

	function freeze(img) {
		if (img.dataset.gifhover === 'done' || img.dataset.gifhover === 'pending') {
			return;
		}
		img.dataset.gifhover = 'pending';

		var start = function () {
			if (!img.parentNode) {
				delete img.dataset.gifhover;
				return;
			}

			var canvas = document.createElement('canvas');
			canvas.className = CANVAS_CLASS;
			canvas.setAttribute('aria-label', img.alt || img.title || '');

			try {
				canvas.width = img.naturalWidth;
				canvas.height = img.naturalHeight;
				canvas.getContext('2d').drawImage(img, 0, 0);
			} catch (e) {
				delete img.dataset.gifhover;
				return;
			}

			var wrap = document.createElement('span');
			wrap.className = WRAPPER_CLASS;

			if (img.closest('figure.img-allocated-height')) {
				wrap.classList.add(ALLOCATED_CLASS);
			}

			img.parentNode.insertBefore(wrap, img);
			wrap.appendChild(img);
			wrap.appendChild(canvas);

			img.classList.add(HIDDEN_CLASS);
			img.dataset.gifhover = 'done';

			wrap.addEventListener('mouseenter', function () {
				setPlaying(img, true);
			});
			wrap.addEventListener('mouseleave', function () {
				setPlaying(img, false);
			});
		};

		if (isLoaded(img)) {
			start();
		} else {
			img.addEventListener('load', function () {
				start();
			}, { once: true });
		}
	}

	function apngState(bytes) {
		var signature = [0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a];
		for (var i = 0; i < 8; i++) {
			if (bytes[i] !== signature[i]) {
				return false;
			}
		}

		var offset = 8;
		while (offset + 8 <= bytes.length) {
			var type = String.fromCharCode(bytes[offset + 4], bytes[offset + 5], bytes[offset + 6], bytes[offset + 7]);
			if (type === 'acTL') {
				return true;
			}
			if (type === 'IDAT') {
				return false;
			}
			var length = (bytes[offset] << 24) | (bytes[offset + 1] << 16) | (bytes[offset + 2] << 8) | bytes[offset + 3];
			offset += 12 + length;
		}
		return null;
	}

	async function isAnimatedPng(img) {
		try {
			var response = await fetch(img.currentSrc || img.src, { headers: { Range: 'bytes=0-65535' } });
			var reader = response.body.getReader();
			var buffer = new Uint8Array(0);
			while (true) {
				var result = await reader.read();
				if (result.done) {
					return apngState(buffer) === true;
				}
				var chunk = result.value;
				var merged = new Uint8Array(buffer.length + chunk.length);
				merged.set(buffer);
				merged.set(chunk, buffer.length);
				buffer = merged;
				var state = apngState(buffer);
				if (state !== null) {
					reader.cancel();
					return state;
				}
				if (buffer.length > 262144) {
					reader.cancel();
					return false;
				}
			}
		} catch (e) {
			return false;
		}
	}

	async function checkApng(img) {
		if (img.dataset.gifhoverApng === 'done') {
			return;
		}
		img.dataset.gifhoverApng = 'done';

		if (await isAnimatedPng(img)) {
			img.classList.add(FREEZE_CLASS);
			freeze(img);
		} else {
			img.classList.remove(APNG_CLASS);
		}
	}

	function scan(root) {
		if (!hasHoverCapability()) {
			return;
		}
		var scope = root || document.body;
		scope.querySelectorAll('img.' + FREEZE_CLASS).forEach(freeze);
		scope.querySelectorAll('img.' + APNG_CLASS).forEach(checkApng);
	}

	function init() {
		scan();

		document.addEventListener('postprocess_liveupdate', function () {
			scan();
		});

		if ('MutationObserver' in window) {
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					for (var i = 0; i < mutation.addedNodes.length; i++) {
						var node = mutation.addedNodes[i];
						if (node.nodeType !== Node.ELEMENT_NODE) {
							continue;
						}
						if (node.matches && node.matches('img.' + FREEZE_CLASS + ', img.' + APNG_CLASS)) {
							scan(node.parentNode);
						} else {
							scan(node);
						}
					}
				});
			});
			observer.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
