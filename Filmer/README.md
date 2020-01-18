Opplasting til UKM-TV
===

Opplasting av filmer er litt komplisert, da `ukm_uploaded_video` holder styr på alle opplastede filmer, mens UKM-TV strengt tatt kan kjøre på egenhånd og motta filmer fra andre steder også.

Gjennom prosessen er `CRON_ID` den unike identifikatoren. Alle tabeller og prosesser jobber mot denne.
Dette er auto_increment primary key fra `ukmtv`-tabellen på videoconverter.ukm.no og blir generert så fort videoconverteren har mottatt en film.


## Når brukeren laster opp film ([UKMNorge/UKMvideo](https://github.com/UKMNorge/UKMvideo))
1. Filmen lastes opp direkte til videoconverter.ukm.no fra UKM.no (jQupload_receive.php).
2. Videoconverteren assigner en `CRON_ID`, og sender denne tilbake til skjemaet på UKM.no.

**Film av innslag**

3. Skjemaet submittes (av brukeren/automatisk), og det opprettes en rad i `ukm_uploaded_video`
    - CRON_ID
    - ARRANGEMENT_ID
    - SESONG
    - NAVN
    - BESKRIVELSE
    - *INNSLAG_ID*
    - *TITTEL* (hvilken av innslagets titler er dette. For øyeblikket alltid 0)

**Film som ikke er tilknyttet innslag**

3. Skjemaet fylles ut og submittes av brukeren, og det opprettes en rad i `ukm_uploaded_video`
    - CRON_ID
    - ARRANGEMENT_ID
    - SESONG
    - NAVN
    - BESKRIVELSE

Først nå vet UKM.no at det er en film på vei, og at den skal konverteres før den er klar, og blir lagt til i UKM-TV.

## Videoconverteren gjør sin greie ([UKMNorge/videoconverter](https://github.com/UKMNorge/videoconverter))
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


## api.ukm.no/video:registrer/ ([UKMNorge/UKMapi_public](https://github.com/UKMNorge/ukmapi_public))
Så fort filmen er lagret, curler videoconverter.ukm.no film-informasjonen til UKMapi. Basert på input-data fra converteren registreres filmen i UKM-TV.

**Hvis innslag**
1. Databaseraden i `ukm_uploaded_video` oppdateres (basert på `CRON_ID`)
    - FILE_PATH
    - IMAGE_PATH
    - CONVERTED:true
2. Filmen registreres i UKM-TV
    Etter registreringen, oppdateres `ukm_uploaded_video` med verdi for `TV_ID`

## Tabeller
### ukm_uploaded_video
Tabellen som overtok for `ukm_related_video`, `ukm_standalone_video` og `ukmno_wp_related` våren 2020 🚀. Data fra de andre er også overført, slik at denne vet om det meste som har blitt lastet opp (det er også kjørt en import fra `ukm_tv_files`, slik at filmer som finnes der er registrert som opplastet).

Når filmer lastes opp, settes det inn en rad her, som har kontroll på det meste om filmen. Når converteren er ferdig, overføres denne informasjonen til UKM-TV. Det blir altså en del dobbelt-lagring, men det gir større fleksibilitet for å videreutvikle opplasteren, og hvilke navn

### ukm_tv_files
Her registreres alle filmer som vises i UKM-TV

### ukm_tv_tags
Alle filmer tagges herfra til månen, slik at vi lett skal finne de igjen. 🌖 (Vi kunne sikkert tagget mer altså). Ved uthenting, gjør SQL-spørringen at disse følger filmen i formatet `tag_type`:`verdi`|`tag_type`:`verdi`, altså veldig likt det tidligere feltet `ukm_tv_files`.`tv_tags`. Ved endring av film, oppdateres tags i denne tabellen.

### ~~ukm_related_video~~

Tabellen holder oversikt over opplastede filer, og hvilke som er konvertert. En ikke-konvertert film relatert til innslag har tomt FILE-felt. Så fort filmen registreres etter konvertering, oppgis også FILE-feltet.

`CRON_ID | B_ID | FILE`

### ~~ukm_standalone_video~~
Tabellen holder oversikt over alle videoreportasjer som er lastet opp.

`CRON_ID | TITLE | DESCRIPTION | FILE | IMAGE | CATEGORY | PL_ID `


### ~~ukmno_wp_related~~
Tabellen holder oversikt over mediefiler tilhørende innslag. Tabellen kan både lenke til filmer, bilder, wp-posts, og kan tenkes også å benyttes til andre medieformater på sikt. En rad i denne tabellen opprettes først når filmen er ferdig konvertert, og et reelt vedlegg til innslaget.
I tabellen lagres det mye informasjon.

`CRON_ID | INNSLAG_ID | BLOG_ID | BLOG_URL | POST_TYPE(video) | POST_META(title,file++) | INNSLAG_KOMMUNE | SESONG | ARRANGEMENTSTYPE`
