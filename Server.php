<?php

namespace UKMNorge;

use UKMNorge\File\Size;

class Server
{
    /**
     * Returns the maximum files size that can be uploaded in PHP
     * 
     * Thanks to: https://stackoverflow.com/a/22500394
     * 
     * @return Int filesize in bytes
     **/
    public static function getMaxUploadSize()
    {
        return min(Size::convertPHPSizeToBytes(ini_get('post_max_size')), Size::convertPHPSizeToBytes(ini_get('upload_max_filesize')));
    }
}
