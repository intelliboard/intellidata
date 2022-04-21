<?php
// This file is part of the Local plans plugin
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
 * This plugin sends users a plans message after logging in
 * and notify a moderator a new user has been added
 * it has a settings page that allow you to configure the messages
 * send.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->locate('localplugins') and $ADMIN->locate('root')) {


    $settings = new admin_settingpage('local_intellidata', get_string('general', 'local_intellidata'));

    $ADMIN->add('localplugins', new admin_category('intellidata', get_string('pluginname', 'local_intellidata')));
    $ADMIN->add('intellidata', $settings);

    // General settings.
    $name = 'local_intellidata/enabled';
    $title = get_string('enabled', 'local_intellidata');
    $setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
    $settings->add($setting);

    $options = [
        0 => get_string('file', 'local_intellidata'),
        1 => get_string('database', 'local_intellidata'),
    ];
    $name = 'local_intellidata/trackingstorage';
    $title = get_string('trackingstorage', 'local_intellidata');
    $setting = new admin_setting_configselect($name, $title, '', 0, $options);
    $settings->add($setting);

    $name = 'local_intellidata/encryptionkey';
    $title = get_string('encryptionkey', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configpasswordunmask($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $name = 'local_intellidata/clientidentifier';
    $title = get_string('clientidentifier', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configpasswordunmask($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $name = 'local_intellidata/cleaner_duration';
    $title = get_string('cleaner_duration', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configduration($name, $title, $description, 0, 86400);
    $settings->add($setting);

    $name = 'local_intellidata/migrationrecordslimit';
    $title = get_string('migrationrecordslimit', 'local_intellidata');
    $description = get_string('migrationrecordslimit_desc', 'local_intellidata');
    $setting = new admin_setting_configtext($name, $title, $description, '1000000');
    $settings->add($setting);

    $name = 'local_intellidata/migrationwriterecordslimit';
    $title = get_string('migrationwriterecordslimit', 'local_intellidata');
    $description = get_string('migrationwriterecordslimit_desc', 'local_intellidata');
    $setting = new admin_setting_configtext($name, $title, $description, '10000');
    $settings->add($setting);

    $name = 'local_intellidata/exportfilesduringmigration';
    $title = get_string('exportfilesduringmigration', 'local_intellidata');
    $description = get_string('exportfilesduringmigration_desc', 'local_intellidata');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $settings->add($setting);

    $name = 'local_intellidata/resetmigrationprogress';
    $title = get_string('resetmigrationprogress', 'local_intellidata');
    $description = get_string('resetmigrationprogress_desc', 'local_intellidata');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $settings->add($setting);

    $name = 'local_intellidata/exportdataformat';
    $title = get_string('exportdataformat', 'local_intellidata');
    $description = get_string('exportdataformat_desc', 'local_intellidata');
    $options = local_intellidata\services\migration_service::ACCEPTABLE_FORMAT_TYPES;
    $setting = new admin_setting_configselect($name, $title, $description, current(array_keys($options)), $options);
    $settings->add($setting);

    // User Tracking.
    $settings->add(new admin_setting_heading(
        'local_intellidata/usertracking', get_string('usertracking', 'local_intellidata'), ''
    ));

    $name = 'local_intellidata/compresstracking';
    $title = new lang_string('compresstracking', 'local_intellidata');
    $description = new lang_string('compresstracking_desc', 'local_intellidata');
    $default = local_intellidata\repositories\tracking\tracking_repository::TYPE_CACHE;
    $options = array(
        local_intellidata\repositories\tracking\tracking_repository::TYPE_LIVE =>
            new lang_string('do_not_use_compresstracking', 'local_intellidata'),
        local_intellidata\repositories\tracking\tracking_repository::TYPE_CACHE =>
            new lang_string('cache_compresstracking', 'local_intellidata'),
        local_intellidata\repositories\tracking\tracking_repository::TYPE_FILE =>
            new lang_string('file_compresstracking', 'local_intellidata')
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $settings->add($setting);

    $name = 'local_intellidata/tracklogs';
    $title = new lang_string('tracklogs', 'local_intellidata');
    $setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
    $settings->add($setting);

    $name = 'local_intellidata/trackdetails';
    $title = new lang_string('trackdetails', 'local_intellidata');
    $setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
    $settings->add($setting);

    $name = 'local_intellidata/inactivity';
    $title = get_string('inactivity', 'local_intellidata');
    $description = get_string('inactivity_desc', 'local_intellidata');
    $default = '60';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    $name = 'local_intellidata/ajaxfrequency';
    $title = get_string('ajaxfrequency', 'local_intellidata');
    $description = get_string('ajaxfrequency_desc', 'local_intellidata');
    $default = '30';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    $name = 'local_intellidata/trackadmin';
    $title = get_string('trackadmin', 'local_intellidata');
    $description = get_string('trackadmin_desc', 'local_intellidata');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $settings->add($setting);

    $name = 'local_intellidata/trackmedia';
    $title = get_string('trackmedia', 'local_intellidata');
    $description = get_string('trackmedia_desc', 'local_intellidata');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $settings->add($setting);


    // BBB meetings.
    $settings->add(new admin_setting_heading(
        'local_intellidata/bbbmeetings', get_string('bbbmeetings', 'local_intellidata'), ''
    ));

    $name = 'local_intellidata/enablebbbmeetings';
    $title = get_string('enablebbbmeetings', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configcheckbox($name, $title, $description, false, true, false);
    $settings->add($setting);

    // BBB debug.
    $name = 'local_intellidata/bbb_debug';
    $title = get_string('enablebbbdebug', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configcheckbox($name, $title, $description, false, true, false);
    $settings->add($setting);

    // BBB API endpoint.
    $name = 'local_intellidata/bbbapiendpoint';
    $title = get_string('bbbapiendpoint', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    // BBB server secret.
    $name = 'local_intellidata/bbbserversecret';
    $title = get_string('bbbserversecret', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    // IB Next LTI.
    $settings->add(new admin_setting_heading(
        'local_intellidata/lti', get_string('ltititle', 'local_intellidata'), ''
    ));

    $name = 'local_intellidata/ltitoolurl';
    $title = get_string('ltitoolurl', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $name = 'local_intellidata/lticonsumerkey';
    $title = get_string('lticonsumerkey', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $name = 'local_intellidata/ltisharedsecret';
    $title = get_string('ltisharedsecret', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $name = 'local_intellidata/debug';
    $title = get_string('ltidebug', 'local_intellidata');
    $description = '';
    $setting = new admin_setting_configcheckbox($name, $title, $description, '', PARAM_TEXT);
    $settings->add($setting);

    $ADMIN->add('intellidata',
        new admin_externalpage('intellidatamigrations',
            new lang_string('migrations', 'local_intellidata'),
            $CFG->wwwroot.'/local/intellidata/migrations/index.php')
    );

    // Temporary solution to review exported files.
    $ADMIN->add('intellidata',
        new admin_externalpage('intellidataexportlogs',
            new lang_string('exportlogs', 'local_intellidata'),
            $CFG->wwwroot.'/local/intellidata/logs/index.php')
    );

    if (!$ADMIN->locate('intellibdata') && $ADMIN->locate('localplugins')) {
        $ADMIN->add('intellidata', new admin_externalpage(
            'intellidatasql',
            new lang_string('sqlreports', 'local_intellidata'),
            $CFG->wwwroot.'/local/intellidata/sql_reports/index.php')
        );
    }

    $ADMIN->add('intellidata',
        new admin_externalpage('intellidataconfig',
            new lang_string('configuration', 'local_intellidata'),
            $CFG->wwwroot.'/local/intellidata/config/index.php')
    );

}