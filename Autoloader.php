<?php
spl_autoload_register(function ($class_name) {
    #echo 'UKM AUTOLOAD: ';
    if( strpos( $class_name, 'UKMNorge\\' ) === 0 ) {
        $file = __DIR__ . str_replace(
            ['\\', 'UKMNorge'], 
            ['/', '']
            , $class_name
        ) .'.php';

        #echo ' TRY &lt;'. $class_name .'&gt; @ PATH: &lt;'. $file .'&gt;';

        if( file_exists( $file ) ) {
            #echo ' REQUIRE FILE';
            require_once( $file );
        }
        #else {
        #    echo ' FILE NOT FOUND';
        #}
    }
});
require_once('vendor/autoload.php');