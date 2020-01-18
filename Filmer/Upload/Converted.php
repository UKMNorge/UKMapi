<?php

namespace UKMNorge\Filmer\Upload;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Innslag\Innslag;

class Converted {

    public static function registerReportasje(
        Int $cronId,
        Arrangement $arrangement,
        String $storage_path,
        String $storage_filename
    ){
        static::setFile( 
            $cronId, 
            static::getFileWithPath($storage_path, $storage_filename)
        );
    }

    public static function registerInnslag(
        Int $cronId,
        Arrangement $arrangement,
        String $storage_path,
        String $storage_filename,
        Innslag $innslag
    ) {
        static::setFile( 
            $cronId, 
            static::getFileWithPath($storage_path, $storage_filename)
        );
        
        return 


        #$update->add('video_image', str_replace('.mp4', '.jpg', $file_with_path));
    }

    /**
     * Beregn full path til filen på lagringsserveren
     *
     * @param String $storage_path
     * @param String $storage_filename
     * @return String full path
     */
    public static function getFileWithPath( String $storage_path, String $storage_filename ) {
        return Server::STORAGE_BASEPATH . rtrim( $storage_path, '/') . '/' . $storage_filename;
    }

    /**
     * Oppdater fil-feltet i ukm_uploaded_video
     *
     * @param Int $cronId
     * @param String $fullPath
     * @return bool true
     * @throws Exception
     */
    public static function setFile( Int $cronId, String $fullPath ) {
        $query = new Update(
            'ukm_uploaded_video',
            ['cron_id' => $cronId]
        );
        $query->add('file', $fullPath);
        $res = $query->run();
        if( !$res ) {
            throw new Exception(
                'Kunne ikke oppdatere fil-parameter for cron '. $cronId,
                515003
            );
        }
        return true;
    }

    /**
     * Prøv å gjette oss frem til bilde-banen hvis lagret info er blank
     *
     * @return String url
     */
    private function _finnBildeFraFil() {
        $video = $this->getFil();
		$ext = strrpos($video, '.');
		$img = substr($video, 0, $ext).'.jpg';
		if( $this->_img_exists($img) ) {
            return $img;
        }
        return $video.'.jpg';
    }

    /**
     * Vurl videoserver for å høre om bildet finnes
     *
     * @param String $url
     * @return Bool
     */
    private function _img_exists( String $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Server::getStorageUrl() . $url);
        
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
        curl_setopt($ch, CURLOPT_USERAGENT, "UKMNorge API");
        
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $output = curl_exec($ch);
        $hd_curl_info = curl_getinfo($ch);
    
        curl_close($ch);
        return $hd_curl_info['content_type'] == 'image/jpeg';
    }
}