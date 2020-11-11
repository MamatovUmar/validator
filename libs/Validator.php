<?php

namespace libs;


class Validator
{

    public $get = [];
    public $method;
    public $fields;
    public $errors = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Получаем пост данные
     */
    public function getPost()
    {
        if (!empty($_POST)) {
            return $_POST;
        }
        $post = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $post;
        }
        return [];
    }

    /**
     * Правила для валидации
     */
    private function rules()
    {
        return [
            'string' => [
                'regexp' => '/\w+/',
                'message' => ' не является строкой'
            ],
            'required' => [
                'regexp' => '/[\s\S]/',
                'message' => 'Поле, обязательное для заполнения'
            ],
            'integer' => [
                'regexp' => '/^-?\d+$/',
                'message' => ' не является целым числом'
            ],
            'float' => [
                'regexp' => '/[+-]?([0-9]*[.])?[0-9]+/',
                'message' => ' не является числой с плавающей точкой'
            ],
            'phone' => [
                'regexp' => '/^((8|\+7))?(\(?\d{3}\)?)?[\d\- ]{7}$/', // пример:+7(950)2885633 или 8(950)2885633
                'message' => ' не правильный формат номера телефона'
            ],
            'array' => [
                'message' => ' не является массивом'
            ],
            'structure' => [
                'message' => ' не является структурой'
            ],

        ];
    }


    /**
     * Функция для валидации
     * 
     * @param $field - проверяемая значение
     * @param $type - тип данных
     */

    public function validate($field, $type)
    {
        if (empty($field) && $type != 'required') {
            return [
                'status' => true,
            ];
        }

        $rules = $this->rules();
        if (!isset($rules[$type])) {
            return [
                'status' => false,
                'message' => 'Неизвестный тип данных'
            ];
        }

        $rule = $rules[$type];
        $result = true;

        switch ($type) {
            case 'array':
                $result = is_array($field);
                break;

            default:
                if (is_array($field)) {

                    $new_field = preg_grep($rule['regexp'], $field);
                    $result = count($new_field) == count($field);
                } else {

                    $result = preg_match($rule['regexp'], $field, $match);
                }
                break;
        }


        if ($result) {
            return [
                'status' => true,
            ];
        }
        return [
            'status' => false,
            'message' => $field . $rule['message']
        ];
    }



    /**
     * Загрузка данных
     * 
     * @param $data - данные для проверки
     */
    public function load($data)
    {
        $fields = $this->fields;

        foreach ($data as $field => $value) {

            if (isset($fields[$field])) {

                foreach ($fields[$field] as $rule) {
                    if (is_array($rule)) continue;


                    if ($rule == 'structure') {
                        $res = $this->structureValidate($value, $fields[$field]);
                    } else {
                        $res = $this->validate($value, $rule);
                    }

                    if (!$res['status']) {
                        $this->errors[$field] = $res['message'];
                        break;
                    }
                }
            }
        }

        return count($this->errors) ? false : true;
    }

    public function structureValidate($value, $rules)
    {
        $result = $this->is_structure($value);
        if ($result) {
            if (isset($rules['fields'])) {
                $v = new Validator();
                $v->fields = $rules['fields'];
                $is_valid = $v->load($value);
                if (!$is_valid) {
                    return [
                        'status' => false,
                        'message' => $v->errors
                    ];
                }
            }
            return [
                'status' => true
            ];
        }
        return [
            'status' => false,
            'message' => 'Поле не является структурой'
        ];
    }

    public function is_structure($field)
    {
        if (array() === $field) return false;
        return array_keys($field) !== range(0, count($field) - 1);
    }
}
