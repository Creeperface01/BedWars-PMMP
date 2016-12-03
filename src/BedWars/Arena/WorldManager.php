<?php

namespace BedWars\Arena;

class WorldManager
{

    public static function addWorld($worldname, $path)
    {
        $imp = \explode("_bw", $worldname)[0];
        $source = dirname($path) . "/worlds/bedwars/" . $imp . "/";
        $dest = $path . "worlds/" . $worldname ."/";

        $count = 0;

        self::xcopy($source, $dest);
    }

    public static function deleteWorld($worldname, $path)
    {
        // delete folder
        $levelpath = $path . "worlds/" . $worldname ."/";
        self::unlinkRecursive($levelpath, true);
    }

    public static function xcopy($source, $dest, $permissions = 0755)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_file($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            self::xcopy("$source/$entry", "$dest/$entry", $permissions);
        }

        // Clean up
        $dir->close();
        return true;
    }

    public static function unlinkRecursive($dir, $deleteRootToo)
    {
        if (!$dh = @opendir($dir)) {
            return;
        }
        while (false !== ($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') {
                continue;
            }

            if (!@unlink($dir . '/' . $obj)) {
                self::unlinkRecursive($dir . '/' . $obj, true);
            }
        }

        closedir($dh);

        if ($deleteRootToo) {
            @rmdir($dir);
        }

        return;
    }

    public static function resetWorld($world, $dataPath)
    {
        self::deleteWorld($world, $dataPath);
        self::addWorld($world, $dataPath);
    }
}

