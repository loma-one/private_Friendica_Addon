(function () {
	if (window.quicktagInitialized) {
		return;
	}
	window.quicktagInitialized = true;

	function relocate(wrapper)
	{
		if (wrapper.dataset.quicktagRelocated) {
			return;
		}

		if (!wrapper.closest('#profile-jot-plugin-wrapper') && !wrapper.closest('.jotplugins')) {
			return;
		}

		var attach = document.querySelector('#profile-jot-submit-wrapper #profile-attach');
		if (attach) {
			var li = attach.closest('li');
			if (li && li.parentNode) {
				var item = document.createElement('li');
				item.appendChild(wrapper);
				li.parentNode.insertBefore(item, li);
				wrapper.dataset.quicktagRelocated = '1';
			}
			return;
		}

		var bbAttach = document.querySelector('.btn-toolbar .bb-attach');
		if (bbAttach) {
			var group = bbAttach.closest('.btn-group') || bbAttach;
			if (group && group.parentNode) {
				group.parentNode.insertBefore(wrapper, group);
				wrapper.dataset.quicktagRelocated = '1';
			}
		}
	}

	function relocateAll()
	{
		document.querySelectorAll('.quicktag-wrapper').forEach(relocate);
	}

	function getPopover(button)
	{
		const wrapper = button.closest('.quicktag-wrapper');
		return wrapper ? wrapper.querySelector('.quicktag-popover') : null;
	}

	function closeAll()
	{
		document.querySelectorAll('.quicktag-popover').forEach(function (popover) {
			popover.hidden = true;
			popover.closest('.quicktag-wrapper')
				.querySelector('#quicktag-toggle-btn')
				.setAttribute('aria-expanded', 'false');
		});
	}

	function insertQuickTag(tag)
	{
		let textarea = document.getElementById('profile-jot-text');

		if (!textarea) {
			textarea = document.querySelector('textarea.comment-edit-text');
		}

		if (!textarea) {
			return;
		}

		const currentText = textarea.value;
		const cursorPos = textarea.selectionStart ?? currentText.length;

		const prefix = (cursorPos > 0 && currentText[cursorPos - 1] !== ' ') ? ' ' : '';
		const insertion = prefix + tag + ' ';

		textarea.value = currentText.substring(0, cursorPos) + insertion + currentText.substring(cursorPos);

		const newCursorPos = cursorPos + insertion.length;
		textarea.setSelectionRange(newCursorPos, newCursorPos);
		textarea.focus();
	}

	document.addEventListener('click', function (e) {
		const toggleBtn = e.target.closest('#quicktag-toggle-btn');
		if (toggleBtn) {
			const popover = getPopover(toggleBtn);
			if (!popover) {
				return;
			}
			popover.hidden = !popover.hidden;
			toggleBtn.setAttribute('aria-expanded', String(!popover.hidden));
			return;
		}

		const chip = e.target.closest('.quicktag-chip');
		if (chip) {
			insertQuickTag(chip.dataset.tag);
			closeAll();
			return;
		}

		if (!e.target.closest('.quicktag-wrapper')) {
			closeAll();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			closeAll();
		}
	});

	relocateAll();
	if (typeof MutationObserver === 'function') {
		new MutationObserver(relocateAll).observe(document.body, {
			childList: true,
			subtree: true
		});
	}
})();
