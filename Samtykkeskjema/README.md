# Samtykkeskjema

Samtykkeskjema-modulen representerer et prosjekt som håndterer samtykkeskjemaer, deres versjoner, prosjekter knyttet til skjemaet, arrangementer, og tilhørende entiteter slik som bilder, filmer og innslag. Hvert skjema kan ha én eller flere versjoner, og hver versjon kan ha individuelle svar og digital signering.

## Hovedklasser

- **Samtykkeskjema**: Representerer selve skjema-prosjektet.
- **SamtykkeVersjon**: En bestemt versjon av samtykkeskjemaet (f.eks. hvis teksten endres).
- **SamtykkeProsjekt**: Prosjekter knyttet til skjemaet, evt. med tilknytning til arrangement.
- **SamtykkeSvar**: Et individuelt svar fra en deltaker/forelder på et samtykkeskjema.

## Diagram

Samtykkeskjema diagram

## Oversikt

- Et Samtykkeskjema har:
  - flere **versjoner** (`SamtykkeVersjon`)
  - flere **prosjekter** (`SamtykkeProsjekt`)
  - flere **arrangementer**
  - flere **entiteter** (koblinger til andre objekter, som `Bilde`, `Film`, eller `Innslag`)
- Hver **versjon** kan få mange **svar** (`SamtykkeSvar`), som registrerer hvilke brukere som har samtykket til teksten/informasjonen i akkurat denne versjonen.
- Det finnes også relasjons-tabeller for koblinger i databasen (`rel_samtykkeskjema_version_svar`, `rel_samtykkeskjema_arrangement` etc.)

## Typisk bruk

### Laste inn skjema

```php
use UKMNorge\Samtykkeskjema\Samtykkeskjema;

// Last inn skjema fra prosjekt med ID 1
$skjemaer = Samtykkeskjema::getByProsjektId(1);

// Hent alle versjoner fra første skjema (som eksempel)
$versjoner = $skjemaer[0]->getVersjoner();
```

### Opprette og samtykke eller avslå et svar

For å opprette et nytt svar (et svar som er åpen for å svare senere og er koblet mot en bruker) på en gitt skjema-versjon, bruk `SamtykkeSvar::createNewSamtykkeSvar`, og deretter kan brukeren enten samtykke (`samtykk()`) eller avslå (`avsla()`). Dette må gjøres på det enkelte svaret, ikke direkte på Samtykkeskjema.

```php
use UKMNorge\Samtykkeskjema\SamtykkeSvar;

// Opprett et nytt SamtykkeSvar for versjon med ID $versionId og bruker $userId
// $isForesatt = true dersom det er en foresatt som svarer på vegne av en deltaker
$svar = SamtykkeSvar::createNewSamtykkeSvar($versionId, $userId, $isForesatt = false);

// Gi samtykke med valgfri streng (f.eks. 'ja') og IP-adresse
$svar->samtykk('ja', $userId, $ipAddress = '192.168.0.1');

// ...eller avslå samtykke (svar settes automatisk til 'nei')
$svar->avsla($userId, $ipAddress = '192.168.0.1');
```

Begge metodene returnerer `SamtykkeSvar`-objektet (fluent interface) og oppdaterer objektets in-memory tilstand (`svar`, `ipAddress`) i tillegg til databasen.

Merk: Svaret kan kun gis én gang per svarobjekt — det kan ikke overskrives. Begge metodene kaster `Exception` hvis svaret allerede er registrert.

### Tilgjengelige gettere på SamtykkeSvar

| Metode              | Beskrivelse                                         |
|---------------------|-----------------------------------------------------|
| `getId()`           | ID til svaret                                       |
| `getVersionId()`    | ID til skjema-versjonen svaret tilhører             |
| `getSvar()`         | Brukerens svar (`'ja'`, `'nei'`, eller tomt)        |
| `getIpAddress()`    | IP-adressen registrert ved svartidspunkt            |
| `getUser()`         | Bruker-ID knyttet til svaret                        |
| `getsif()`          | Tidsstempel for oppretting (`created_at`)           |
| `isSigned()`        | Om svaret er digitalt signert (`bool`)              |
| `getSignedMethod()` | Signeringsmetode: `'delta'`, `'arrsys'` eller null  |
| `isForesatt()`      | Om svaret er avgitt av en foresatt (`bool`)         |

## Relaterte filer

- `Samtykkeskjema.php` – hovedklasse
- `SamtykkeVersjon.php` – håndterer en enkelt versjon med svar
- `SamtykkeSvar.php` – individuelt svar
- `SamtykkeProsjekt.php` – prosjekt-tilknytning

## Teknisk

- Bruker UKM sin Query-klasse for all database-håndtering.
- Klassene kan både konstrueres fra rad-array (fra database) eller ID.
- Alle relasjoner og data behandles som objektorienterte modeller.

## Diagram

![Samtykkeskjema databasemodell](docs/samtykkeskjema_DB.drawio.png?raw=true)

```
Samtykkeskjema
  ├─ SamtykkeProsjekt*
  ├─ Arrangement*
  ├─ SamtykkeVersjon*
  │     └─ SamtykkeSvar*
  └─ Entiteter (Bilde/Film/Innslag)*
```
