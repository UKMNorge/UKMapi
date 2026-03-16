# "Samtykkeskjema

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
$skjema = new Samtykkeskjema::getByProsjektId(1);

// Hent alle versjoner
$versjoner = $skjema->getVersjoner();
```

### Gi samtykke på et samtykkeskjema

```php
$skjema->giSamtykke(1, false, '192.168.0.1');
$skjema->giSamtykkeTilSisteVersjon(1, false, '192.168.0.1')
```

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