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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    https://intelliboard.net/
 */
define([
        'jquery',
        'core/log',
        'local_intellidata/tracking_repository',
        'local_intellidata/cookies_helper'
    ],
    function(
        $,
        log,
        TrackingRepository,
        CookiesHelper
    ) {

        var PARAMS = {
            AJAXFREQUENCY: 30,
            INACTIVITY: 60,
            PERIOD: 1000,
            INTERVAL: null,
            PAGE: '',
            PARAM: '',
            MEDIATRACK: 0,
        };

        var TRACKING = {
            COUNTER: 0,
            AJAXCOUNTER: 0,
            WARNINGTIME: 0,
            LOGOUTTIME: 0
        };

        var registerParams = function(params) {
            PARAMS.INACTIVITY = params.inactivity || PARAMS.INACTIVITY;
            PARAMS.AJAXFREQUENCY = params.ajaxfrequency || PARAMS.AJAXFREQUENCY;
            PARAMS.PERIOD = params.period || PARAMS.PERIOD;
            PARAMS.PAGE = params.page || PARAMS.PAGE;
            PARAMS.PARAM = params.param || PARAMS.PARAM;
            PARAMS.MEDIATRACK = params.mediatrack || PARAMS.MEDIATRACK;
            log.debug('IntelliData: Set Params', [PARAMS, TRACKING]);
        };

        var registerEventListeners = function() {
            $(document).on("mousemove", clearCounter);
            $(document).on("keypress", clearCounter);
            $(document).on("scroll", clearCounter);
            $(window).on("unload", resetParams);
        };

        var clearCounter = function() {
            TRACKING.COUNTER = 0;
            TRACKING.WARNINGTIME = 0;
            TRACKING.LOGOUTTIME = 0;
        };

        var resetParams = function() {
            CookiesHelper.setCookie('intellidatapage', PARAMS.PAGE);
            CookiesHelper.setCookie('intellidataparam', PARAMS.PARAM);
            CookiesHelper.setCookie('intellidatatime', PARAMS.AJAXFREQUENCY);
        };

        var initTracking = function() {
            setInterval(track, PARAMS.PERIOD);
            log.debug('IntelliData: Start Tracking', [PARAMS, TRACKING]);
        };

        var track = function() {
            if (PARAMS.MEDIATRACK) {
                var status = mediaTracking();
                if (status && !document.hidden) {
                    clearCounter();
                }
            }
            if (TRACKING.COUNTER <= PARAMS.INACTIVITY) {
                TRACKING.COUNTER++;
                TRACKING.AJAXCOUNTER++;

                if (TRACKING.AJAXCOUNTER == PARAMS.AJAXFREQUENCY && PARAMS.AJAXFREQUENCY) {
                    TrackingRepository.sendRequest(PARAMS.PAGE, PARAMS.PARAM);
                    TRACKING.AJAXCOUNTER = 0;
                }
            }
        };

        var mediaTracking = function(){
            var media = [];
            var status = false;

            // Track Video.js players by checking for vjs-playing class
            var videoJsPlayers = document.querySelectorAll('.video-js');
            if (videoJsPlayers.length) {
                videoJsPlayers.forEach(function(player) {
                    // Check if player has vjs-playing class (indicates it's currently playing)
                    var isPlaying = player.classList.contains('vjs-playing');
                    if (isPlaying) {
                        media.push({
                            paused: false,
                            element: player,
                            type: 'videojs'
                        });
                    }
                });
            }

            // Track regular HTML5 media elements
            var internal = document.querySelectorAll('audio,video');
            if (internal.length) {
                internal.forEach(function(element) {
                    // Skip Video.js elements as they're handled above
                    if (!element.closest('.video-js')) {
                        media.push(element);
                    }
                });
            }

            // Track iframe media elements (for cross-origin content)
            var frames = document.querySelectorAll('iframe');
            if (frames.length) {
                frames.forEach(function(frame) {
                    // Skip iframe if it's part of a Video.js player (already tracked above)
                    if (frame.closest('.video-js')) {
                        return;
                    }

                    try {
                        // Try to access same-origin iframe content
                        var elements = frame.contentWindow.document.querySelectorAll('audio,video');
                        if (elements.length) {
                            elements.forEach(function(element) {
                                media.push(element);
                            });
                        }
                    } catch (e) {
                        // Cross-origin iframe - use alternative tracking methods
                        log.debug('IntelliData: Cross-origin iframe detected, using alternative tracking', frame.src);

                        // Check if iframe contains known media services
                        var iframeInfo = analyzeIframeSource(frame);
                        if (iframeInfo.hasMedia) {
                            // For known media services, we can assume potential media activity
                            // when iframe is visible and user is active
                            var isVisible = isElementVisible(frame);
                            if (isVisible) {
                                // Add a virtual media element for tracking purposes
                                media.push({
                                    paused: false, // Assume playing if visible
                                    src: frame.src,
                                    type: iframeInfo.type
                                });
                            }
                        }
                    }
                });
            }

            // Check if any media is playing
            if (media.length) {
                media.forEach(function(element) {
                    if (!element.paused) {
                        status = true;
                    }
                });
            }

            return status;
        };

        // Helper function to analyze iframe source for known media services
        var analyzeIframeSource = function(iframe) {
            var src = iframe.src || '';
            if (src.includes('youtube.com') || src.includes('youtu.be')) {
                return { type: 'youtube', hasMedia: true };
            }

            if (src.includes('vimeo.com')) {
                return { type: 'vimeo', hasMedia: true };
            }

            return { type: 'unknown', hasMedia: false };
        };

        // Helper function to check if element is visible
        var isElementVisible = function(element) {
            var rect = element.getBoundingClientRect();
            var windowHeight = window.innerHeight || document.documentElement.clientHeight;
            var windowWidth = window.innerWidth || document.documentElement.clientWidth;

            return (
                rect.top < windowHeight &&
                rect.bottom > 0 &&
                rect.left < windowWidth &&
                rect.right > 0
            );
        };

        return {
            init: function(params) {
                registerParams(params);
                registerEventListeners();
                initTracking();
            }
        };
    });
