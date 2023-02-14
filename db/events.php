<?php
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
 * Add event handlers for the local intellidata
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

defined('MOODLE_INTERNAL') || die;

$observers = [
    // Users events.
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\local_intellidata\entities\users\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_intellidata\entities\users\observer::user_updated',
    ],
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_intellidata\entities\users\observer::user_deleted',
    ],

    // Users Auth events.
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_intellidata\entities\userlogins\observer::user_loggedin',
    ],
    [
        'eventname' => '\core\event\user_loggedout',
        'callback' => '\local_intellidata\entities\userlogins\observer::user_loggedout',
    ],

    // Categories events.
    [
        'eventname' => '\core\event\course_category_created',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_created',
    ],
    [
        'eventname' => '\core\event\course_category_updated',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_updated',
    ],
    [
        'eventname' => '\core\event\course_category_deleted',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_deleted',
    ],

    // Courses events.
    [
        'eventname' => '\core\event\course_created',
        'callback' => '\local_intellidata\entities\courses\observer::course_created',
    ],
    [
        'eventname' => '\core\event\course_updated',
        'callback' => '\local_intellidata\entities\courses\observer::course_updated',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_intellidata\entities\courses\observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\course_restored',
        'callback' => '\local_intellidata\entities\courses\observer::course_restored',
    ],

    // Enrollments events.
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_created',
    ],
    [
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_updated',
    ],
    [
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_deleted',
    ],

    // Roles events.
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_intellidata\entities\roles\observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\local_intellidata\entities\roles\observer::role_unassigned',
    ],

    // Course completion events.
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\local_intellidata\entities\coursecompletions\observer::course_completed',
    ],
    [
        'eventname' => '\core\event\course_completion_updated',
        'callback' => '\local_intellidata\entities\coursecompletions\observer::course_completion_updated',
    ],

    // Activities events.
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_created',
    ],
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_updated',
    ],
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_deleted',
    ],

    // Activity completion.
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\local_intellidata\entities\activitycompletions\observer::course_module_completion_updated',
    ],

    // User grades events.
    [
        'eventname' => '\core\event\user_graded',
        'callback' => '\local_intellidata\entities\usergrades\observer::user_graded',
    ],

    // Grade letter updated events.
    array(
        'eventname' => '\core\event\grade_letter_updated',
        'callback' => '\local_intellidata\entities\usergrades\observer::grade_letter_updated',
    ),
    array(
        'eventname' => '\core\event\grade_letter_created',
        'callback' => '\local_intellidata\entities\usergrades\observer::grade_letter_created',
    ),
    array(
        'eventname' => '\core\event\grade_letter_deleted',
        'callback' => '\local_intellidata\entities\usergrades\observer::grade_letter_deleted',
    ),

    // Forum discussions.
    [
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_created',
    ],
    [
        'eventname' => '\mod_forum\event\discussion_updated',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_updated',
    ],
    [
        'eventname' => '\mod_forum\event\discussion_moved',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_moved',
    ],
    [
        'eventname' => '\mod_forum\event\discussion_deleted',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_deleted',
    ],

    // Forum posts.
    [
        'eventname' => '\mod_forum\event\post_created',
        'callback' => '\local_intellidata\entities\forums\observer::post_created',
    ],
    [
        'eventname' => '\mod_forum\event\post_updated',
        'callback' => '\local_intellidata\entities\forums\observer::post_updated',
    ],
    [
        'eventname' => '\mod_forum\event\post_deleted',
        'callback' => '\local_intellidata\entities\forums\observer::post_deleted',
    ],

    // Forum posts.
    [
        'eventname' => '\mod_quiz\event\attempt_started',
        'callback' => '\local_intellidata\entities\quizzes\observer::attempt_started',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_intellidata\entities\quizzes\observer::attempt_submitted',
    ],

    // Quiz questions events.
    [
        'eventname' => '\core\event\question_created',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_created',
    ],
    [
        'eventname' => '\core\event\question_updated',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_updated',
    ],
    [
        'eventname' => '\core\event\question_deleted',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_deleted',
    ],

    // Assignment Submissions.
    [
        'eventname' => '\mod_assign\event\submission_created',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_created',
    ],
    [
        'eventname' => '\mod_assign\event\submission_updated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_updated',
    ],
    [
        'eventname' => '\mod_assign\event\submission_duplicated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_duplicated',
    ],
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_graded',
    ],
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => '\local_intellidata\entities\assignments\observer::assessable_submitted',
    ],
    [
        'eventname' => '\mod_assign\event\submission_status_updated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_status_updated',
    ],

    // Cohorts events.
    [
        'eventname' => '\core\event\cohort_created',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_created',
    ],
    [
        'eventname' => '\core\event\cohort_updated',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_updated',
    ],
    [
        'eventname' => '\core\event\cohort_deleted',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_deleted',
    ],

    // Course sections events.
    [
        'eventname' => '\core\event\course_section_created',
        'callback' => '\local_intellidata\entities\coursesections\observer::course_section_created',
    ],
    [
        'eventname' => '\core\event\course_section_updated',
        'callback' => '\local_intellidata\entities\coursesections\observer::course_section_updated',
    ],
    [
        'eventname' => '\core\event\course_section_deleted',
        'callback' => '\local_intellidata\entities\coursesections\observer::course_section_deleted',
    ],

    // Cohort members events.
    [
        'eventname' => '\core\event\cohort_member_added',
        'callback' => '\local_intellidata\entities\cohortmembers\observer::cohort_member_added',
    ],
    [
        'eventname' => '\core\event\cohort_member_removed',
        'callback' => '\local_intellidata\entities\cohortmembers\observer::cohort_member_removed',
    ],

    // Grade Item events.
    [
        'eventname' => '\core\event\grade_item_deleted',
        'callback' => '\local_intellidata\entities\gradeitems\observer::grade_item_deleted',
    ],

    // Participations.
    [
        'eventname' => '*',
        'callback' => '\local_intellidata\entities\participations\observer::new_participation',
    ],

    // Tracking.
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::user_deleted',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::course_module_deleted',
    ],

    // User info categories events.
    [
        'eventname' => '\core\event\user_info_category_created',
        'callback' => '\local_intellidata\entities\userinfocategories\observer::user_info_category_created',
    ],
    [
        'eventname' => '\core\event\user_info_category_updated',
        'callback' => '\local_intellidata\entities\userinfocategories\observer::user_info_category_updated',
    ],
    [
        'eventname' => '\core\event\user_info_category_deleted',
        'callback' => '\local_intellidata\entities\userinfocategories\observer::user_info_category_deleted',
    ],

    // User info fields events.
    [
        'eventname' => '\core\event\user_info_field_created',
        'callback' => '\local_intellidata\entities\userinfofields\observer::user_info_field_created',
    ],
    [
        'eventname' => '\core\event\user_info_field_updated',
        'callback' => '\local_intellidata\entities\userinfofields\observer::user_info_field_updated',
    ],
    [
        'eventname' => '\core\event\user_info_field_deleted',
        'callback' => '\local_intellidata\entities\userinfofields\observer::user_info_field_deleted',
    ],

    // User info data events.
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\local_intellidata\entities\userinfodatas\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_intellidata\entities\userinfodatas\observer::user_updated',
    ],

    // Logs.
    [
        'eventname' => '*',
        'callback' => '\local_intellidata\entities\logs\observer::log_created',
    ]
];
