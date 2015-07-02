<?php

/**
 *  Library for static testfunctions.
 * 
 *  PHP version 5
 *  
 *  @category Library
*   @package  Global/
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     TODO
 *  
 */


/**
 * Reads all files of the working directory.
 * 
 * @return multitype: Array containing all files of the working directory.
 */

function getAllFilesInCurrentDirectory()
{
    $dhandle = opendir(getcwd());
    $arr_contents = array();
    
    while ($entry = readdir($dhandle)) {
        if ($entry == "." || $entry == "..") {
            continue;
        }
        array_push($arr_contents, $entry);
    }
    closedir($dhandle);
    return $arr_contents;
}

/**
 * Removes all files in a directory recursively 
 * and removes also the directory itself.
 * 
 * @param unknown $dir the directory to remove recursively.
 * 
 * @return nothing
 */

function rmr($dir)
{
   
    if (is_dir($dir)) {

        $dircontent = scandir($dir);
        foreach ($dircontent as $c) {

            if ($c != '.' && $c != '..' && is_dir($dir . DIRECTORY_SEPARATOR . $c)) {
                rmr($dir . DIRECTORY_SEPARATOR . $c);
            } else if ($c != '.' && $c != '..') {
                    unlink($dir . DIRECTORY_SEPARATOR . $c);
            }
        }
        rmdir($dir);
    } else if (file_exists($dir)) {
            unlink($dir);
    }
}




