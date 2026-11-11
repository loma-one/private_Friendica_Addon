function usernoticeIsColumnView()
{
    if (new URLSearchParams(window.location.search).get('mode') === 'column') {
        return true;
    }

    return document.body.classList.contains('column-view');
}

function usernoticeInit()
{
    var modals = document.querySelectorAll('.usernotice-modal');
    if (modals.length === 0) {
        return;
    }

    if (usernoticeIsColumnView()) {
        for (var d = 0; d < modals.length; d++) {
            modals[d].parentNode.removeChild(modals[d]);
        }
        return;
    }

    for (var r = 1; r < modals.length; r++) {
        modals[r].parentNode.removeChild(modals[r]);
    }

    var modal = modals[0];
    if (modal.dataset.usernoticeInit === '1') {
        return;
    }
    modal.dataset.usernoticeInit = '1';

    var currentText = modal.getAttribute('data-notice-text') || '';

    if (!localStorage.getItem('usernoticeClosed') || localStorage.getItem('usernoticeText') !== currentText) {
        modal.style.display = 'block';
    }

    var links = modal.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        links[i].setAttribute('target', '_blank');
        links[i].setAttribute('rel', 'noopener noreferrer');
    }

    var closeBtn = document.getElementById('usernotice-ok-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            localStorage.setItem('usernoticeClosed', 'true');
            localStorage.setItem('usernoticeText', currentText);
        });
    }
}

usernoticeInit();
document.addEventListener('DOMContentLoaded', usernoticeInit);
document.addEventListener('up:fragment:inserted', usernoticeInit);
document.addEventListener('postprocess_liveupdate', usernoticeInit);
