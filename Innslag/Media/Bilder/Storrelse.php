<?php

namespace UKMNorge\Innslag\Media\Bilder;

use Exception;

class Storrelse
{
    var $file = null;
    var $width = null;
    var $height = null;
    var $mimetype = null;
    var $basepath = null;
    var $path_internal = null;
    var $path_external = null;

    public function __construct(array $bildedata)
    {
        if (!defined('UKM_HOSTNAME')) {
            throw new Exception(
                'Bilde-størrelse krever UKM_HOSTNAME',
                132005
            );
        }
        $this->basepath =  UKM_HOSTNAME == 'ukm.dev' ?
            '/var/www/wordpress/' : '/home/ukmno/public_html/';

        $this->setFile($bildedata['file']);
        $this->setWidth((float) $bildedata['width']);
        $this->setHeight((float) $bildedata['height']);
        if (isset($bildedata['mime-type'])) {
            $this->setMimeType($bildedata['mime-type']);
        }
        $this->setInternalPath($bildedata['path_int']);
        $this->setExternalPath($bildedata['path_ext']);
    }

    /**
     * Sett filbane (relativ)
     *
     * @param String file
     *
     * @return self
     **/
    public function setFile(String $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Hent filbane (relativ)
     *
     * @return String file
     **/
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sett bildebredde (px)
     *
     * @param Float width
     * @return self
     **/
    public function setWidth(Float $width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Hent bildebredde (px)
     *
     * @return Float bredde
     **/
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Hent bildets orientering
     *
     * @return String portrait|landscape
     **/
    public function getOrientation()
    {
        if ($this->getWidth() < $this->getHeight()) {
            return 'portrait';
        }
        return 'landscape';
    }
    /**
     * Sett bildehøyde (px)
     *
     * @param Float høyde
     * @return self
     **/
    public function setHeight(Float $height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Hent bildehøyde (px)
     *
     * @return Float høyde
     **/
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Sett mimetype
     *
     * @param String mimetype
     * @return self
     **/
    public function setMimeType(String $mimetype)
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    /**
     * Hent mimetype
     *
     * @return String mimetype
     **/
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * Sett external path (URL-base)
     *
     * @param String external path
     * @return self
     **/
    public function setExternalPath(String $path)
    {
        $this->path_external = str_replace(
            'http:',
            'https:',
            rtrim($path, '/') . '/'
        );
        return $this;
    }

    /**
     * Hent external path (URL-base)
     *
     * @return String url-base
     **/
    public function getExternalPath()
    {
        return $this->path_external;
    }

    /**
     * Sett internal path (path-base)
     *
     * @param String internal path
     * @return self
     **/
    public function setInternalPath(String $path)
    {
        $this->path_internal = rtrim($path, '/') . '/';
        return $this;
    }

    /**
     * Hent internal path (path-base)
     *
     * @return String path-base
     **/
    public function getInternalPath()
    {
        return rtrim($this->basepath, '/') . '/' . $this->path_internal;
    }

    /**
     * Hent full URL
     *
     * @return String url
     **/
    public function getUrl()
    {
        return $this->getExternalPath() . $this->getFile();
    }

    /**
     * Hent full filbane
     *
     * @return String filbane
     **/
    public function getPath()
    {
        return $this->getInternalPath() . $this->getFile();
    }
}
