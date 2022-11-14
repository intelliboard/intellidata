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
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\custom;
use core\invalid_persistent_exception;
use local_intellidata\helpers\DBManagerHelper;
use local_intellidata\helpers\EventsHelper;
use local_intellidata\services\dbschema_service;
use stdClass;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2022 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity {

    /** The entity type name. */
    public static $datatype = null;

    /** @var array The model data. */
    private $data = array();

    /** @var array The fields to return. */
    protected $returnfields = [];

    /** @var array The list of validation errors. */
    private $errors = array();

    public function __construct($datatype, $record = null, $returnfields = []) {

        self::$datatype = $datatype;

        if (count($returnfields)) {
            $this->returnfields = $returnfields;
        }
        if ($record) {
            self::set_values($record);
        }
    }

    /**
     * Insert Values from record.
     *
     * @return \stdClass
     */
    final public function set_values($record) {
        $properties = self::properties_definition($this->returnfields);

        foreach ($properties as $property => $definition) {
            if (!isset($record->{$property})) {
                continue;
            }
            $this->set($property, $record->{$property});
        }
    }

    /**
     * Data setter.
     *
     * This is the main setter for all the properties. Developers can implement their own setters (set_propertyname)
     * and they will be called by this function. Custom setters should call internal_set() to finally set the value.
     * Internally this is not used {@link self::to_record()} or
     * {@link self::from_record()} because the data is not expected to be validated or changed when reading/writing
     * raw records from the DB.
     *
     * @param  string $property The property name.
     * @return \local_intellidata\entities\entity
     */
    final public function set($property, $value) {
        if (!self::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        $methodname = 'set_' . $property;
        if (method_exists($this, $methodname)) {
            $this->$methodname($value);
            return $this;
        }
        return $this->raw_set($property, $value);
    }

    /**
     * Data getter.
     *
     * This is the main getter for all the properties. Developers can implement their own getters (get_propertyname)
     * and they will be called by this function. Custom getters can use raw_get to get the raw value.
     * Internally this is not used by {@link self::to_record()} or
     * {@link self::from_record()} because the data is not expected to be validated or changed when reading/writing
     * raw records from the DB.
     *
     * @param  string $property The property name.
     * @return mixed
     */
    final public function get($property) {
        if (!self::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        $methodname = 'get_' . $property;
        if (method_exists($this, $methodname)) {
            return $this->$methodname();
        }
        return $this->raw_get($property);
    }

    /**
     * @return mixed|string|null
     * @throws coding_exception
     */
    final public function get_crud() {
        if ($this->raw_get('crud')) {
            return $this->raw_get('crud');
        }

        return EventsHelper::CRUD_CREATED;
    }

    /**
     * Internal Data getter.
     *
     * This is the main getter for all the properties. Developers can implement their own getters
     * but they should be calling {@link self::get()} in order to retrieve the value. Essentially
     * the getters defined by the developers would only ever be used as helper methods and will not
     * be called internally at this stage. In other words, do not expect {@link self::to_record()} or
     * {@link self::from_record()} to use them.
     *
     * This is protected because it is only for raw low level access to the data fields.
     * Note this function is named raw_get and not get_raw to avoid naming clashes with a property named raw.
     *
     * @param  string $property The property name.
     * @return mixed
     */
    final protected function raw_get($property) {
        if (!self::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        if (!array_key_exists($property, $this->data) && !self::is_property_required($property)) {
            $this->raw_set($property, self::get_property_default_value($property));
        }
        return isset($this->data[$property]) ? $this->data[$property] : null;
    }

    /**
     * Data setter.
     *
     * This is the main setter for all the properties. Developers can implement their own setters
     * but they should always be calling {@link self::set()} in order to set the value. Essentially
     * the setters defined by the developers are helper methods and will not be called internally
     * at this stage. In other words do not expect {@link self::to_record()} or
     * {@link self::from_record()} to use them.
     *
     * This is protected because it is only for raw low level access to the data fields.
     *
     * @param  string $property The property name.
     * @param  mixed $value The value.
     * @return $this
     */
    final protected function raw_set($property, $value) {
        if (!self::has_property($property)) {
            throw new coding_exception('Unexpected property \'' . s($property) .'\' requested.');
        }
        if (!array_key_exists($property, $this->data) || $this->data[$property] != $value) {
            // If the value is changing, we invalidate the model.
            $this->validated = false;
        }
        $this->data[$property] = $value;

        return $this;
    }

    /**
     * Get the properties definition of this model..
     *
     * @return array
     */
    final public static function properties_definition($returnfields = []) {
        global $CFG;

        $def = self::define_properties();

        // List of reserved property names. Mostly because we have methods (getters/setters) which would confict with them.
        // Think about backwards compability before adding new ones here!
        $reserved = array('errors', 'formatted_properties', 'property_default_value', 'property_error_message');

        foreach ($def as $property => $definition) {

            // Include only return fields.
            if (count($returnfields) and !in_array($property, $returnfields)) {
                unset($def[$property]);
                continue;
            }

            // Ensures that the null property is always set.
            if (!array_key_exists('null', $definition)) {
                $def[$property]['null'] = NULL_NOT_ALLOWED;
            }

            // Warn the developers when they are doing something wrong.
            if ($CFG->debugdeveloper) {
                if (!array_key_exists('type', $definition)) {
                    throw new coding_exception('Missing type for: ' . $property);
                } else if (isset($definition['message']) && !($definition['message'] instanceof lang_string)) {
                    throw new coding_exception('Invalid error message for: ' . $property);
                } else if (in_array($property, $reserved)) {
                    throw new coding_exception('This property cannot be defined: ' . $property);
                }
            }
        }

        $def['recordtimecreated'] = array(
            'default' => 0,
            'type' => PARAM_INT,
            'null' => NULL_NOT_ALLOWED
        );
        $def['recordusermodified'] = array(
            'default' => 0,
            'type' => PARAM_INT,
            'null' => NULL_NOT_ALLOWED
        );
        $def['crud'] = [
            'default' => EventsHelper::CRUD_CREATED,
            'type' => PARAM_TEXT,
            'description' => 'Record CRUD.',
            'null' => NULL_ALLOWED
        ];

        return $def;
    }

    /**
     * Gets all the formatted properties.
     *
     * Formatted properties are properties which have a format associated with them.
     *
     * @return array Keys are property names, values are property format names.
     */
    final public static function get_formatted_properties() {
        $properties = self::properties_definition();

        $formatted = array();
        foreach ($properties as $property => $definition) {
            $propertyformat = $property . 'format';
            if (($definition['type'] == PARAM_RAW || $definition['type'] == PARAM_CLEANHTML)
                && array_key_exists($propertyformat, $properties)
                && $properties[$propertyformat]['type'] == PARAM_INT) {
                $formatted[$property] = $propertyformat;
            }
        }

        return $formatted;
    }

    /**
     * Gets the default value for a property.
     *
     * This assumes that the property exists.
     *
     * @param string $property The property name.
     * @return mixed
     */
    final protected static function get_property_default_value($property) {
        $properties = self::properties_definition();
        if (!isset($properties[$property]['default'])) {
            return null;
        }
        $value = $properties[$property]['default'];
        if ($value instanceof \Closure) {
            return $value();
        }
        return $value;
    }

    /**
     * Gets the error message for a property.
     *
     * This assumes that the property exists.
     *
     * @param string $property The property name.
     * @return lang_string
     */
    final protected static function get_property_error_message($property) {
        $properties = self::properties_definition();
        if (!isset($properties[$property]['message'])) {
            return new lang_string('invaliddata', 'error');
        }
        return $properties[$property]['message'];
    }

    /**
     * Returns whether or not a property was defined.
     *
     * @param  string $property The property name.
     * @return boolean
     */
    final public static function has_property($property) {
        $properties = self::properties_definition();
        return isset($properties[$property]);
    }

    /**
     * Returns whether or not a property is required.
     *
     * By definition a property with a default value is not required.
     *
     * @param  string $property The property name.
     * @return boolean
     */
    final public static function is_property_required($property) {
        $properties = self::properties_definition();
        return !array_key_exists('default', $properties[$property]);
    }

    /**
     * Populate this class with data from a DB record.
     *
     * Note that this does not use any custom setter because the data here is intended to
     * represent what is stored in the database.
     *
     * @param \stdClass $record A DB record.
     * @return persistent
     */
    final public function from_record(stdClass $record) {
        $record = (array) $record;
        foreach ($record as $property => $value) {
            $this->raw_set($property, $value);
        }
        return $this;
    }

    /**
     * Create a DB record from this class.
     *
     * Note that this does not use any custom getter because the data here is intended to
     * represent what is stored in the database.
     *
     * @return \stdClass
     */
    final public function to_record() {
        $data = new stdClass();
        $properties = self::properties_definition($this->returnfields);
        foreach ($properties as $property => $definition) {
            $data->$property = $this->raw_get($property);
        }

        return $data;
    }

    /**
     * Hook to execute before an export.
     *
     * This is only intended to be used by child classes, do not put any logic here!
     *
     * @return void
     */
    protected function before_export() {
    }

    /**
     * Hook to execute after an export.
     *
     * @return void
     */
    public function after_export($record) {
        return $record;
    }

    /**
     * Insert a record in the DB.
     *
     * @return persistent
     */
    final public function export() {
        global $USER;

        if (!$this->is_valid()) {
            $errors = $this->get_errors();
            $errors['data'] = '(' . self::$datatype . ') ' . json_encode($this->data);
            throw new invalid_persistent_exception($errors);
        }

        // Before create hook.
        $this->before_export();

        // Clean properties.
        $this->clean_data();

        // We can safely set those values bypassing the validation because we know what we're doing.
        $now = time();
        $this->raw_set('recordtimecreated', $now);
        $this->raw_set('recordusermodified', $USER->id);
        $this->raw_set('crud', $this->get_crud());

        $record = $this->to_record();

        // We ensure that this is flagged as validated.
        $this->validated = true;

        return $record;
    }

    /**
     * Export data with after_export() action.
     *
     * @return null
     * @throws invalid_persistent_exception
     */
    final public function export_data() {
        return $this->after_export($this->export());
    }

    /**
     * Hook to execute before the validation.
     *
     * This hook will not affect the validation results in any way but is useful to
     * internally set properties which will need to be validated.
     *
     * This is only intended to be used by child classes, do not put any logic here!
     *
     * @return void
     */
    protected function before_validate() {
    }

    /**
     * Validates the data.
     *
     * Developers can implement addition validation by defining a method as follows. Note that
     * the method MUST return a lang_string() when there is an error, and true when the data is valid.
     *
     * protected function validate_propertyname($value) {
     *     if ($value !== 'My expected value') {
     *         return new lang_string('invaliddata', 'error');
     *     }
     *     return true
     * }
     *
     * It is OK to use other properties in your custom validation methods when you need to, however note
     * they might not have been validated yet, so try not to rely on them too much.
     *
     * Note that the validation methods should be protected. Validating just one field is not
     * recommended because of the possible dependencies between one field and another,also the
     * field ID can be used to check whether the object is being updated or created.
     *
     * When validating foreign keys the persistent should only check that the associated model
     * exists. The validation methods should not be used to check for a change in that relationship.
     * The API method setting the attributes on the model should be responsible for that.
     * E.g. On a course model, the method validate_categoryid will check that the category exists.
     * However, if a course can never be moved outside of its category it would be up to the calling
     * code to ensure that the category ID will not be altered.
     *
     * @return array|true Returns true when the validation passed, or an array of properties with errors.
     */
    final public function validate() {

        // Before validate hook.
        $this->before_validate();

        // If this object has not been validated yet.
        if ($this->validated !== true) {

            $errors = array();
            $properties = self::properties_definition($this->returnfields);
            foreach ($properties as $property => $definition) {

                // Get the data, bypassing the potential custom getter which could alter the data.
                $value = $this->raw_get($property);

                // Check if the property is required.
                if ($value === null && self::is_property_required($property)) {
                    $errors[$property] = new lang_string('requiredelement', 'form');
                    continue;
                }

                // Check that type of value is respected.
                try {
                    if ($definition['type'] === PARAM_BOOL && $value === false) {
                        // Validate_param() does not like false with PARAM_BOOL, better to convert it to int.
                        $value = 0;
                    }
                    if ($definition['type'] === PARAM_CLEANHTML) {
                        // We silently clean for this type. It may introduce changes even to valid data.
                        $value = clean_param($value, PARAM_CLEANHTML);
                    }
                    if ($definition['type'] === PARAM_RAW || $definition['type'] === PARAM_TEXT) {
                        // We silently clean for this type to avoid utf encoding problems.
                        $value = clean_param($value, PARAM_RAW);
                    }
                    validate_param($value, $definition['type'], $definition['null']);
                } catch (\Exception $e) {
                    $errors[$property] = self::get_property_error_message($property);
                    continue;
                }

                // Check that the value is part of a list of allowed values.
                if (isset($definition['choices']) && !in_array($value, $definition['choices'])) {
                    $errors[$property] = self::get_property_error_message($property);
                    continue;
                }

                // Call custom validation method.
                $method = 'validate_' . $property;
                if (method_exists($this, $method)) {

                    $valid = $this->{$method}($value);
                    if ($valid !== true) {
                        if (!($valid instanceof lang_string)) {
                            throw new coding_exception('Unexpected error message.');
                        }
                        $errors[$property] = $valid;
                        continue;
                    }
                }
            }

            $this->validated = true;
            $this->errors = $errors;
        }

        return empty($this->errors) ? true : $this->errors;
    }

    /**
     * Returns whether or not the model is valid.
     *
     * @return boolean True when it is.
     */
    final public function is_valid() {
        return $this->validate() === true;
    }

    /**
     * Returns the validation errors.
     *
     * @return array
     */
    final public function get_errors() {
        $this->validate();
        return $this->errors;
    }

    final public function clean_data() {

        $properties = self::properties_definition($this->returnfields);

        foreach ($properties as $property => $definition) {

            // Get the data, bypassing the potential custom getter which could alter the data.
            $value = $this->raw_get($property);

            // Check if the property is required.
            if ($value === null) {
                continue;
            }

            if ($definition['type'] === PARAM_BOOL && $value === false) {
                $value = 0;
            } else if ($definition['type'] === PARAM_CLEANHTML) {
                $value = clean_param($value, PARAM_CLEANHTML);
            } else {
                $value = clean_param($value, $definition['type']);
            }

            $this->raw_set($property, $value);
        }

    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        $fields = [];
        $dbschema = new dbschema_service();

        if (!self::$datatype) {
            return $fields;
        }

        $columns = $dbschema->get_table_columns(self::$datatype);

        foreach ($columns as $column) {
            $fields[$column->name] = [
                'type' => PARAM_RAW,
                'description' => $column->name,
                'default' => DBManagerHelper::get_field_default_value($column),
                'null' => NULL_ALLOWED
            ];
        }

        return $fields;
    }

}
