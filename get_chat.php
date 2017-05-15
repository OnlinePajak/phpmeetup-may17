<?php
    //get chat
    $myfile = 'chat_log.txt';
    $handle = fopen($myfile, 'r');

    $filesize = filesize($myfile);

    if ($filesize > 0) {
        $lines = fread($handle, $filesize);

        $handle = fopen($myfile, 'w') or die('Cannot open file:  '.$myfile);
        fwrite($handle, '');

        $lines = explode("\n",$lines);
        $arr = array();
        foreach ($lines as $line){
            $arr[] = json_decode($line, true);
        }
        array_pop($arr); //exclude \n at the last row
        $data = json_encode($arr);

        echo $data;
    } else {
        echo "";
    }
