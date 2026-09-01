(function() {
    'use strict';

    window.RadioPlayer = window.RadioPlayer || {};

    window.RadioPlayerInit = function() {
        var container = document.getElementById('friendica-radio-player');
        if (!container) return;

        var playBtn = document.getElementById('radio-play-btn');
        var streamSelect = document.getElementById('radio-stream-select');
        var volumeSlider = document.getElementById('radio-volume');
        var statusMsg = document.getElementById('radio-status-msg');
        var playIcon = document.getElementById('radio-play-icon');
        var playText = document.getElementById('radio-play-text');

        if (!playBtn || playBtn.dataset.bound) return;
        playBtn.dataset.bound = 'true';

        // Audio Engine (Global Singleton)
        var audio = document.getElementById('global-radio-audio');
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'global-radio-audio';
            audio.style.display = 'none';
            audio.preload = 'none';
            audio.setAttribute('referrerpolicy', 'no-referrer');
            document.body.appendChild(audio);
        }

        // State & Storage
        var storedStream = localStorage.getItem('radioplayer_stream');
        if (storedStream) streamSelect.value = storedStream;

        var storedVolume = localStorage.getItem('radioplayer_volume');
        if (storedVolume !== null) {
            volumeSlider.value = storedVolume;
            audio.volume = storedVolume;
        }

        function setPlayingState(playing) {
            playBtn.className = playing ? 'btn btn-danger btn-block' : 'btn btn-success btn-block';
            playIcon.className = playing ? 'ri-stop-fill' : 'ri-play-fill';
            playText.textContent = ' ' + (playing ? playBtn.dataset.labelStop : playBtn.dataset.labelPlay);
        }

        function setStatus(msg) {
            statusMsg.textContent = msg || '';
            statusMsg.style.display = msg ? 'block' : 'none';
        }

        setPlayingState(!audio.paused && audio.src);

        // Play / Stop Controls
        playBtn.addEventListener('click', function() {
            if (audio.paused) {
                var url = streamSelect.value;
                if (!url) {
                    setStatus(statusMsg.dataset.msgSelect);
                    return;
                }

                window.RadioPlayer.stopping = false;
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
                window.RadioPlayer.stopping = true;
                audio.pause();
                audio.removeAttribute('src');
                audio.load();
                setPlayingState(false);
                setStatus();
            }
        });

        // Station Selector
        streamSelect.addEventListener('change', function() {
            localStorage.setItem('radioplayer_stream', this.value);
            var wasPlaying = !audio.paused;

            window.RadioPlayer.stopping = true;
            audio.pause();
            audio.src = this.value;
            audio.load();

            if (wasPlaying) {
                window.RadioPlayer.stopping = false;
                setStatus(statusMsg.dataset.msgNew);

                var handleSwitch = function() {
                    audio.removeEventListener('canplay', handleSwitch);
                    audio.removeEventListener('playing', handleSwitch);

                    audio.play().then(function() {
                        setPlayingState(true);
                        setStatus();
                    }).catch(function() {
                        setPlayingState(false);
                        setStatus(statusMsg.dataset.msgLoadError);
                    });
                };

                audio.addEventListener('canplay', handleSwitch);
                audio.addEventListener('playing', handleSwitch);
            } else {
                setStatus();
            }
        });

        // Volume Control
        volumeSlider.addEventListener('input', function() {
            audio.volume = this.value;
            localStorage.setItem('radioplayer_volume', this.value);
        });

        // Error Handling
        if (!audio.dataset.hasErrorListener) {
            audio.addEventListener('error', function() {
                if (window.RadioPlayer.stopping || !audio.src || audio.src === window.location.href || audio.networkState === 3) {
                    return;
                }
                setPlayingState(false);
                setStatus(statusMsg.dataset.msgConnError);
            });
            audio.dataset.hasErrorListener = 'true';
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.RadioPlayerInit);
    } else {
        window.RadioPlayerInit();
    }
})();
