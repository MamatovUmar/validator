<?php
require_once 'config.php';

use libs\Validator;


$v = new Validator();

if ($v->method != 'POST') {
    http_response_code(405);
    exit;
}

// Получаем post данные
$post = $v->getPost();

// указываем поля и правила для влидации
$v->fields = [
    'foo' => ['integer', 'required'],
    'bar' => ['string', 'required'],
    'baz' => ['phone', 'required'],
    'arr' => ['array', 'integer'],
    'ass_arr' => [
        'structure',
        'fields' => [
            'one' => ['integer', 'required'],
            'two' => ['string', 'required'],
        ]
    ],
];

// загрузка данных для валидации 
$result = $v->load($post);

if ($result) {
    echo json_encode([
        'status' => true
    ]);
} else {
    echo json_encode([
        'status' => false,
        'messages' => $v->errors
    ]);
}
