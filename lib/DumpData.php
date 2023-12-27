<?php

function dd($var)
{
    echo '<pre>';
        var_dump($var);
    echo '</pre>';
    exit(0);
}

function dump($var)
{
    echo '<pre>';
        var_dump($var);
    echo '</pre>';
}

function to_json($var)
{
    return json_encode($var);
}