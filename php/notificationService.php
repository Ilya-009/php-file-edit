<?php

class Notification{
    static function showMessage(){
        $savedMessage = self::getMessageFromFile();

        if ($savedMessage === ""){
            return;
        }
        echo $savedMessage;

        self::createOrClearFile();
    }

    static function getMessageFromFile(){
        return file_get_contents("message.txt");
    }

    private static function createOrClearFile(){
        file_put_contents("message.txt", "");
    }
}

while(Notification::getMessageFromFile() === '') {}

Notification::showMessage();
