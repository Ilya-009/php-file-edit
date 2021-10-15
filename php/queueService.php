<?php

$files = $_POST["files"];
$commands = $_POST["commands"];

if (isset($files)){

    if (file_get_contents('files.txt') !== '')
        file_put_contents('files.txt', ',' . $files, FILE_APPEND);
    else
        file_put_contents('files.txt', $files);

    file_put_contents('commands.txt', $commands);
}