function usernoticeInit()
{
    var modal = document.getElementById('usernotice-modal');
    if (!modal || modal.dataset.usernoticeInit === '1') {
        return;
    }
    modal.dataset.usernoticeInit = '1';

    var currentText = modal.getAttribute('data-notice-text') || '';

    if (!localStorage.getItem('usernoticeClosed') || localStorage.getItem('usernoticeText') !== currentText) {
        modal.style.display = 'block';
    }

    // Alle Links im Modal in neuem Tab öffnen
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
