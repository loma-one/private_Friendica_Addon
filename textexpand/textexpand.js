document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.textexpand-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const rnd = this.dataset.target;
            const full = document.getElementById('textexpand-full-' + rnd);
            const teaser = document.getElementById('textexpand-teaser-' + rnd);

            if (full.classList.contains('open')) {
                full.classList.remove('open');
                teaser.style.display = 'block';
            } else {
                full.classList.add('open');
                teaser.style.display = 'none';
            }
        });
    });
});
