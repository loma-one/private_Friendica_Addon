(function() {
    'use strict';

    var isStopping = false;
    var currentAbortController = null;

    function cleanupPlayerUI() {
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }
    }

    window.RadioPlayerInit = function() {
        var container = document.getElementById('friendica-radio-player');
        if (!container) {
            cleanupPlayerUI();
            return;
        }

        var playBtn = document.getElementById('radio-play-btn');
        var streamSelect = document.getElementById('radio-stream-select');
        var volumeSlider = document.getElementById('radio-volume');
        var statusMsg = document.getElementById('radio-status-msg');
        var playIcon = document.getElementById('radio-play-icon');
        var playText = document.getElementById('radio-play-text');

        if (!playBtn) return;

         if (container.dataset.initialized === 'true') return;
        container.dataset.initialized = 'true';

        cleanupPlayerUI();
        currentAbortController = new AbortController();
        var signal = currentAbortController.signal;

        var audio = document.getElementById('global-radio-audio');
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'global-radio-audio';
            audio.style.display = 'none';
            audio.preload = 'none';
            audio.setAttribute('referrerpolicy', 'no-referrer');
            document.body.appendChild(audio);
        }

        var storedStream = localStorage.getItem('radioplayer_stream');
        if (storedStream && streamSelect) {
            streamSelect.value = storedStream;
        }

        var storedVolume = localStorage.getItem('radioplayer_volume');
        if (storedVolume !== null && volumeSlider) {
            volumeSlider.value = storedVolume;
            audio.volume = storedVolume;
        }

        function setPlayingState(playing) {
            if (!playBtn || !playIcon || !playText) return;
            playBtn.className = playing ? 'btn btn-danger btn-block' : 'btn btn-success btn-block';
            playIcon.className = playing ? 'ri-stop-fill' : 'ri-play-fill';
            playText.textContent = playing ? playBtn.dataset.labelStop : playBtn.dataset.labelPlay;
        }

        function setStatus(msg) {
            if (!statusMsg) return;
            statusMsg.textContent = msg || '';
            statusMsg.style.display = msg ? 'block' : 'none';
        }

        var isPlaying = !audio.paused && audio.src && audio.src !== '' && audio.src !== window.location.href;
        setPlayingState(isPlaying);

        playBtn.addEventListener('click', function() {
            var currentlyPlaying = !audio.paused && audio.src && audio.src !== '' && audio.src !== window.location.href;

            if (!currentlyPlaying) {
                var url = streamSelect.value;
                if (!url) {
                    setStatus(statusMsg.dataset.msgSelect);
                    return;
                }

                isStopping = false;
                setStatus(statusMsg.dataset.msgLoading);

                var handleCanPlay = function() {
                    audio.removeEventListener('canplay', handleCanPlay);
                    audio.play().then(function() {
                        setPlayingState(true);
                        setStatus();
                    }).catch(function() {
                        setPlayingState(false);
                        setStatus(statusMsg.dataset.msgPlayError);
                    });
                };

                audio.addEventListener('canplay', handleCanPlay);
                audio.src = url;
                audio.load();
            } else {
                isStopping = true;
                audio.pause();
                audio.removeAttribute('src');
                audio.load();
                setPlayingState(false);
                setStatus();
            }
        }, { signal: signal });

        if (streamSelect) {
            streamSelect.addEventListener('change', function() {
                localStorage.setItem('radioplayer_stream', this.value);
                var wasPlaying = !audio.paused && audio.src && audio.src !== '' && audio.src !== window.location.href;

                isStopping = true;
                audio.pause();
                audio.src = this.value;
                audio.load();

                if (wasPlaying) {
                    isStopping = false;
                    setStatus(statusMsg.dataset.msgNew);

                    var handleSwitch = function() {
                        audio.removeEventListener('canplay', handleSwitch);
                        audio.play().then(function() {
                            setPlayingState(true);
                            setStatus();
                        }).catch(function() {
                            setPlayingState(false);
                            setStatus(statusMsg.dataset.msgLoadError);
                        });
                    };

                    audio.addEventListener('canplay', handleSwitch);
                } else {
                    setStatus();
                }
            }, { signal: signal });
        }

        if (volumeSlider) {
            volumeSlider.addEventListener('input', function() {
                audio.volume = this.value;
                localStorage.setItem('radioplayer_volume', this.value);
            }, { signal: signal });
        }

        if (!audio.dataset.hasErrorListener) {
            audio.addEventListener('error', function() {
                if (isStopping || !audio.src || audio.src === window.location.href || audio.networkState === 3) {
                    return;
                }
                setPlayingState(false);
                setStatus(statusMsg ? statusMsg.dataset.msgConnError : '');
            });
            audio.dataset.hasErrorListener = 'true';
        }
    };

    var spaObserver = new MutationObserver(function() {
        if (document.getElementById('friendica-radio-player')) {
            window.RadioPlayerInit();
        } else {
            cleanupPlayerUI();
        }
    });

    function startSPAObserver() {
        var mainContent = document.getElementById('region_2') || document.body;
        spaObserver.observe(mainContent, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.RadioPlayerInit();
            startSPAObserver();
        });
    } else {
        window.RadioPlayerInit();
        startSPAObserver();
    }
})();
