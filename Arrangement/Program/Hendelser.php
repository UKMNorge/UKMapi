<?php

namespace UKMNorge\Arrangement\Program;

use Exception;
use stdClass;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Context\Context;

require_once('UKM/Autoloader.php');

class Hendelser {
    var $context = null;
    var $loaded = false;
    var $forestillinger = null;
    var $skjulte_forestillinger = null;
    var $containerType = null;
    var $containerId = null;
    var $rekkefolge = null;

    var $container_pl_id = null; // Brukes av container_type 'innslag'

    public function __construct($context)
    {
        $this->context = $context;

        if (!in_array($context->getType(), ['innslag', 'monstring'])) {
            throw new Exception('FORESTILLINGER: Støtter kun context innslag eller mønstring, ikke ' . $context->getType());
        }
        $this->rekkefolge = [];
    }

    public function getContext()
    {
        return $this->context;
    }

    public static function sorterPerDag($forestillinger)
    {
        $sortert = [];
        foreach ($forestillinger as $forestilling) {
            $key = $forestilling->getStart()->format('d_m');
            if (!isset($sortert[$key])) {
                $dag = new stdClass();
                $dag->key    = $key;
                $dag->date     = $forestilling->getStart();
                $dag->forestillinger = [];
                $sortert[$key] = $dag;
            }
            $sortert[$key]->forestillinger[] = $forestilling;
        }
        ksort($sortert);
        return $sortert;
    }

    public static function filterByDato($timestamp, $forestillinger)
    {
        $filtrert = [];
        foreach ($forestillinger as $forestilling) {
            if ($forestilling->getStart()->format('d_m') == $timestamp->format('d_m')) {
                $filtrert[] = $forestilling;
            }
        }
        return $filtrert;
    }

    public function get($id)
    {
        if ( Hendelse::validateClass($id)) {
            $id = $id->getId();
        }
        foreach ($this->getAbsoluteAll() as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }

        throw new Exception('Kunne ikke finne hendelse ' . $id . '.', 2); // OBS: code brukes av har()
    }

    /**
     * Er innslaget med i hendelsen
     *
     * @param object person
     * @return boolean
     **/
    public function har($hendelse)
    {
        try {
            $this->get($hendelse);
            return true;
        } catch (Exception $e) {
            if ($e->getCode() == 2) {
                return false;
            }
            throw $e;
        }
    }


    public function getAntall()
    {
        return sizeof($this->getAll());
    }

    public function getAntallSkjulte()
    {
        return sizeof($this->getAllSkjulte());
    }

    public function getAll()
    {
        $this->_load();
        return $this->forestillinger;
    }

    public function getAllSkjulte()
    {
        $this->_load();
        return $this->skjulte_forestillinger;
    }

    public function getAllInterne()
    {
        $this->_load();
        return $this->interne_forestillinger;
    }

    public function getAllInkludertInterne()
    {
        $alle = [];
        if (is_array($this->getAll())) {
            foreach ($this->getAll() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        if (is_array($this->getAllInterne())) {
            foreach ($this->getAllInterne() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        ksort($alle);
        return $alle;
    }

    public function getAllInkludertSkjulte()
    {
        $alle = [];
        if (is_array($this->getAll())) {
            foreach ($this->getAll() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        if (is_array($this->getAllSkjulte())) {
            foreach ($this->getAllSkjulte() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        ksort($alle);
        return $alle;
    }

    /**
     * @return Hendelse[]
     */
    public function getAbsoluteAll()
    {
        $alle = [];
        if (is_array($this->getAll())) {
            foreach ($this->getAll() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        if (is_array($this->getAllInterne())) {
            foreach ($this->getAllInterne() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        if (is_array($this->getAllSkjulte())) {
            foreach ($this->getAllSkjulte() as $hendelse) {
                $alle[$hendelse->getStart()->getTimestamp() . '-' . $hendelse->getId()] = $hendelse;
            }
        }

        ksort($alle);
        return $alle;
    }



    public function getIdArray($method = 'getAll')
    {
        if (!in_array($method, array('getAll', 'getAllSkjulte', 'getAllInkludertSkjulte'))) {
            throw new Exception('PROGRAM: getIdArray fikk ugyldig metode-kall (' . $method . ')');
        }
        $idArray = [];
        foreach ($this->$method() as $hendelse) {
            $idArray[] = $hendelse->getId();
        }
        return $idArray;
    }

    public function _load()
    {
        if ($this->loaded) {
            return true;
        }

        $this->forestillinger = []; // Alle synlige (ikke interne) forestillinger
        $this->skjulte_forestillinger = []; // Alle skjulte (interne + ikke interne) forestillinger
        $this->interne_forestillinger = []; // Alle synlige, men interne forestillinger

        $SQL = $this->_getQuery();
        #		echo $SQL->debug();
        $res = $SQL->run();
        if (!$res) {
            return array();
        }
        while ($row = Query::fetch($res)) {
            $forestilling = new Hendelse($row);

            $context = Context::createForestilling(
                (Int) $row['c_id'],                     // Forestilling Id
                $this->getContext()->getMonstring()     // Mønstring-context
            );
            $forestilling->setContext($context);
            if ($forestilling->erSynligRammeprogram()) {
                if ($forestilling->erIntern()) {
                    $this->interne_forestillinger[] = $forestilling;
                } else {
                    $this->forestillinger[] = $forestilling;
                }
            } else {
                $this->skjulte_forestillinger[] = $forestilling;
            }
            if ('innslag' == $this->getContext()->getType()) {
                $this->setRekkefolge($forestilling->getId(), $row['order']);
            }
        }
        $this->loaded = true;
        return true;
    }

    public function setRekkefolge($forestilling_id, $order)
    {
        $this->rekkefolge[$forestilling_id] = $order;
        return $this;
    }
    public function getRekkefolge($forestilling)
    {
        if (is_numeric($forestilling)) {
            $id = $forestilling;
        } else {
            $id = $forestilling->getId();
        }

        if (!isset($this->rekkefolge[$id])) {
            throw new Exception('Innslaget er ikke med i denne hendelsen! (' . $id . ')');
        }
        return $this->rekkefolge[$id];
    }

    private function _getQuery()
    {
        switch ($this->getContext()->getType()) {
            case 'monstring':
                if (null == $this->getContext()->getMonstring()->getId()) {
                    throw new Exception('FORESTILLINGER: Krever MønstringID (containerId) for å hente mønstringens program');
                }

                return new Query(
                    "SELECT *
						    	 FROM `smartukm_concert` 
						    	 WHERE `pl_id` = '#pl_id'
						    	 ORDER BY #order ASC",
                    array(
                        'pl_id' => $this->getContext()->getMonstring()->getId(),
                        'order' => 'c_start'
                    )
                );
            case 'innslag':
                if (null == $this->getContext()->getMonstring()->getId()) {
                    throw new Exception('FORESTILLINGER: Krever MønstringID for å hente innslagets program');
                }
                return new Query(
                    "SELECT `concert`.*,
								`relation`.`order`
								FROM `smartukm_concert` AS `concert`
								JOIN `smartukm_rel_b_c` AS `relation`
									ON (`relation`.`c_id` = `concert`.`c_id`)
								WHERE `concert`.`pl_id` = '#pl_id'
								AND `relation`.`b_id` = '#b_id'
								ORDER BY `c_start` ASC",
                    array('b_id' => $this->getContext()->getInnslag()->getId(), 'pl_id' => $this->getContext()->getMonstring()->getId())
                );

            default:
                throw new Exception('FORESTILLINGER: Har ikke støtte for ' . $this->getContext()->getType() . '-collection (#2)');
        }
    }
}
