<?php

require_once __DIR__ . '/../vendor/autoload.php';

\BladeOrm\Query\SqlBuilder::setEscapeMethod(function($value){
    return str_replace("'", "''", $value);
});
