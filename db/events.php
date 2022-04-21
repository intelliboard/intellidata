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

$observers = array(
    // Users events.
    array(
        'eventname' => '\core\event\user_created',
        'callback' => '\local_intellidata\entities\users\observer::user_created',
    ),
    array(
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_intellidata\entities\users\observer::user_updated',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_intellidata\entities\users\observer::user_deleted',
    ),

    // Users Auth events.
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_intellidata\entities\userlogins\observer::user_loggedin',
    ),
    array(
        'eventname' => '\core\event\user_loggedout',
        'callback' => '\local_intellidata\entities\userlogins\observer::user_loggedout',
    ),

    // Categories events.
    array(
        'eventname' => '\core\event\course_category_created',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_created',
    ),
    array(
        'eventname' => '\core\event\course_category_updated',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_updated',
    ),
    array(
        'eventname' => '\core\event\course_category_deleted',
        'callback' => '\local_intellidata\entities\categories\observer::course_category_deleted',
    ),

    // Courses events.
    array(
        'eventname' => '\core\event\course_created',
        'callback' => '\local_intellidata\entities\courses\observer::course_created',
    ),
    array(
        'eventname' => '\core\event\course_updated',
        'callback' => '\local_intellidata\entities\courses\observer::course_updated',
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_intellidata\entities\courses\observer::course_deleted',
    ),

    // Enrollments events.
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_created',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_updated',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\local_intellidata\entities\enrolments\observer::user_enrolment_deleted',
    ),

    // Roles events.
    array(
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_intellidata\entities\roles\observer::role_assigned',
    ),
    array(
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\local_intellidata\entities\roles\observer::role_unassigned',
    ),

    // Course completion events.
    array(
        'eventname' => '\core\event\course_completed',
        'callback' => '\local_intellidata\entities\coursecompletions\observer::course_completed',
    ),
    array(
        'eventname' => '\core\event\course_completion_updated',
        'callback' => '\local_intellidata\entities\coursecompletions\observer::course_completion_updated',
    ),

    // Activities events.
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_created',
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_updated',
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\local_intellidata\entities\activities\observer::course_module_deleted',
    ),

    // Activity completion.
    array(
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\local_intellidata\entities\activitycompletions\observer::course_module_completion_updated',
    ),

    // User grades events.
    array(
        'eventname' => '\core\event\user_graded',
        'callback' => '\local_intellidata\entities\usergrades\observer::user_graded',
    ),

    // Forum discussions.
    array(
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_created',
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_updated',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_updated',
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_moved',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_moved',
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_deleted',
        'callback' => '\local_intellidata\entities\forums\observer::discussion_deleted',
    ),

    // Forum posts.
    array(
        'eventname' => '\mod_forum\event\post_created',
        'callback' => '\local_intellidata\entities\forums\observer::post_created',
    ),
    array(
        'eventname' => '\mod_forum\event\post_updated',
        'callback' => '\local_intellidata\entities\forums\observer::post_updated',
    ),
    array(
        'eventname' => '\mod_forum\event\post_deleted',
        'callback' => '\local_intellidata\entities\forums\observer::post_deleted',
    ),

    // Forum posts.
    array(
        'eventname' => '\mod_quiz\event\attempt_started',
        'callback' => '\local_intellidata\entities\quizzes\observer::attempt_started',
    ),
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_intellidata\entities\quizzes\observer::attempt_submitted',
    ),

    // Quiz questions events.
    array(
        'eventname' => '\core\event\question_created',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_created',
    ),
    array(
        'eventname' => '\core\event\question_updated',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_updated',
    ),
    array(
        'eventname' => '\core\event\question_deleted',
        'callback' => '\local_intellidata\entities\quizquestions\observer::question_deleted',
    ),

    // Assignment Submissions.
    array(
        'eventname' => '\mod_assign\event\submission_created',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_created',
    ),
    array(
        'eventname' => '\mod_assign\event\submission_updated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_updated',
    ),
    array(
        'eventname' => '\mod_assign\event\submission_duplicated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_duplicated',
    ),
    array(
        'eventname' => '\mod_assign\event\submission_graded',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_graded',
    ),
    array(
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => '\local_intellidata\entities\assignments\observer::assessable_submitted',
    ),
    array(
        'eventname' => '\mod_assign\event\submission_status_updated',
        'callback' => '\local_intellidata\entities\assignments\observer::submission_status_updated',
    ),

    // Cohorts events.
    array(
        'eventname' => '\core\event\cohort_created',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_created',
    ),
    array(
        'eventname' => '\core\event\cohort_updated',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_updated',
    ),
    array(
        'eventname' => '\core\event\cohort_deleted',
        'callback' => '\local_intellidata\entities\cohorts\observer::cohort_deleted',
    ),

    // Cohort members events.
    array(
        'eventname' => '\core\event\cohort_member_added',
        'callback' => '\local_intellidata\entities\cohortmembers\observer::cohort_member_added',
    ),
    array(
        'eventname' => '\core\event\cohort_member_removed',
        'callback' => '\local_intellidata\entities\cohortmembers\observer::cohort_member_removed',
    ),

    // Grade Item events.
    array(
        'eventname' => '\core\event\grade_item_deleted',
        'callback' => '\local_intellidata\entities\gradeitems\observer::grade_item_deleted',
    ),

    // Participations.
    array(
        'eventname' => '*',
        'callback' => '\local_intellidata\entities\participations\observer::new_participation',
    ),

    // Tracking.
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::user_deleted',
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::course_deleted',
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\local_intellidata\entities\usertrackings\observer::course_module_deleted',
    ),
);