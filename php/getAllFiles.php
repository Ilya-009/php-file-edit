<?php

function getAllFilesFromServer(){
    $filesFolder = "../files/";
    $records = scandir($filesFolder);
    $responseData = array();

    foreach ($records as $file){
        if($file == "." || $file == "..") continue;

        $extension = explode(".", $file)[1];

        $fileFullPath = $filesFolder . "/" . $file;

        switch ($extension){
            case "txt":
                array_push($responseData, [
                    "name" => $file,
                    "size" => filesize($fileFullPath),
                    "content" => substr(file_get_contents($fileFullPath), 0, 20)
                ]);
                break;
            case "jpg":
            case "jpeg":
            case "png":
                $imageSize = getimagesize($fileFullPath);

                array_push($responseData, [
                    "name" => $file,
                    "size" => filesize($fileFullPath),
                    "resolution" => "$imageSize[0] x $imageSize[1]"
                ]);

                break;
        }
    }

    //Convert to JSON for frontend
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
}

getAllFilesFromServer();