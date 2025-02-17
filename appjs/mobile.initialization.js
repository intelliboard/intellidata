var OTHERDATA = this.INIT_OTHERDATA;

(function() {

    var PAGE_URL = '';
    var PAGETYPES = {
        SITE: OTHERDATA.pagetype_site,
        COURSE: OTHERDATA.pagetype_course,
        MODULE: OTHERDATA.pagetype_module,
    };

    var PARAMS = {
        AJAXFREQUENCY: OTHERDATA.ajaxfrequency,
        INACTIVITY: OTHERDATA.inactivity,
        INTERVAL: OTHERDATA.interval,
        PERIOD: 1000,
        PAGE: '',
        PARAM: '',
        MEDIATRACK: OTHERDATA.mediatrack,
    };

    var TRACKING = {
        COUNTER: 0,
        AJAXCOUNTER: 0,
        WARNINGTIME: 0,
        LOGOUTTIME: 0,
        LASTTIMETRACK: 0,
    };

    function clearCounter() {
        TRACKING.COUNTER = 0;
        TRACKING.WARNINGTIME = 0;
        TRACKING.LOGOUTTIME = 0;
    }

    function registerEvents() {
        document.addEventListener('touchstart', clearCounter);
        document.addEventListener('touchmove', clearCounter);

        console.log('Events (touchstart, touchmove) registered...');
    }

    function mediaTracking() {
        var media = [];
        var status = false;
        var internal = document.querySelectorAll('audio,video');
        var frames = document.querySelectorAll('iframe');
        if (frames.length) {
            frames.forEach(function(frame) {
                var elements = frame.contentWindow.document.querySelectorAll('audio,video');
                if (elements.length) {
                    elements.forEach(function(element) {
                        media.push(element);
                    });
                }
            });
        }
        if (internal.length) {
            internal.forEach(function(element) {
                media.push(element);
            });
        }

        if (media.length) {
            media.forEach(function(element) {
                if (!element.paused) {
                    status = true;
                }
            });
        }

        return status;
    }

    // Sending tracking AJAX request via fetch.
    function sendAjaxRequest(init) {
        console.log('Send ajax request ' + PARAMS.PAGE + ' ' + PARAMS.PARAM);
        if (!PARAMS.PAGE || !PARAMS.PARAM) {
            return;
        }

        fetch(OTHERDATA.api_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'wstoken': OTHERDATA.token,
                'wsfunction': 'local_intelldata_save_mobile_tracking',
                'moodlewsrestformat': 'json',
                'user': OTHERDATA.user_id,
                'page': PARAMS.PAGE,
                'param': PARAMS.PARAM,
                'time': TRACKING.LASTTIMETRACK,
                'init': init,
            })
        })
            .then(response => response.json())
            .then(data => {
                TRACKING.LASTTIMETRACK = data.time;
                console.log('Server response:', data);
            })
            .catch(error => console.error('Send ajax request error:', error));
    }

    // The launch of event registration methods is in the interval due to the fact
    // that during initialization we have document = null.
    var interval = setInterval(() => {
        if (document.body) {
            registerEvents();

            clearInterval(interval);
        }
    }, 100);

    setInterval(function() {
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

                sendAjaxRequest(0);

                TRACKING.AJAXCOUNTER = 0;
            }
        }
    }, PARAMS.PERIOD);

    // Checking the page for the change.
    setInterval(function() {
        var actualHref = this.location.toString();
        if (PAGE_URL != actualHref) {

            PAGE_URL = actualHref;

            PARAMS.PAGE = PAGETYPES.SITE;
            PARAMS.PARAM = 1;

            var coursesPattern = /courses\/([^\/]+)\/(\d+)(?:\/(\d+))?/;
            var match = actualHref.match(coursesPattern);
            if (match) {
                var pagetype = match[1];
                var courseId = match[2];
                var moduleId = match[3];

                if (pagetype === 'course') {
                    PARAMS.PAGE = PAGETYPES.COURSE;
                    PARAMS.PARAM = parseInt(courseId, 10);
                } else {
                    PARAMS.PAGE = PAGETYPES.MODULE;
                    PARAMS.PARAM = moduleId ? parseInt(moduleId, 10) : 1;
                }
            }

            clearCounter();

            TRACKING.LASTTIMETRACK = 0;

            sendAjaxRequest(1);
        }

    }, 100);
})();