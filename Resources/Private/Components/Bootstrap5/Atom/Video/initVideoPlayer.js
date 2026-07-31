/**
 * Custom video player for sk-video containers (data-js="videoPlayer").
 *
 * Replaces initVideoAutoplay. Features:
 *  - Autoplay via IntersectionObserver when sk-video-autoplay is present
 *  - Custom play/pause button (data-js="videoPlay")
 *  - Expand button (data-js="videoExpand"): pauses inline video, opens a <dialog>
 *    with the best available quality source, continuing from the same timestamp.
 *    On dialog close: inline video resumes from where the dialog was paused.
 *
 * CSS state classes on the container:
 *  is-loading  — buffering (spinner visible)
 *  is-playing  — video is currently playing (pause icon shown, controls hidden on hover)
 */

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const setPlayingState = (container, playing) => {
    container.classList.toggle('is-playing', playing);
};

/**
 * Starts playback, handling preload="none" videos.
 *
 * iOS Safari ignores <source> children entirely until load() is called —
 * play() on a video with readyState HAVE_NOTHING is a no-op there. So if the
 * video hasn't buffered any data yet, we switch to preload="auto", call
 * load() to make iOS pick up the sources, and play() once canplay fires.
 */
const playVideo = (container, videoElement) => {
    container.classList.add('is-loading');

    const finishLoading = () => container.classList.remove('is-loading');

    const attemptPlay = () => {
        videoElement.play().then(() => {
            finishLoading();
            setPlayingState(container, true);
        }).catch(finishLoading);
    };

    if (videoElement.readyState >= videoElement.HAVE_FUTURE_DATA) {
        attemptPlay();
        return;
    }

    videoElement.addEventListener('canplay', attemptPlay, { once: true });
    videoElement.addEventListener('error', finishLoading, { once: true });
    videoElement.preload = 'auto';
    videoElement.load();
};

const createExpandDialog = (bestSrc, bestSrcType, labelClose) => {
    const dialog = document.createElement('dialog');
    dialog.className = 'sk-video-dialog';
    dialog.innerHTML = `
        <div class="sk-video-dialog-inner">
            <button class="sk-video-dialog-close" type="button" aria-label="${labelClose}">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
            <video class="sk-video-dialog-element" controls playsinline>
                <source src="${bestSrc}" type="${bestSrcType}">
            </video>
        </div>
    `;
    document.body.appendChild(dialog);
    return dialog;
};

/**
 * License popup: click-to-toggle on .sk-video-license-btn.
 * Handled globally — also covers decorative videos (no data-js="videoPlayer").
 * Closes on outside click or ESC.
 */
const initLicensePopups = () => {
    document.querySelectorAll('.sk-video-license-btn').forEach((button) => {
        const wrapper = button.closest('.sk-video-license');
        if (!wrapper) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const isNowOpen = wrapper.classList.toggle('is-open');
            if (isNowOpen) {
                // Close any other open license popups
                document.querySelectorAll('.sk-video-license.is-open').forEach((other) => {
                    if (other !== wrapper) {
                        other.classList.remove('is-open');
                    }
                });
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.sk-video-license')) {
            document.querySelectorAll('.sk-video-license.is-open').forEach((wrapper) => {
                wrapper.classList.remove('is-open');
            });
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.sk-video-license.is-open').forEach((wrapper) => {
                wrapper.classList.remove('is-open');
            });
        }
    });
};

const initVideoPlayer = () => {
    initLicensePopups();

    const containers = document.querySelectorAll('[data-js="videoPlayer"]');
    if (!containers.length) {
        return;
    }

    // One shared IntersectionObserver for all autoplay containers
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const container = entry.target;
                const videoElement = container.querySelector('video');

                if (!videoElement || prefersReducedMotion()) {
                    observer.unobserve(container);
                    return;
                }

                playVideo(container, videoElement);

                observer.unobserve(container);
            });
        },
        { threshold: 0.25 }
    );

    containers.forEach((container) => {
        const videoElement = container.querySelector('video');
        if (!videoElement) {
            return;
        }

        const playBtn    = container.querySelector('[data-js="videoPlay"]');
        const muteBtn    = container.querySelector('[data-js="videoMute"]');
        const expandBtn  = container.querySelector('[data-js="videoExpand"]');
        const isAutoplay = container.classList.contains('sk-video-autoplay');
        const bestSrc    = container.dataset.bestSrc    || '';
        const bestSrcType = container.dataset.bestSrcType || '';

        // ----------------------------------------------------------------
        // Autoplay via IntersectionObserver
        // ----------------------------------------------------------------
        if (isAutoplay) {
            observer.observe(container);
        }

        // ----------------------------------------------------------------
        // Sync CSS state with native video events
        // ----------------------------------------------------------------
        videoElement.addEventListener('play',  () => setPlayingState(container, true));
        videoElement.addEventListener('pause', () => setPlayingState(container, false));
        videoElement.addEventListener('ended', () => setPlayingState(container, false));

        // ----------------------------------------------------------------
        // Play/pause toggle (shared logic used by button and container click)
        // ----------------------------------------------------------------
        const togglePlayPause = () => {
            if (videoElement.paused) {
                playVideo(container, videoElement);
            } else {
                videoElement.pause();
            }
        };

        if (playBtn) {
            playBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                togglePlayPause();
            });
        }

        // ----------------------------------------------------------------
        // Mute / unmute toggle (autoplay videos start muted)
        // ----------------------------------------------------------------
        if (muteBtn) {
            const labelMute = muteBtn.dataset.labelMute || 'Mute';
            const labelUnmute = muteBtn.dataset.labelUnmute || 'Unmute';

            const syncMuteState = () => {
                container.classList.toggle('is-muted', videoElement.muted);
                muteBtn.setAttribute('aria-label', videoElement.muted ? labelUnmute : labelMute);
            };
            syncMuteState();

            videoElement.addEventListener('volumechange', syncMuteState);

            muteBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                videoElement.muted = !videoElement.muted;
            });
        }

        // Container click = play/pause (WCAG 2.2.2 — Pause, Stop, Hide)
        // Only for autoplay videos; non-autoplay use the explicit play button.
        if (isAutoplay) {
            container.addEventListener('click', (event) => {
                if (event.target.closest('[data-js="videoExpand"]')) {
                    return;
                }
                togglePlayPause();
            });
        }

        // ----------------------------------------------------------------
        // Expand button: open best quality in a <dialog>, resume at same position
        // ----------------------------------------------------------------
        if (expandBtn && bestSrc) {
            expandBtn.addEventListener('click', (event) => {
                event.stopPropagation();

                const currentTime = videoElement.currentTime;
                videoElement.pause();

                const labelClose = expandBtn.dataset.labelClose || 'Close';
                const dialog = createExpandDialog(bestSrc, bestSrcType, labelClose);
                const dialogVideo = dialog.querySelector('video');

                dialog.showModal();

                // Seek to inline position and start playing
                dialogVideo.currentTime = currentTime;
                dialogVideo.play().catch(() => {});

                const closeDialog = () => {
                    const savedTime = dialogVideo.currentTime;
                    dialogVideo.pause();
                    dialog.close();
                    // Restore inline position; user decides whether to resume
                    videoElement.currentTime = savedTime;
                    dialog.remove();
                };

                dialog.querySelector('.sk-video-dialog-close')
                    .addEventListener('click', closeDialog);

                // Click on the backdrop (outside dialog-inner) closes the dialog
                dialog.addEventListener('click', (ev) => {
                    if (ev.target === dialog) {
                        closeDialog();
                    }
                });

                // ESC key closes the dialog (native behavior) — sync inline position
                dialog.addEventListener('close', () => {
                    if (dialog.isConnected) {
                        videoElement.currentTime = dialogVideo.currentTime;
                        dialog.remove();
                    }
                }, { once: true });
            });
        }
    });
};

export { initVideoPlayer };