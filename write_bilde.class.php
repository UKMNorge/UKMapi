<?php
require_once('UKM/logger.class.php');
require_once('UKM/innslag.class.php');

class write_bilde {
    
    /**
     * Slett gitt bilde
     *
     * @param Bilde $bilde
     * @param Integer $blog_id
     * @return Bool $success
     */
	public static function delete( $bilde, $blog_id ) {
        if( !is_object( $bilde ) || get_class( $bilde ) !== 'bilde' ) {
            throw new Exception(
                'Delete krever bilde-objekt som parameter 1',
                514001
            );
        }
        
        if( !is_numeric( $blog_id ) ) {
            throw new Exception(
                'Delete krever numerisk blogg id som parameter 2',
                514002
            );
        }



		$del = new SQLdel('ukmno_wp_related',
							  array('blog_id'=>$blog_id,
							  		'post_id'=>$bilde->getPostId(),
							  		'post_type'=>'image')
							  );
        $res = $del->run();
        // Res kan være andre ting enn bool:true, men vil evaluere til true hvis suksess    
        if( $res ) {
            return true;
        }
        return false;
    }
}