(function () {
	if (window.quicktagInitialized) {
		return;
	}
	window.quicktagInitialized = true;

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
		const suffix = ' '; // Leerzeichen nach dem Hashtag für den folgenden Text

		const insertion = prefix + tag + suffix;

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
})();
