<?php

namespace UKMNorge\Arrangement\Program;

interface HendelseItemInterface {
    public function getId();
    public function getNavn();
    public function getItemType() : string;
}
