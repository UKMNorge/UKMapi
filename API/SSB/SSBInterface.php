<?php

namespace UKMNorge\API\SSB;

interface SSBInterface {

	const API_URL = 'http://data.ssb.no/api/v0/no/';

	# Denne funksjonen velger hvilken SSB-ressurs spørringen skal kjøre mot (oftest en tabell).
	# Argument må være på formen 'ressurs/ressurs-id', ie 'table/04231' for Levendefødte.
	public function setResource($resource);

	# Dette er funksjonen som kjører spørringen mot SSBs systemer.
	public function run();

	# Dette er funksjonen som returnerer selve spørringen som et JSON-objekt.
	# Kan echoes ut for debugging.
	public function query();
}