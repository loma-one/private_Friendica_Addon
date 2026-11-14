<span class="quicktag-wrapper">
	<style>
		.quicktag-wrapper {
			position: relative;
			display: inline-block;
		}

		.quicktag-popover {
			top: auto;
			bottom: 100%;
			left: 0;
			margin: 0 0 5px 0;
			min-width: 200px;
			padding: 8px;
			display: flex;
			flex-direction: column;
		}

		.quicktag-popover[hidden] {
			display: none;
		}

		.quicktag-header {
			font-weight: bold;
			font-size: 12px;
			margin-bottom: 6px;
			border-bottom: 1px solid currentColor;
			padding-bottom: 3px;
			opacity: 0.6;
		}

		.quicktag-chips {
			display: flex;
			flex-wrap: wrap;
			gap: 4px;
		}

		.quicktag-chip {
			border-radius: 12px;
			margin: 0;
		}
	</style>

	<button type="button" id="quicktag-toggle-btn" class="btn btn-default btn-sm" title="{{$title}}" aria-haspopup="true" aria-expanded="false">
		<i class="ri-hashtag" aria-hidden="true"></i>
	</button>

	<div id="quicktag-popover" class="dropdown-menu quicktag-popover" hidden>
		<div class="quicktag-header">{{$title}}</div>
		<div class="quicktag-chips">
			{{foreach $tags as $tag}}
				<button type="button" class="btn btn-default btn-xs quicktag-chip" data-tag="{{$tag}}">{{$tag}}</button>
			{{/foreach}}
		</div>
	</div>

	<script>
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

			function insertQuickTag(tag, chip)
			{
				let textarea = null;

				const form = chip.closest('form');
				if (form) {
					textarea = form.querySelector('textarea[name="body"]');
				}

				if (!textarea) {
					textarea = document.getElementById('profile-jot-text');
				}

				if (!textarea) {
					textarea = document.querySelector('textarea.comment-edit-text');
				}

				if (!textarea) {
					return;
				}

				const currentText = textarea.value;
				const cursorPos = textarea.selectionStart ?? currentText.length;

				const prefix = (cursorPos > 0 && currentText[cursorPos - 1] !== ' ') ? ' ' : '';
				const suffix = ' ';

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
					insertQuickTag(chip.dataset.tag, chip);
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
	</script>
</span>
