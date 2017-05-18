<?php

class ValidationSet {

    private $_validators = array();
    private $_validation_errors = array();
    private $_has_errors;

    public function returnInstance() {
        return $this;
    }

    public function addValidator(Validator $validator) {
        $validator->setValidationSet($this);
        array_push($this->_validators, $validator);
    }

    public function addError($validation_error) {
        array_push($this->_validation_errors, $validation_error);
        $this->setHasErrors(TRUE);
    }

    public function hasErrors() {
        return $this->_has_errors;
    }

    public function setHasErrors($has_errors) {
        $this->_has_errors = $has_errors;
    }

    public function getValidators() {
        return $this->_validators;
    }

    public function getValidationErrors() {
        return $this->_validation_errors;
    }

    public function getErrors() {
        $errors = array();
        foreach ($this->_validation_errors as $item) {
            $errors[$item->getFieldName()] = $item->getErrorMessage();
        }
        return $errors;
    }

    public function getErrorsByFieldName($field_name) {
        foreach ($this->_validation_errors as $value) {
            if ($value->getFieldName() == $field_name) {
                return $value->getErrorMessage();
            }
        }
    }

    public function validate() {
        foreach ($this->_validators as $validator) {
            $validator->validate();
        }
    }

}

class Validator {

    private $_validation_set;
    private $_field_name;
    private $_field_value;
    private $_error_message;

    public function getValidationSet() {
        return $this->_validation_set;
    }

    public function setValidationSet(&$validation_set) {
        $this->_validation_set = $validation_set;
    }

    public function getFieldName() {
        return $this->_field_name;
    }

    public function getFieldValue() {
        return $this->_field_value;
    }

    public function getErrorMessage() {
        return $this->_error_message;
    }

    public function getValueByFieldName($field_name) {
        foreach ($_POST as $key => $value) {
            if ($key == $field_name) {
                return $value;
            }
        }
    }

}

class TextValidator extends Validator {

    public function __construct($validation_set, $field_name, $field_value = NULL, $error_message = NULL) {

        $this->_validation_set = $validation_set;
        $this->_field_name = $field_name;

        if ($field_value == NULL) {
            $this->_field_value = $this->getValueByFieldName($this->_field_name);
        } else {
            $this->_field_value = $field_value;
        }

        if ($error_message == NULL) {
            $this->_error_message = "No text was supplied";
        } else {
            $this->_error_message = $error_message;
        }
    }

    public function validate() {
        if (!(strlen($this->_field_value) > 0)) {
            $this->_validation_set->addError(new Error($this->_field_name, $this->_error_message));
        }
    }

}

class NumberValidator extends Validator {

    public function __construct($validation_set, $field_name, $field_value = NULL, $error_message = NULL) {
        $this->_validation_set = $validation_set;
        $this->_field_name = $field_name;

        if ($field_value == NULL) {
            $this->_field_value = $this->getValueByFieldName($this->_field_name);
        } else {
            $this->_field_value = $field_value;
        }

        if ($error_message == NULL) {
            $this->_error_message = "Invalid number format";
        } else {
            $this->_error_message = $error_message;
        }
    }

    public function validate() {
        if (strlen($this->_field_value) > 0) {
            if (!preg_match('/^\d+(\.\d+)?$/', $this->_field_value) || !($this->_field_value > 0)) {
                $this->_validation_set->addError(new Error($this->_field_name, $this->_error_message));
            }
        } else {
            $this->_validation_set->addError(new Error($this->_field_name, $this->_error_message));
        }
    }

}

class ExtensionValidator extends Validator {

    private $_extension;

    public function __construct($validation_set, $field_name, $extension, $field_value = NULL, $error_message = NULL) {
        $this->_validation_set = $validation_set;
        $this->_field_name = $field_name;
        $this->_extension = $extension;

        if ($field_value == NULL) {
            $this->_field_value = $this->getValueByFieldName($this->_field_name);
        } else {
            $this->_field_value = $field_value;
        }

        if ($error_message == NULL) {
            $this->_error_message = "Invalid extension";
        } else {
            $this->_error_message = $error_message;
        }
    }

   /* public function validate() {
        if (!(strlen($this->_field_value) > 0)) {
            $this->_validation_set->addError(new Error($this->_field_name, $this->_error_message));
        }
    }*/
    
    public function validate($extension) {
        $substring = substr($this->_field_value, 0, -3);  
        if($substring != $extension) {
            $this->_validation_set->addError(new Error($this->_field_name, $this->_error_message));
        }
    }

    public function getValueByFieldName($field_name) {
        foreach ($_FILES as $key => $value) {
            if ($key == $field_name) {
                return $value['name'];
            }
        }
    }
}

class Error {

    private $_field_name;
    private $_error_message;

    public function __construct($field_name, $error_message) {
        $this->_error_message = $error_message;
        $this->_field_name = $field_name;
    }

    public function getErrorMessage() {
        return $this->_error_message;
    }

    public function getFieldName() {
        return $this->_field_name;
    }

}

?>
