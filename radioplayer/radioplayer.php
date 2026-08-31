<?php
/**
 * Name: RadioPlayer
 * Description: Adds a persistent web radio player above the timeline.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;

function radioplayer_install()
{
    Hook::register('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
}

function radioplayer_uninstall()
{
    Hook::unregister('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
}

function radioplayer_page_header(&$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

$streams = [
        ['name' => 'Radio Paradise (Main)', 'url' => 'https://stream.radioparadise.com/aac-320'],
        ['name' => 'WDR2', 'url' => 'https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3'],
        ['name' => 'DLF Nova', 'url' => 'https://st03.dlf.de/dlf/03/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk', 'url' => 'https://st01.dlf.de/dlf/01/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk Kultur', 'url' => 'https://st02.dlf.de/dlf/02/128/mp3/stream.mp3'],
        ['name' => 'WDR 5', 'url' => 'https://wdr-wdr5-live.icecast.wdr.de/wdr/wdr5/live/mp3/128/stream.mp3'],
        ['name' => 'ORF Radio FM4', 'url' => 'https://orf-live.ors-shoutcast.at/fm4-q2a'],
        ['name' => '1LIVE', 'url' => 'https://wdr-1live-live.icecast.wdr.de/wdr/1live/live/mp3/128/stream.mp3'],
        ['name' => 'OE1', 'url' => 'https://orf-live.ors-shoutcast.at/oe1-q2a'],
        ['name' => 'SRF 3', 'url' => 'https://stream.srg-ssr.ch/m/srf3/mp3_128'],
        ['name' => 'Radio Paradise (Mellow Mix)', 'url' => 'https://stream.radioparadise.com/mellow-320'],
        ['name' => 'Radio Paradise (Rock Mix)', 'url' => 'https://stream.radioparadise.com/rock-320']
    ];

    $optionsHtml = '';
    foreach ($streams as $stream) {
        $optionsHtml .= '<option value="' . htmlspecialchars($stream['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($stream['name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }

    $rawPlay = DI::l10n()->t('Play');
    $rawStop = DI::l10n()->t('Stop');

    $lblPlay = htmlspecialchars($rawPlay, ENT_QUOTES, 'UTF-8');
    $lblStop = htmlspecialchars($rawStop, ENT_QUOTES, 'UTF-8');

    $jsonPlay = json_encode($rawPlay);
    $jsonStop = json_encode($rawStop);
    $jsonSelectError = json_encode(DI::l10n()->t('Please select a valid stream.'));
    $jsonLoading = json_encode(DI::l10n()->t('Loading stream...'));
    $jsonNewSender = json_encode(DI::l10n()->t('Loading new station...'));
    $jsonPlayError = json_encode(DI::l10n()->t('Playback blocked by browser.'));
    $jsonLoadError = json_encode(DI::l10n()->t('Error loading the new station.'));
    $jsonConnError = json_encode(DI::l10n()->t('Connection to stream server lost.'));

    $b .= <<<HTML
    <div id="friendica-radio-player" class="panel panel-default" style="margin-bottom: 15px;">
        <div class="panel-body" style="padding: 10px 15px;">
            <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">

                <div class="col-xs-12 col-sm-4" style="margin-bottom: 5px;">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="ri-radio-2-line" aria-hidden="true"></i></span>
                        <select id="radio-stream-select" class="form-control">
                            {$optionsHtml}
                        </select>
                    </div>
                </div>

                <div class="col-xs-6 col-sm-4 text-center" style="margin-bottom: 5px;">
                    <button id="radio-play-btn" class="btn btn-success btn-block" type="button">
                        <i id="radio-play-icon" class="ri-play-fill" aria-hidden="true"></i>
                        <span id="radio-play-text">{$lblPlay}</span>
                    </button>
                </div>

                <div class="col-xs-6 col-sm-4" style="margin-bottom: 5px;">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 4px 8px;">
                        <i class="ri-volume-up-line" style="font-size: 1.2em; opacity: 0.7;" aria-hidden="true"></i>
                        <input type="range" id="radio-volume" min="0" max="1" step="0.05" value="0.8" style="width: 100%; cursor: pointer;">
                    </div>
                </div>

            </div>

            <div id="radio-status-msg" class="text-danger small" style="margin-top: 5px; display: none;"></div>
        </div>
    </div>

    <script>
    (function() {
        window.RadioPlayer = window.RadioPlayer || {};

        var txtPlay = {$jsonPlay};
        var txtStop = {$jsonStop};
        var txtSelectError = {$jsonSelectError};
        var txtLoading = {$jsonLoading};
        var txtNewSender = {$jsonNewSender};
        var txtPlayError = {$jsonPlayError};
        var txtLoadError = {$jsonLoadError};
        var txtConnError = {$jsonConnError};

        var audio = document.getElementById("global-radio-audio");
        if (!audio) {
            audio = document.createElement("audio");
            audio.id = "global-radio-audio";
            audio.style.display = "none";
            audio.preload = "none";
            audio.setAttribute("referrerpolicy", "no-referrer");
            document.body.appendChild(audio);
        }

        var playBtn = document.getElementById("radio-play-btn");
        var playIcon = document.getElementById("radio-play-icon");
        var playText = document.getElementById("radio-play-text");
        var streamSelect = document.getElementById("radio-stream-select");
        var volumeSlider = document.getElementById("radio-volume");
        var statusMsg = document.getElementById("radio-status-msg");

        if (!playBtn || !streamSelect) {
            return;
        }

        var savedStream = localStorage.getItem("radioplayer_stream");
        if (savedStream) {
            streamSelect.value = savedStream;
        }

        var savedVolume = localStorage.getItem("radioplayer_volume");
        if (savedVolume !== null) {
            volumeSlider.value = savedVolume;
            audio.volume = savedVolume;
        }

        function updatePlayState(isPlaying) {
            if (isPlaying) {
                playBtn.className = "btn btn-danger btn-block";
                playIcon.className = "ri-stop-fill";
                playText.textContent = " " + txtStop;
            } else {
                playBtn.className = "btn btn-success btn-block";
                playIcon.className = "ri-play-fill";
                playText.textContent = " " + txtPlay;
            }
        }

        function showStatus(text) {
            if (text) {
                statusMsg.textContent = text;
                statusMsg.style.display = "block";
            } else {
                statusMsg.style.display = "none";
            }
        }

        updatePlayState(!audio.paused && audio.src);

        playBtn.addEventListener("click", function() {
            if (audio.paused) {
                var targetUrl = streamSelect.value;

                if (!targetUrl) {
                    showStatus(txtSelectError);
                    return;
                }

                window.RadioPlayer.stopping = false;
                showStatus(txtLoading);

                var onCanPlay = function() {
                    audio.removeEventListener("canplay", onCanPlay);

                    var playPromise = audio.play();
                    if (playPromise !== undefined) {
                        playPromise.then(function() {
                            updatePlayState(true);
                            showStatus("");
                        }).catch(function(error) {
                            updatePlayState(false);
                            showStatus(txtPlayError);
                        });
                    }
                };

                audio.addEventListener("canplay", onCanPlay);
                audio.src = targetUrl;
                audio.load();

            } else {
                window.RadioPlayer.stopping = true;
                audio.pause();
                audio.removeAttribute("src");
                audio.load();
                updatePlayState(false);
                showStatus("");
            }
        });

        streamSelect.addEventListener("change", function() {
            localStorage.setItem("radioplayer_stream", this.value);

            var wasPlaying = !audio.paused;
            window.RadioPlayer.stopping = true;

            audio.pause();
            audio.src = this.value;
            audio.load();

            if (wasPlaying) {
                window.RadioPlayer.stopping = false;
                showStatus(txtNewSender);

                var startPlayback = function() {
                    audio.removeEventListener("canplay", startPlayback);
                    audio.removeEventListener("playing", startPlayback);

                    audio.play().then(function() {
                        updatePlayState(true);
                        showStatus("");
                    }).catch(function() {
                        updatePlayState(false);
                        showStatus(txtLoadError);
                    });
                };

                audio.addEventListener("canplay", startPlayback);
                audio.addEventListener("playing", startPlayback);
            } else {
                showStatus("");
            }
        });

        volumeSlider.addEventListener("input", function() {
            audio.volume = this.value;
            localStorage.setItem("radioplayer_volume", this.value);
        });

        if (!audio.dataset.hasErrorListener) {
            audio.addEventListener("error", function() {
                if (window.RadioPlayer.stopping || !audio.src || audio.src === window.location.href || audio.networkState === 3) {
                    return;
                }
                var currentPlayBtn = document.getElementById("radio-play-btn");
                var currentStatusMsg = document.getElementById("radio-status-msg");
                if (currentPlayBtn) {
                    currentPlayBtn.className = "btn btn-success btn-block";
                    var icon = document.getElementById("radio-play-icon");
                    var text = document.getElementById("radio-play-text");
                    if (icon) icon.className = "ri-play-fill";
                    if (text) text.textContent = " " + txtPlay;
                }
                if (currentStatusMsg) {
                    currentStatusMsg.textContent = txtConnError;
                    currentStatusMsg.style.display = "block";
                }
            });
            audio.dataset.hasErrorListener = "true";
        }
    })();
    </script>
HTML;
}
