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
define(['jquery', 'core/ajax', 'core/log'], function($, ajax, log) {

    var sendRequest = async function(page, param) {

        log.debug('IntelliData Request', [page, param]);

        var useragent = '';
        if (navigator.userAgent && navigator.userAgentData) {
            useragent = navigator.userAgent;
            try {
                const ua = await navigator.userAgentData.getHighEntropyValues(['platform', 'platformVersion']);
                const windowsIndex = useragent.indexOf('Windows');
                if ((ua.platform == 'Windows') && (windowsIndex !== -1)) {
                    const semicolonIndex = useragent.indexOf(';', windowsIndex);
                    if (semicolonIndex !== -1) {
                        useragent = useragent.substring(0, windowsIndex) +
                            'Windows NT ' + ua.platformVersion + useragent.substring(semicolonIndex);
                    }
                }
            } catch (error) {
                log.debug('Error retrieving platform info: ' + error.message);
            }
        }

        ajax.call([{
            methodname: 'local_intelldata_save_tracking', args: {
                page: page,
                param: param,
                useragent: useragent
            }
        }])[0]
        .done(function(response) {
            log.debug('IntelliData: Request Inserted at ' + response.time);
        }).fail(function(ex) {
            log.debug('IntelliData: Request ERROR: ' + ex.message);
        });
    };

    return {
        sendRequest: sendRequest
    };
});
