UKMapi
======

## For å bruke biblioteket
Alle våre servere har `/etc/php-includes/` definert i php include path, og i det er i denne mappen du vanligvis finner dette repoet, klonet inn i mappen `UKM`. 
Autoloaderen finner du derfor her:
```php
require_once('UKM/Autoloader.php');
```

Config-konstanter (env) finner du her
```php
require_once('UKMconfig.inc.php');
```


## Exceptions:
API V2 skal kaste kodede exceptions. For exceptions benyttes følgende struktur og tabell for å genere sekssifret unik error-kode
`[ les|skriv {1|5} ] [ objekt {xx} ] [ action {yyy} ]`

prefix | objekt | les | skriv
------------ | ------------- | ------------ | -------------
 x | SQL | - | 901yyy 
1 | Mønstring | 101yyy | 501yyy
1 | Arrangement/Arrangement | 101yyy | 501yyy
2 | Kommune | 102yyy | 502yyy
3 | Fylke | 103yyy | 503yyy
4 | InnslagCollection | 104yyy | 504yyy
5 | Innslag | 105yyy | 505yyy
6 | PersonCollection | 106yyy | 506yyy
7 | Person | 107yyy | 507yyy
8 | TitlerCollection | 108yyy | 508yyy
9 | Tittel | 109yyy | 509yyy
10 | _collection | 110yyy | 511yyy
11 | Kontaktperson | 111yyy | 511yyy
12 | Context | 112yyy | -
13 | Samtykke | 113yyy | -
14 | Bilde | 114yyy | 514yyy
15 | Film | 115yyy | 515yyy
16 | Post | 116yyy | 516yyy
17 | Sensitivt | 117yyy | -
18 | SensitivtRequester | 118yyy | - 
19 | Intoleranse | 119yyy | -
20 | Hendelse/forestilling | 120yyy | 520yyy
21 | HendelseCollection | 121yyy | 521yyy
22 | Nominasjon | 122yyy | 522yyy
23 | Meta | 123yyy | 523yyy
30 | Innslag/Type | 130yyy | - 
31 | Media/Artikkel | 131yyy | -
32 | Media/Bilde | 132yyy | -
33 | Innslag/Playback | 133yyy | 533yyy
34 | Kommunikasjon/Reservasjon | 134yyy | - 
41 | Fil/Excel | 141yyy | - 
42 | Kommunikasjon/Epost | 142yyy | - 
43 | Filmer/UKMTV | 143yyy | 543yyy
44 | Filmer/UKMTV/Direkte | 144yyy | 544yyy
45 | Some/Forslag | 145yyy | 545yyy
46 | Some/Kanaler | 146yyy | 546yyy
47 | Some/Kanaler | 147yyy | 547yyy
48 | Kommunikasjon/SMS | 148yyy | - 
49 | Kommunikasjon/Mottaker | 149yyy | - 
50 | Arrangement/Arrangementer | 150yyy | -
51 | Arrangement/Skjema/Skjema | 151yyy | 551yyy
52 | Arrangement/Skjema/Sporsmal | 152yyy | -
53 | Arrangement/Skjema/SvarSett | 153yyy | -
54 | Arrangement/Skjema/Svar | 154yyy | -  
55 | Arrangement/Videresending/Videresending | 155yyy | - 
56 | Arrangement/Videresending/Videresender | 156yyy | - 
57 | Arrangement/Videresending/Mottaker | 157yyy | - 
58 | Arrangement/Videresending/Avsender | 158yyy | - 
59 | Arrangement/Eier | 159yyy | - 
60 | Arrangement/Videresending/Ledere | 160yyy | 560yyy
61 | Nettverk/Administrator | 161yyy | 561yyy
62 | Nettverk/Omrade | 162yyy | 562yyy
71 | Wordpress/User | 171yyy | 571yyy
72 | Wordpress/Blog | 172yyy | 572yyy
73 | Wordpress/Modul | 173yyy | 573yyy
81 | Slack | 181yyy | 581yyy
82 | Mailchimp | - | 582yyy


# Wordpress-options
Disse variablene er satt på wordpress-bloggene, for å enklere
kunne angi hvilken funksjonalitet de ulike bloggene skal ha.

Bruk `get_option( $navn )` i wordpress.

Navn | Returnerer | Beskrivelse
--- | --- | ---
**pl_id** | Bool false \| Int $arrangementID | false = ikke arrangement <br /> numerisk = arrangementID
**site_type**| String [kommune \| fylke \| arrangement] | Brukes for å velge templates i UKMresponsive, identifisere kommunesider osv
**pl_eier_type** | String [kommune \| fylke \| land] | Brukes for å switche funksjonalitet i moduler osv<br /> (hvis arrangementet er eid av et fylke, skal administrator også kunne...)
**pl_eier_id** | Int | Hvilken kommune eller fylke eier arrangementet (har opprettet det)
**fylke** | Int | Viser hvilket fylke denne bloggen faller inn under. <br />Både kommuner og fylker har denne variabelen satt
**kommune** | Int | Viser hvilken kommune/bydel denne bloggen omhandler. <br />Brukes kun sammen med `site_type:kommune`
**~~kommuner~~** | String csv Int | Hvis dette er et lokal-arrangement (i database og mange sammenhenger kalt kommune-arrangement) angir denne variabelen hvilke kommuner, eller hvilken kommune, som er med i arrangementet. <br /> Oppdateres arrangementet, oppdateres denne. Bruk heller $arrangement->getKommuner()

# Ny innslagstype
For å opprette en ny innslagstype som fungerer både i admin og i Delta, må du:
- Opprette .yml-filen som definerer innslagstypen i `Innslag\Typer\config\`
- Legge til en case i `Innslag\Typer\Typer.php` L#388, `_translate_id_to_key()`
- Legge til typen i `Typer::getAllTyper()` L#201.
- Og legge til en relasjon mellom mønstringen og der det skal åpnes for denne typen (Update UKMmonstring, kanskje?)
- Dersom innslagstypen har èn eller flere av disse, må en egen translate-fil finnes i Delta.
	- beskrivelse
	- varighet
	- sjanger
	- tekniske_behov
	- titler
	- funksjon