// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Video player, progress tracker and reflection interaction controller.
 *
 * @module     mod_videoreflection/player
 * @package   mod_videoreflection
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function (Ajax, Notification) {
    const HEARTBEAT_MS = 5000;
    const POLL_MS = 1000;
    const MAX_SEGMENT_SECONDS = 20;

    /**
     * Loads the YouTube iframe API once per page.
     *
     * @returns {Promise<void>}
     */
    const loadYouTubeApi = () => {
        if (window.YT && window.YT.Player) {
            return Promise.resolve();
        }
        if (window.__videoreflectionYouTubePromise) {
            return window.__videoreflectionYouTubePromise;
        }
        window.__videoreflectionYouTubePromise = new Promise((resolve) => {
            const previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                if (typeof previous === 'function') {
                    previous();
                }
                resolve();
            };
            if (!document.querySelector('script[data-videoreflection-youtube]')) {
                const script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.async = true;
                script.dataset.videoreflectionYoutube = '1';
                document.head.appendChild(script);
            }
        });
        return window.__videoreflectionYouTubePromise;
    };

    /**
     * Loads the Vimeo player API once per page.
     *
     * @returns {Promise<void>}
     */
    const loadVimeoApi = () => {
        if (window.Vimeo && window.Vimeo.Player) {
            return Promise.resolve();
        }
        if (window.__videoreflectionVimeoPromise) {
            return window.__videoreflectionVimeoPromise;
        }
        window.__videoreflectionVimeoPromise = new Promise((resolve, reject) => {
            let script = document.querySelector('script[data-videoreflection-vimeo]');
            if (!script) {
                script = document.createElement('script');
                script.src = 'https://player.vimeo.com/api/player.js';
                script.async = true;
                script.dataset.videoreflectionVimeo = '1';
                document.head.appendChild(script);
            }
            script.addEventListener('load', () => resolve(), {once: true});
            script.addEventListener('error', reject, {once: true});
        });
        return window.__videoreflectionVimeoPromise;
    };

    /**
     * Creates an HTML5 video adapter.
     *
     * @param {HTMLElement} host Player host.
     * @param {Object} config Configuration.
     * @returns {Promise<Object>}
     */
    const createHtml5Adapter = (host, config) => {
        const video = document.createElement('video');
        video.controls = true;
        video.preload = 'metadata';
        video.playsInline = true;
        if (config.poster) {
            video.poster = config.poster;
        }
        video.src = config.url;
        host.replaceChildren(video);
        return new Promise((resolve, reject) => {
            const ready = () => resolve({
                getCurrentTime: () => Promise.resolve(video.currentTime || 0),
                getDuration: () => Promise.resolve(Number.isFinite(video.duration) ? video.duration : 0),
                getPlaybackRate: () => Promise.resolve(video.playbackRate || 1),
                isPlaying: () => Promise.resolve(!video.paused && !video.ended),
                seek: (position) => {
                    video.currentTime = Math.max(0, position);
                    return Promise.resolve();
                },
            });
            if (video.readyState >= 1) {
                ready();
            } else {
                video.addEventListener('loadedmetadata', ready, {once: true});
                video.addEventListener('error', () => reject(new Error('Unable to load video.')), {once: true});
            }
        });
    };

    /**
     * Creates a YouTube adapter.
     *
     * @param {HTMLElement} host Player host.
     * @param {Object} config Configuration.
     * @returns {Promise<Object>}
     */
    const createYouTubeAdapter = async (host, config) => {
        await loadYouTubeApi();
        const target = document.createElement('div');
        target.id = 'videoreflection-youtube-' + config.cmid;
        host.replaceChildren(target);
        return new Promise((resolve) => {
            let player;
            player = new window.YT.Player(target.id, {
                videoId: config.url,
                playerVars: {playsinline: 1, rel: 0},
                events: {
                    onReady: () => resolve({
                        getCurrentTime: () => Promise.resolve(player.getCurrentTime() || 0),
                        getDuration: () => Promise.resolve(player.getDuration() || 0),
                        getPlaybackRate: () => Promise.resolve(player.getPlaybackRate() || 1),
                        isPlaying: () => Promise.resolve(player.getPlayerState() === window.YT.PlayerState.PLAYING),
                        seek: (position) => {
                            player.seekTo(Math.max(0, position), true);
                            return Promise.resolve();
                        },
                    }),
                },
            });
        });
    };

    /**
     * Creates a Vimeo adapter.
     *
     * @param {HTMLElement} host Player host.
     * @param {Object} config Configuration.
     * @returns {Promise<Object>}
     */
    const createVimeoAdapter = async (host, config) => {
        await loadVimeoApi();
        host.replaceChildren();
        const player = new window.Vimeo.Player(host, {
            id: Number(config.url),
            responsive: true,
        });
        await player.ready();
        return {
            getCurrentTime: () => player.getCurrentTime(),
            getDuration: () => player.getDuration(),
            getPlaybackRate: () => player.getPlaybackRate().catch(() => 1),
            isPlaying: () => player.getPaused().then((paused) => !paused),
            seek: (position) => player.setCurrentTime(Math.max(0, position)).then(() => undefined),
        };
    };

    /**
     * Creates the source-specific player adapter.
     *
     * @param {HTMLElement} host Player host.
     * @param {Object} config Configuration.
     * @returns {Promise<Object>}
     */
    const createAdapter = (host, config) => {
        if (!config.url) {
            return Promise.reject(new Error('No video source is configured.'));
        }
        if (config.source === 'youtube') {
            return createYouTubeAdapter(host, config);
        }
        if (config.source === 'vimeo') {
            return createVimeoAdapter(host, config);
        }
        return createHtml5Adapter(host, config);
    };

    /**
     * Initialises player tracking and reflections.
     *
     * @param {Object} config Server configuration.
     * @returns {Promise<void>}
     */
    const init = async (config) => {
        const root = document.getElementById('videoreflection-root');
        const host = document.getElementById('videoreflection-player-host');
        if (!root || !host) {
            return;
        }

        let adapter;
        try {
            adapter = await createAdapter(host, config);
        } catch (error) {
            host.textContent = error.message;
            Notification.exception(error);
            return;
        }

        let duration = await adapter.getDuration();
        let maxAllowed = Math.max(Number(config.contiguousEnd) || 0, Number(config.lastPosition) || 0);
        let sequence = 0;
        let lastHeartbeatPosition = null;
        let lastHeartbeatAt = performance.now();
        let lastPollPosition = null;
        let lastPollAt = performance.now();
        let seekDetected = false;
        let wasPlaying = false;
        let selectedQuestion = 0;
        let selectedTime = null;
        let selectedPurpose = 'reflection';

        const progressBar = document.getElementById('videoreflection-progress-bar');
        const percentLabel = document.getElementById('videoreflection-percent');
        const countLabel = document.getElementById('videoreflection-count');
        const requiredLabel = document.getElementById('videoreflection-required');
        const completionLabel = document.getElementById('videoreflection-completion-label');
        const timeline = document.getElementById('videoreflection-timeline');
        const compose = document.getElementById('videoreflection-compose');
        const composeQuestion = document.getElementById('videoreflection-compose-question');
        const textInput = document.getElementById('videoreflection-text');
        const typeInput = document.getElementById('videoreflection-entrytype');

        /** Positions all timeline markers after duration becomes known. */
        const positionMarkers = () => {
            if (!duration || !timeline) {
                return;
            }
            timeline.querySelectorAll('[data-marker-time]').forEach((marker) => {
                const time = Number(marker.dataset.markerTime);
                if (Number.isFinite(time) && time >= 0) {
                    marker.style.left = Math.min(100, Math.max(0, time / duration * 100)) + '%';
                }
            });
        };
        positionMarkers();

        /** Updates status widgets with server-returned completion state. */
        const updateStatus = (result) => {
            if (typeof result.percent !== 'undefined') {
                const percent = Math.max(0, Math.min(100, Number(result.percent) || 0));
                if (progressBar) {
                    progressBar.style.width = percent + '%';
                    progressBar.parentElement?.setAttribute('aria-valuenow', String(percent));
                }
                if (percentLabel) {
                    percentLabel.textContent = percent.toFixed(1);
                }
            }
            if (typeof result.reflectioncount !== 'undefined' && countLabel) {
                countLabel.textContent = String(result.reflectioncount);
            }
            if (typeof result.requiredanswered !== 'undefined' && requiredLabel) {
                requiredLabel.textContent = result.requiredanswered + '/' + result.requiredtotal;
            }
            if (typeof result.complete !== 'undefined' && completionLabel) {
                completionLabel.textContent = result.complete ? config.strings.complete : config.strings.incomplete;
                completionLabel.classList.toggle('bg-success', Boolean(result.complete));
                completionLabel.classList.toggle('bg-secondary', !result.complete);
            }
            if (typeof result.contiguousend !== 'undefined') {
                maxAllowed = Math.max(maxAllowed, Number(result.contiguousend) || 0);
            }
        };

        /** Sends current position and optionally a newly watched segment. */
        const sendHeartbeat = async (forceNoSegment = false) => {
            const current = await adapter.getCurrentTime();
            duration = (await adapter.getDuration()) || duration;
            let start = -1;
            let end = -1;
            if (!forceNoSegment && !seekDetected && lastHeartbeatPosition !== null) {
                const delta = current - lastHeartbeatPosition;
                if (delta > 0 && delta <= MAX_SEGMENT_SECONDS) {
                    start = lastHeartbeatPosition;
                    end = current;
                }
            }
            sequence++;
            lastHeartbeatPosition = current;
            lastHeartbeatAt = performance.now();
            seekDetected = false;
            try {
                const result = await Ajax.call([{
                    methodname: 'mod_videoreflection_update_progress',
                    args: {
                        cmid: config.cmid,
                        position: current,
                        duration: duration || 0,
                        watchedstart: start,
                        watchedend: end,
                        sequence: sequence,
                    },
                }])[0];
                updateStatus(result);
            } catch (error) {
                Notification.exception(error);
            }
        };

        /** Restores a requested reflection moment or the previous position. */
        if (Number(config.initialPosition) >= 0) {
            await adapter.seek(Number(config.initialPosition));
        } else if (Number(config.lastPosition) > 2 && Number(config.resumeMode) !== 0) {
            let resume = Number(config.resumeMode) === 1;
            if (Number(config.resumeMode) === 2) {
                resume = window.confirm(config.strings.resumeConfirm);
            }
            if (resume) {
                await adapter.seek(Number(config.lastPosition));
            }
        }

        duration = (await adapter.getDuration()) || duration;
        positionMarkers();
        lastHeartbeatPosition = await adapter.getCurrentTime();
        lastPollPosition = lastHeartbeatPosition;
        lastPollAt = performance.now();

        /** Polls playback to detect legitimate continuous playback versus seeking. */
        window.setInterval(async () => {
            try {
                const now = performance.now();
                const current = await adapter.getCurrentTime();
                const currentDuration = await adapter.getDuration();
                const rate = Math.max(0.25, Number(await adapter.getPlaybackRate()) || 1);
                const playing = await adapter.isPlaying();
                if (currentDuration > 0) {
                    duration = currentDuration;
                    positionMarkers();
                }

                if (lastPollPosition !== null) {
                    const elapsed = Math.max(0.1, (now - lastPollAt) / 1000);
                    const delta = current - lastPollPosition;
                    const plausible = elapsed * rate + 2.5;
                    if (delta < -2 || delta > plausible) {
                        seekDetected = true;
                        if (!config.allowSeek && delta > plausible && current > maxAllowed + 1) {
                            await adapter.seek(maxAllowed);
                            lastPollPosition = maxAllowed;
                            lastHeartbeatPosition = maxAllowed;
                            lastPollAt = now;
                            return;
                        }
                    } else if (playing && delta >= -0.5) {
                        maxAllowed = Math.max(maxAllowed, current);
                    }
                }

                if (playing && !wasPlaying) {
                    lastHeartbeatPosition = current;
                    lastHeartbeatAt = now;
                    seekDetected = false;
                }
                if (playing && now - lastHeartbeatAt >= HEARTBEAT_MS) {
                    await sendHeartbeat(false);
                } else if (!playing && wasPlaying) {
                    await sendHeartbeat(false);
                }

                wasPlaying = playing;
                lastPollPosition = current;
                lastPollAt = now;
            } catch (error) {
                // Transient player API polling errors are ignored; the next poll can recover.
            }
        }, POLL_MS);

        /** Opens the reflection composer for a free reflection or configured prompt. */
        const openComposer = async (question = null) => {
            if (!compose || !textInput || !typeInput) {
                return;
            }
            selectedQuestion = question ? Number(question.id) : 0;
            selectedTime = question && Number(question.time) >= 0 ? Number(question.time) : null;
            selectedPurpose = question ? question.purpose : 'reflection';
            typeInput.value = selectedPurpose === 'doubt' ? 'doubt' : 'reflection';
            if (composeQuestion) {
                composeQuestion.hidden = !question;
                composeQuestion.textContent = question ? question.text : '';
            }
            compose.hidden = false;
            if (selectedTime !== null) {
                await adapter.seek(selectedTime);
            }
            textInput.focus();
        };

        /** Adds a marker for a newly created timed reflection. */
        const addMarker = (entry) => {
            if (!timeline || Number(entry.timepoint) < 0) {
                return;
            }
            const marker = document.createElement('button');
            marker.type = 'button';
            marker.className = 'videoreflection-marker videoreflection-marker-entry';
            marker.dataset.seekTime = String(entry.timepoint);
            marker.dataset.markerTime = String(entry.timepoint);
            marker.dataset.reflectionMarker = String(entry.id);
            marker.title = entry.timeformatted + ' — ' + entry.reflectiontext;
            timeline.appendChild(marker);
            positionMarkers();
        };

        /** Appends a newly created reflection using DOM nodes rather than HTML strings. */
        const appendOwnEntry = (entry) => {
            const list = document.getElementById('videoreflection-own-list');
            if (!list) {
                return;
            }
            document.getElementById('videoreflection-empty-own')?.remove();
            const article = document.createElement('article');
            article.className = 'videoreflection-entry';
            article.dataset.reflectionId = String(entry.id);

            const meta = document.createElement('div');
            meta.className = 'videoreflection-entry-meta';
            if (Number(entry.timepoint) >= 0) {
                const seek = document.createElement('button');
                seek.type = 'button';
                seek.className = 'btn btn-sm btn-link p-0';
                seek.dataset.seekTime = String(entry.timepoint);
                seek.textContent = entry.timeformatted;
                meta.appendChild(seek);
            } else {
                const general = document.createElement('span');
                general.textContent = entry.timeformatted;
                meta.appendChild(general);
            }
            const badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark';
            badge.textContent = entry.entrytype === 'doubt' ? config.strings.typeDoubt : config.strings.typeReflection;
            meta.appendChild(badge);
            article.appendChild(meta);

            const body = document.createElement('div');
            body.className = 'videoreflection-entry-text';
            body.textContent = entry.reflectiontext;
            article.appendChild(body);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger mt-2';
            remove.dataset.deleteReflection = String(entry.id);
            remove.textContent = config.strings.delete;
            article.appendChild(remove);
            list.appendChild(article);
            addMarker(entry);
        };

        document.getElementById('videoreflection-add-current')?.addEventListener('click', () => openComposer());
        document.getElementById('videoreflection-cancel')?.addEventListener('click', () => {
            if (compose) {
                compose.hidden = true;
            }
            if (textInput) {
                textInput.value = '';
            }
        });

        document.getElementById('videoreflection-save')?.addEventListener('click', async () => {
            if (!textInput || !typeInput) {
                return;
            }
            const text = textInput.value.trim();
            if (!text) {
                textInput.setCustomValidity(config.strings.reflectionRequired);
                textInput.reportValidity();
                textInput.setCustomValidity('');
                return;
            }
            const timepoint = selectedTime === null ? await adapter.getCurrentTime() : selectedTime;
            try {
                const result = await Ajax.call([{
                    methodname: 'mod_videoreflection_add_reflection',
                    args: {
                        cmid: config.cmid,
                        questionid: selectedQuestion,
                        timepoint: timepoint,
                        entrytype: typeInput.value,
                        reflectiontext: text,
                    },
                }])[0];
                appendOwnEntry(result);
                updateStatus(result);
                textInput.value = '';
                if (compose) {
                    compose.hidden = true;
                }
                selectedQuestion = 0;
                selectedTime = null;
                selectedPurpose = 'reflection';
            } catch (error) {
                Notification.exception(error);
            }
        });

        root.addEventListener('click', async (event) => {
            const seekTarget = event.target.closest('[data-seek-time]');
            if (seekTarget) {
                const time = Number(seekTarget.dataset.seekTime);
                if (Number.isFinite(time) && time >= 0) {
                    await adapter.seek(time);
                    seekDetected = true;
                    lastHeartbeatPosition = time;
                }
            }

            const answer = event.target.closest('[data-answer-question]');
            if (answer) {
                await openComposer({
                    id: answer.dataset.answerQuestion,
                    time: Number(answer.dataset.questionTime),
                    purpose: answer.dataset.questionPurpose,
                    text: answer.dataset.questionText,
                });
            }

            const remove = event.target.closest('[data-delete-reflection]');
            if (remove) {
                const reflectionId = Number(remove.dataset.deleteReflection);
                if (!window.confirm(config.strings.deleteConfirm)) {
                    return;
                }
                try {
                    const result = await Ajax.call([{
                        methodname: 'mod_videoreflection_delete_reflection',
                        args: {cmid: config.cmid, reflectionid: reflectionId},
                    }])[0];
                    root.querySelector('[data-reflection-id="' + reflectionId + '"]')?.remove();
                    timeline?.querySelector('[data-reflection-marker="' + reflectionId + '"]')?.remove();
                    updateStatus(result);
                } catch (error) {
                    Notification.exception(error);
                }
            }
        });
    };

    return {init: init};
});
