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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

class DBHelper {
    const MYSQL_TYPE = 'mysqli';
    const POSTGRES_TYPE = 'pgsql';
    const MARIADB_TYPE = 'mariadb';

    /**
     * @param $id
     * @param $value
     * @param array $params
     * @param null $dbtype
     * @return string|null
     */
    public static function get_operator($id, $value, $params = array(), $dbtype = null) {
        global $CFG;

        $operators = [
            'TIME_TO_SEC' => array(
                self::MYSQL_TYPE => 'TIME_TO_SEC',
                self::POSTGRES_TYPE => function($value, $params) {
                    return "extract ('epoch' from TO_TIMESTAMP($value, 'HH24:MI:SS')::TIME)";
                }),
            'SEC_TO_TIME' => array(
                self::MYSQL_TYPE => 'SEC_TO_TIME',
                self::POSTGRES_TYPE => ''
            ),
            'GROUP_CONCAT' => array(
                self::MYSQL_TYPE => function($value, $params = array('separator' => ', ')) {

                    if (empty($params['order'])) {
                        $params['order'] = '';
                    }

                    return "GROUP_CONCAT($value SEPARATOR '" . $params['separator'] . "')";
                },
                self::POSTGRES_TYPE => function($value, $params = array('separator' => ', ')) {

                    if (empty($params['order'])) {
                        $params['order'] = '';
                    }

                    return "string_agg($value::character varying, '" . $params['separator'] . "')";
                }
            ),
            'WEEKDAY' => array(
                self::MYSQL_TYPE => 'WEEKDAY',
                self::POSTGRES_TYPE => function($value, $params) {
                    return "extract(dow from $value::timestamp)";
                }
            ),
            'DAYOFWEEK' => array(
                self::MYSQL_TYPE => function($value, $params) {
                    return "(DAYOFWEEK($value) - 1)";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "EXTRACT(DOW FROM $value)";
                }
            ),
            'DATE_FORMAT_A' => array(
                self::MYSQL_TYPE => function($value, $params) {
                    return "DATE_FORMAT($value, '%a')";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "to_char($value, 'Day')";
                }
            ),
            'FROM_UNIXTIME' => array(
                self::MYSQL_TYPE => function($value, $params = array()) {

                    $format = isset($params['format']) ? $params['format'] : '%Y-%m-%d %T';
                    $pureparam = isset($params['pureparam']) ? $params['pureparam'] : false;

                    return "FROM_UNIXTIME($value, " . (!$pureparam ? "'{$format}'" : "{$format}") . ")";
                },
                self::POSTGRES_TYPE => function($value, $params = array()) {
                    $format = isset($params['format']) ? $params['format'] : 'YYYY-mm-dd HH24:MI:SS';
                    $pureparam = isset($params['pureparam']) ? $params['pureparam'] : false;
                    return "to_char(to_timestamp({$value}), " . (!$pureparam ? "'{$format}'" : "{$format}") . ")";
                }
            ),
            'MONTH' => array(
                self::MYSQL_TYPE => function($value, $params) {
                    return "MONTH(FROM_UNIXTIME({$value}))";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "EXTRACT(MONTH FROM to_timestamp({$value}))";
                }
            ),
            'INSERT' => array(
                self::MYSQL_TYPE => function($value, $params) {
                    $sentence = $params['sentence'];
                    $position = isset($params['position']) ? $params['position'] : 1;
                    $length   = isset($params['length']) ? $params['length'] : "CHAR_LENGTH($value)";

                    return "INSERT($sentence, $position, $length, $value)";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    $sentence = $params['sentence'];
                    $position = isset($params['position']) ? $params['position'] : 1;
                    $length   = isset($params['length']) ? $params['length'] : "CHAR_LENGTH($value)";

                    return "OVERLAY($sentence placing $value from $position for $length)";
                }
            ),
            'DAY' => array(
                self::MYSQL_TYPE => 'DAY',
                self::POSTGRES_TYPE => function($value, $params) {
                    return "date_part('day', $value)";
                }
            ),
            'YEAR' => array(
                self::MYSQL_TYPE => function($value, $params) {
                    return "YEAR(FROM_UNIXTIME({$value}))";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "EXTRACT(YEAR FROM to_timestamp({$value}))";
                }
            ),
            'FIND_IN_SET' => [
                self::MYSQL_TYPE => function($value, $params) {
                    if (!isset($params['field'])) {
                        throw new \Exception('parameter "field" is required');
                    }

                    return "FIND_IN_SET({$params['field']}, {$value})";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    if (!isset($params['field'])) {
                        throw new \Exception('parameter "field" is required');
                    }

                    return "{$params['field']} = ANY (string_to_array({$value},','))";
                }
            ],
            'CAST_FLOAT' => [
                self::MYSQL_TYPE => function($value, $params) {
                    if (!isset($params['field'])) {
                        throw new \Exception('parameter "field" is required');
                    }

                    return "CAST({$params['field']} AS DECIMAL({$value}))";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    if (!isset($params['field'])) {
                        throw new \Exception('parameter "field" is required');
                    }

                    return "CAST({$params['field']} AS FLOAT)";
                }
            ],
            'JSON_UNQUOTE' => [
                self::MYSQL_TYPE => function($value, $params) {
                    return "JSON_UNQUOTE($value)";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "($value)::int";
                },
            ],
            'JSON_EXTRACT' => [
                self::MYSQL_TYPE => function($value, $params) {
                    return "JSON_EXTRACT($value, '$.{$params['path']}')";
                },
                self::POSTGRES_TYPE => function($value, $params) {
                    return "$value::json->>'{$params['path']}'";
                },
            ],
        ];

        if ($dbtype === null) {
            if ($CFG->dbtype == self::MARIADB_TYPE) {
                $dbtype = self::MYSQL_TYPE;
            } else {
                $dbtype = $CFG->dbtype;
            }
        }

        if (empty($operators[$id])) {
            return null;
        }

        $operator = $operators[$id];

        if (is_array($operators[$id])) {
            if (empty($operators[$id][$dbtype])) {
                $operator = $operators[$id][self::MYSQL_TYPE];
            } else {
                $operator = $operators[$id][$dbtype];
            }
        }

        if (is_string($operator)) {
            $value = $operator . '(' . $value . ')';
        } else {
            $value = $operator($value, $params);
        }

        return $value;
    }

    /**
     * @param string $groupperiod daytime|week|monthyearday|month|monthyear|quarter|year
     * @param $sqlfield
     * @return string
     * @throws \coding_exception
     * @throws \Exception
     */
    public static function group_by_date_val($groupperiod, $sqlfield, $params = []) {
        global $CFG;

        if (isset($params['offset'])) {
            $offset = intval($params['offset']);
        } else {
            $offset = 0;
        }

        switch ($groupperiod) {
            case 'daytime':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgretimedate', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqltimedate', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;

            case 'week':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgreweek', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqlweek', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;
            case 'monthyearday':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgremonthyearday', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqlmonthyearday', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;
            case 'month':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgremonth', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqlmonth', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;
            case 'monthyear':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgremonthyear', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqlmonthyear', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;
            case 'quarter':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgrequarteryear', 'local_intellicart');;
                    $result = "CONCAT('Q', to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}'))";
                } else {
                    $format = get_string('mysqlyear', 'local_intellicart');
                    $quarter = "QUARTER(FROM_UNIXTIME({$sqlfield} + {$offset}))";
                    $year = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                    $result = "CONCAT('Q', {$quarter}, ' ', {$year})";
                }

                break;
            case 'year':
                if ($CFG->dbtype == self::POSTGRES_TYPE) {
                    $format = get_string('postgreyear', 'local_intellicart');;
                    $result = "to_char(to_timestamp({$sqlfield} + {$offset}),'{$format}')";
                } else {
                    $format = get_string('mysqlyear', 'local_intellicart');
                    $result = "FROM_UNIXTIME({$sqlfield} + {$offset}, '{$format}')";
                }

                break;
            default:
                throw new \Exception('Invalid grouping period');
        }

        return $result;
    }

    /**
     * @param $type
     * @return string
     * @throws \Exception
     */
    public static function get_typecast($type) {
        global $CFG;

        if ($CFG->dbtype != self::POSTGRES_TYPE) {
            return '';
        }

        switch ($type) {
            case 'numeric':
                return '::NUMERIC';
            case 'text':
                return '::TEXT';
            default:
                throw new \Exception('Invalid type');
        }
    }

    /**
     * @param $sql
     * @param $params
     * @return array|string|string[]
     */
    public static function debug_build_sql($sql, $params) {
        $sql = str_replace(['{', '}'], ['mdl_', ''], $sql);

        foreach ($params as $key => $param) {
            $sql = str_replace(":{$key}", "'$param'", $sql);
        }

        return $sql;
    }

    /**
     * @return string[]
     */
    public static function get_row_number() {
        global $CFG;

        if ($CFG->dbtype == self::POSTGRES_TYPE) {
            $rownumber = "row_number() OVER ()";
            $rownumberselect = "";
        } else {
            $rownumber = "@x:=@x+1";
            $rownumberselect = "(SELECT @x:= 0) AS x, ";
        }

        return [$rownumber, $rownumberselect];
    }

    /**
     * @param $letters
     * @return string
     */
    public static function get_condition_userstatus($letters = '') {
        if (get_config('local_intellicart', 'displayrecordsforsuspendedusers')) {
            $res = "#deleted = 0";
        } else {
            $res = "#deleted = 0 AND #suspended = 0 AND #confirmed = 1";
        }

        $letters = ($letters) ? "{$letters}." : '';

        return str_replace('#', $letters, $res);
    }
}
