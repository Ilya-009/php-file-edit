<?php

class ProcessState{
    static function getState() {
        return self::getStateFromFile() === "1";
    }

    static function setState($state){
        file_put_contents("state.txt", $state === true ? "1" : "");
    }

    private static function getStateFromFile(){
        return file_get_contents("state.txt");
    }
}

$requiredState = $_GET["state"];

//Set initial state when url param state is set
if (isset($requiredState)){
    ProcessState::setState($requiredState === "1");
}else{
    ProcessState::setState(true);
}