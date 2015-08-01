<?php

/**
 *  Library for static testfunctions.
 *
 *  PHP version 5
 *
 * @category Library
 * @package  Global/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 *
 */



class SubTestHelper
{
    static $_subtestDirectories = array();

    static function getNewSubTestDirectory()
    {

        $newIndex = sizeof(self::$_subtestDirectories) + 1;
        $newName = "subtest_$newIndex";

        $newDirectory = self::getParentSubTestDirectory() . DIRECTORY_SEPARATOR . $newName;
        $created = mkdir($newDirectory, 0777, true);
        if (!$created) {
            throw new RuntimeException("Could not subtestDirectory: $newDirectory");
        }
        array_push(self::$_subtestDirectories, $newDirectory);
        return $newDirectory;

    }

    static function getParentSubTestDirectory()
    {
        return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "test_output";
    }

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
