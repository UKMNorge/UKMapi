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
     * @param FilmInterface $film
     * @return String html
     */
    public static function getEmbed( FilmInterface $film, String $class = null, String $style = null ) {
        if($film instanceof CloudflareFilm) {
            return '<iframe src="'. $film->getEmbedUrl() .'" allowfullscreen="true" allow="autoplay; picture-in-picture" style="height: 100%; width: 100%; position: absolute; top: 0px; left: 0px; border: none;" data-dashlane-frameid="14127"></iframe>';
        }
        return '<div class="embed-responsive embed-responsive-16by9 '. ( !is_null($class) ? $class :'' ) .' ">'
        .  '<iframe src="'. $film->getEmbedUrl() .'" '
        .  ' style="width: 100vw; height: Calc( (100vw/16)*9); max-height: 85vh; '. ( !is_null($style) ? $style :'' ) .'" '
        .  ' class="ukmtv embed-responsive-item" border="0" frameborder="0" '
        .  ' mozallowfullscreen="true" webkitallowfullscreen="true" allowfullscreen="true">'
        .  '</iframe>'
        .  '</div>';
    }
}