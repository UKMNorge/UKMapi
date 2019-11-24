<?php

namespace UKMNorge\Innslag\Media\Bilder;

use UKMNorge\Database\SQL\Delete;

class Write {
    
    /**
     * Slett gitt bilde
     *
     * @param Bilde $bilde
     * @param Integer $blog_id
     * @return Bool $success
     */
	public static function delete( Bilde $bilde, Int $blog_id ) {
        Bilde::validateClass( $bilde );
        
        if( !is_numeric( $blog_id ) ) {
            throw new Exception(
                'Delete krever numerisk blogg id som parameter 2',
                514002
            );
        }

		$del = new Delete(
            'ukmno_wp_related',
			[
                'blog_id'=>$blog_id,
                'post_id'=>$bilde->getPostId(),
                'post_type'=>'image'
            ]
        );
        $res = $del->run();
        // Res kan være andre ting enn bool:true, men vil evaluere til true hvis suksess    
        if( $res ) {
            return true;
        }
        return false;
    }
}