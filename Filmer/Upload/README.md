Opplasting til UKM-TV
===

Opplasting av filmer er mildt sagt (over)komplisert.

Gjennom prosessen er `CRON_ID` den unike identifikatoren. Alle tabeller og prosesser jobber mot denne, unntatt `ukm_tv_files`. 🤦🏼‍♂️
Dette er en auto_increment primary key fra `ukmtv`-tabellen på videoconverter.ukm.no og blir generert så fort videoconverteren har mottatt en film.


## Når brukeren laster opp film
**Film av innslag**
1. Filmen lastes opp direkte til videoconverter.ukm.no fra UKM.no (jQupload_receive.php).
2. Videoconverteren assigner en `CRON_ID`, og sender denne tilbake til skjemaet på UKM.no.
3. Skjemaet submittes (av brukeren/automatisk), og det opprettes en rad i `ukm_related_video`
    - CRON_ID
    - B_ID
    - ARRANGEMENT_ID

**Film som ikke er tilknyttet innslag**
1. Filmen lastes opp direkte til videoconverter.ukm.no fra UKM.no (jQupload_receive.php).
2. Videoconverteren assigner en `CRON_ID`, og sender denne tilbake til skjemaet på UKM.no.
3. Skjemaet fylles ut og submittes av brukeren, og det opprettes en rad i `ukm_standalone_video`
    - CRON_ID
    - NAVN
    - BESKRIVELSE
    - KATEGORI
    - ARRANGEMENT_ID


## Videoconverteren gjør sin greie
1. Filmen hurtig-konverteres, og det lages 3 filer (`Convert-cron`). Dette tar ca 5 min.
    - _720p
    - _mobile
    - .jpg
2. Videoconverteren markerer databaseraden med `storage` (klar til overføring).
3. `Storage-cron` flytter de 3 filene til `video.ukm.no` (her lever også wowza et lykkelig liv, btw).
4. Når filene er flyttet, curler videoconverteren tilbake til `api.ukm.no/registrer_video`
5. Så fort videoconverteren har tid (ingen filmer som ikke er konvertert i kø), starter den å re-konvertere filmene. Alle filmer konverteres flere ganger:
    - hurtig (rett etter opplasting): hastighet over komprimering. Gir relativt stor fil.
    - skikkelig (når ingen hurtig-opplastinger er i kø): komprimering over hastighet. Gir liten fil med høy kvalitet.
    - arkiv (når alle hurtig- og skikkelig-konverteringer er gjort): kraftig komprimering, høyere oppløsning. Litt større fil for arkivet.

    Storage-cron flytter:
    - hurtig-konvertert fil til video.ukm.no (videostorage)
    - skikkelig konvertert fil til video.ukm.no, hvor den overskriver hurtig-konverterte filer.
    - arkiv-filer via NFS-share til NASA @ UKM-kontoret (lokal filserver)


## api.ukm.no/registrer_video
Basert på input-data registreres filmen i UKM-TV

**Hvis innslag**
1. Setter inn rad i `ukmno_wp_related`-tabellen (skulle den ved feil finnes fra før, oppdateres den)
2. Oppdaterer raden i `ukm_related_video` (da vet UKM.no at den er ute av konverteringskø)

**Hvis standalone (reportasje)**

IDK ATM
@TODO

## Tabeller
### ukm_related_video

Tabellen holder oversikt over opplastede filer, og hvilke som er konvertert. En ikke-konvertert film relatert til innslag har tomt FILE-felt. Så fort filmen registreres etter konvertering, oppgis også FILE-feltet.

`CRON_ID | B_ID | FILE`

### ukm_standalone_video
Tabellen holder oversikt over alle videoreportasjer..... (håper jeg - hvis ikke lurer jeg på hvem som gjør det)

@TODO

### ukmno_wp_related
Tabellen holder oversikt over mediefiler tilhørende innslag. Tabellen kan både lenke til filmer, bilder, wp-posts, og kan tenkes også å benyttes til andre medieformater på sikt. En rad i denne tabellen opprettes først når filmen er ferdig konvertert, og et reelt vedlegg til innslaget.
I tabellen lagres det mye informasjon.

`CRON_ID | INNSLAG_ID | BLOG_ID | BLOG_URL | POST_TYPE(video) | POST_META(title,file++) | INNSLAG_KOMMUNE | SESONG | ARRANGEMENTSTYPE`

### ukm_tv_files
Her registreres alle filmer som vises i UKM-TV