<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Filmer\UKMTV\Server\Server;

class Html {

    /**
     * Hent filmens metadata
     *
     * @param Film $film
     * @return String html
     */
    public static function getMeta( Film $film ) {
        $html = '';
        foreach( $film->getMeta() as $key => $val ) {
            $html .= '<meta property="'. $key .'" content="'. $val .'">';
        }
        $html .=
            '<link rel="alternate" type="application/json+oembed"'.
            ' href="'. Server::getOembedUrl() . '?url='.urlencode($film->getUrl()).'" '.
            ' title="UKM-TV oEmbed" />';

        return $html;
    }

    /**
     * Hent filmens embedkode (iframe)
     *
     * @param Film $film
     * @return String html
     */
    public static function getEmbed( Film $film, String $class = null, String $style = null ) {
        return '<div class="embed-responsive embed-responsive-16by9 '. ( !is_null($class) ? $class :'' ) .' ">'
        .  '<iframe src="'. $film->getEmbedUrl() .'" '
        .  ' style="width: 100vw; height: Calc( (100vw/16)*9); max-height: 85vh; '. ( !is_null($style) ? $style :'' ) .'" '
        .  ' class="ukmtv embed-responsive-item" border="0" frameborder="0" '
        .  ' mozallowfullscreen="true" webkitallowfullscreen="true" allowfullscreen="true">'
        .  '</iframe>'
        .  '</div>';
    }
}