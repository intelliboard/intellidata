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
 * OAuth LTI.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */

namespace local_intellidata\lti;

/**
 * OAuth LTI.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */
class OAuthUtil {

    /**
     * Url encode.
     *
     * @param $input
     * @return array|string|string[]
     */
    public static function urlencode_rfc3986($input) {
        if (is_array($input)) {
            return array_map([
                'local_intellidata\lti\OAuthUtil',
                'urlencode_rfc3986',
            ], $input);
        } else {
            if (is_scalar($input)) {
                return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
            } else {
                return '';
            }
        }
    }

    /**
     * Build http query.
     *
     * @param $params
     * @return string
     */
    public static function build_http_query($params) {
        if (!$params) {
            return '';
        }

        // Urlencode both keys and values.
        $keys = self::urlencode_rfc3986(array_keys($params));
        $values = self::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref Spec 9.1.1.
        uksort($params, 'strcmp');

        $pairs = [];
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value.
                // Ref Spec 9.1.1.
                natsort($value);
                foreach ($value as $duplicatevalue) {
                    $pairs[] = $parameter . '=' . $duplicatevalue;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61).
        // Each name-value pair is separated by an '&' character (ASCII code 38).
        return implode('&', $pairs);
    }
}
