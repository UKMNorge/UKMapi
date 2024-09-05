<?php

namespace UKMNorge\API\SSB;

interface KlassInterface {

	const API_URL = 'https://data.ssb.no/api/klass/v1/classifications/';

	# Denne funksjonen velger hvilken SSB-ressurs spørringen skal kjøre mot (oftest en tabell).
	# Argument må være på formen 'ressurs/ressurs-id', ie 'table/04231' for Levendefødte.
	public function setClassificationId($classificationId);

	# Datasettene er sortert etter codes
	public function getCodes($debug = false);

	# Henter alle endringer i datasettet i gitt range.
	public function getChanges($debug = false);

	# Dette er funksjonen som kjører spørringen mot SSBs systemer.
	public function run();

}