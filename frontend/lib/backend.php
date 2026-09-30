<?php

//All te file paths to directory and the ini file - Spandan Patel 9/29/26
const MQ_DIR = __DIR__ . '/../../RabbitMQ';
const MQ_INI = 'testRabbitMQ.ini';

//defining requests - Spandan Ptel 9/29/26
const MQ_Routers = [
'login' => 'loginServer',
'register' => 'registerServer'];

function mq_error($message) {
return ['status' => 'error', 'message' => $message]; }


