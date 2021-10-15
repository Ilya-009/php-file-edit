<?php

//Include services
require_once "setProcessState.php";

while(true){
    $files = file_get_contents('files.txt');
    $commands = file_get_contents('commands.txt');

    if ($files === '' || $commands === ''){
        makeDelay();
        continue;
    }

    $commands = json_decode($commands);

    $filesList = explode(',', $files);
    $file = $filesList[0];

    $extension = explode('.', $file)[1];

    switch ($extension){
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            editImage($file, $extension, $commands);
            break;
        case 'txt':
            editDocument($file, $commands);
            break;
    }

    if (ProcessState::getState() === false){
        clearFileQueue();
        clearCommandsQueue();
        continue;
    }

    removeFileFromQueue($files, $file);

    //Clear everything left when edit process is over
    if (count($filesList) <= 1){
        clearFileQueue();
        clearCommandsQueue();

        ProcessState::setState(false);

        makeDelay();
        file_put_contents('message.txt','end');
    }

    makeDelay();
}

function editImage($image, $extension, $commands){
    $fileFullPath = '../files/' . $image;

    foreach ($commands as $command){
        $commandIndex = $command->{"number"};
        $commandValue = $command->{"value"};

        //Check if command not for image
        if ($commandIndex < 3) continue;

        switch ($commandIndex){
            case 3:
                makeDelay();
                $imageSizeParams = explode(',', $commandValue);//Required image size (a,b from user input)
                resizeImage($image, $fileFullPath, $imageSizeParams[1], $imageSizeParams[0]);
                break;
            case 4:
                makeDelay();
                $cropSizes = explode(',', $commandValue);//Sizes of new cropped image
                cropImage($image, $fileFullPath, $extension, $cropSizes[1], $cropSizes[0]);
                break;
            case 5:
                makeDelay();
                convertToPng($image, $fileFullPath);
                break;
        }
    }
}

function editDocument($file, $commands){
    $fileFullPath = '../files/' . $file;

    foreach ($commands as $command){
        $commandIndex = $command->{"number"};
        $commandValue = $command->{"value"};

        //Check if command not for image
        if ($commandIndex > 2) continue;

        switch ($commandIndex){
            case 0:
                makeDelay();
                removeSubstringFromFile($fileFullPath, $commandValue);
                break;
            case 1:
                makeDelay();
                pushTextIntoFile($fileFullPath, $commandValue);
                break;
            case 2:
                makeDelay();
                clearFileContent($fileFullPath);
                break;
        }
    }
}

//-----------Start of images editing scripts---------------//
function convertToPng($fileName, $filePath){
   imagepng(imagecreatefromstring(file_get_contents($filePath)), '../files/' . explode(".", $fileName)[0] . '.png');
   unlink($filePath);

   file_put_contents('message.txt', "Преобразование изображения $fileName на png успешно выполнено!");
}

function resizeImage($fileName, $filePath, $height=null, $width = null){
    $imageSource = imagecreatefromstring(file_get_contents($filePath));

    list($imageSourceWidth, $imageSourceHeight) = getimagesize($filePath);

    //Check input params
    if ($height == null){
        $height = $imageSourceHeight;
    }
    if ($width == null){
        $width = $imageSourceWidth;
    }

    //Empty canvas creating
    $resized = imagecreatetruecolor($width, $height); // SMALLER BY 50%

    //Resize image
    imagecopyresampled($resized, $imageSource, 0, 0, 0, 0, $width, $height, $imageSourceWidth, $imageSourceHeight);

    //Save new image
    imagejpeg($resized, $filePath);

    //Clean up unused data
    imagedestroy($imageSource);
    imagedestroy($resized);

    //Send message to frontend
    file_put_contents('message.txt', "Изменение размеров картинки $fileName на {$height}x{$width}");
}

function cropImage($fileName, $imagePath, $extension, $cropHeight, $cropWidth){
    $imageSource = imagecreatefromstring(file_get_contents($imagePath));
    $croppedImage = imagecrop($imageSource, ['x' => 0, 'y' => 0, 'width' => intval($cropWidth), 'height' => intval($cropHeight)]);

    if ($croppedImage !== FALSE){
         switch ($extension){
             case "jpg":
             case "jpeg":
                 imagejpeg($croppedImage, $imagePath);
                 break;
             case "png":
                 imagepng($croppedImage, $imagePath);
                 break;
             case "gif":
                 imagegif($croppedImage, $imagePath);
                 break;
         }

         imagedestroy($croppedImage);
         imagedestroy($imageSource);

         file_put_contents('message.txt', "Обрезка картинки $fileName выполнена успешно!");
     }else{
         file_put_contents('message.txt', "Ошибка при обрезке картинки!");
     }
}

//----------------End of image editing functions--------------------//


//----------------Start of document editing functions--------------------//
function removeSubstringFromFile($fileName, $char){
    $fileContent = file_get_contents($fileName);
    $editedContent = str_replace($char, "", $fileContent);
    file_put_contents($fileName, $editedContent);

    makeDelay();
    file_put_contents('message.txt', "Удаление символа '$char' из файла $fileName выполнено успешно!");
}

function pushTextIntoFile($filePath, $textContent){
    //Set content with append type
    file_put_contents($filePath, $textContent, FILE_APPEND);
    $fileName = basename($filePath);

    makeDelay();
    file_put_contents('message.txt', "Добавление текста в файл $fileName выполнено успешно!");
}

function clearFileContent($filePath){
    file_put_contents($filePath, "");
    $fileName = basename($filePath);

    makeDelay();
    file_put_contents('message.txt', "Очищение файла $fileName выполнено успешно!");
}
//----------------Start of document editing functions--------------------//

function makeDelay(){
    sleep(1);
}

function removeFileFromQueue($files, $file){
    $newFiles = str_replace($file . ',', '', $files);
    file_put_contents('files.txt', $newFiles);
}

function clearFileQueue(){
    file_put_contents('files.txt', '');
}

function clearCommandsQueue(){
    file_put_contents('commands.txt', '');
}