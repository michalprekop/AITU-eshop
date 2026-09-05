# AITU – stav pred spustením

Aktualizované 6. septembra 2026. Zmeny sú nasadené na aitu.world za existujúcim uzamknutím. **Verejné spustenie neprebehlo.** Verejná požiadavka vracia HTTP 503 a „AITU ESHOP OPENING SOON“; súbor uzamknutia je nezmenený. Nastavenia dopravných pluginov sa nemenili.

## Hotové na webe

- Spracované odpovede z formulára a opravené DIČ. SK/EN kontakt, obchodné podmienky, ochrana súkromia, cookies, vrátenie a reklamácie obsahujú potvrdené údaje predajcu. Sídlo je oddelené od adresy na zasielanie vráteného tovaru.
- Doplnená expedícia do 5 pracovných dní, SuperFaktúra ako používaný fakturačný systém a potvrdený Instagram. Existujúce FAQ, doprava a O AITU sú aktualizované.
- Všetkých 14 produktov má hmotnosť balenia 0,31 kg. Všetkých 60 uložených variantov ju dedí; štyri staré varianty sú súkromné a 56 aktívnych jazykových variantov používa existujúce mapovanie skladu. Ceny, obrázky, rozmery aj zásoby ostali nezmenené. Hmotnosť pochádza z potvrdeného formulára, nie zo zámeny s gramážou látky 240 g/m².
- Opis pôvodu teraz presne uvádza dizajn, potlač a výšivku na Slovensku. Základný model BY102 sa neoznačuje za tričko vyrobené na Slovensku.
- Online odstúpenie má kontrolu údajov a samostatné potvrdenie, súkromný záznam v administrácii, potvrdenie e-mailom aj na stiahnutie a ochranu pred duplicitným podaním. Nevyžaduje účet. Samo nestornuje objednávku ani nevracia peniaze.
- SK aj EN pokladňa majú povinné prijatie obchodných podmienok a informatívny odkaz na súkromie. Tlačidlo znie „Objednať s povinnosťou platby“ / „Place order and pay“. Znenie podmienok sa ukladá k novej objednávke a vkladá do jej zákazníckych e-mailov.
- Pätička sa prispôsobuje počtu zákazníckych odkazov. Opravené zdvojené EUR pri celkovej sume spôsobené doplnením kódu meny cez WooPayments; nastavenia platieb ani dopravy sa tým nemenia.

- SK a EN používajú spoločný fyzický sklad. 56 jazykových variantov je spárovaných na 28 záznamov model/veľkosť; celkový fyzický sklad zostal 28 kusov.
- Košík počíta centové sumy bez zaokrúhlenia dopravy na celé eurá. Overené sadzby z existujúceho pluginu: SK výdajné miesto 4,49 € / kuriér 6,49 €; CZ výdajné miesto 5,49 € / kuriér 6,49 €.
- Všetkých 14 jazykových produktov má skutočnú tabuľku BY102 v centimetroch, originálny nákres výrobcu a poradie S, M, L, XL. Neželané popisy pod nákresom sú odstránené.
- Opravené domovské stránky, SK katalóg, odkaz bannera, prázdne kategórie a odkazy v pätičke. Domovská stránka aj katalóg zobrazujú sedem skutočných dizajnov v každom jazyku.
- Doplnené SK/EN stránky O AITU, Časté otázky, Doprava a platba a Stav objednávky. Sledovanie objednávky používa natívny formulár WooCommerce.
- Opravené načítanie skriptov v pokladni, zobrazovanie chýb WooCommerce a hlásenie úspešného pridania do košíka.
- Doplnené H1, popisy stránok, údaje Product pre vyhľadávače a anglická domovská stránka. `/en/` smeruje na anglický úvod. Ukážkové stránky aj oba články „Ahoj svet!“ sú v konceptoch.
- Voliteľné návštevnícke meranie Jetpack/WooCommerce a sledovanie zdroja objednávky sú vypnuté. Existujúce pripojenie WooPayments zostalo zachované. IBM Plex Mono sa načítava z vlastného servera s priloženou licenciou.
- Použité dočasné administrátorské pomocné pluginy boli po overení odstránené.

## Čo zostáva pred verejným spustením

1. **Telefonický kontakt – rozhodnutie používateľa.** Používateľ 6. septembra výslovne uviedol, že telefón nechce zverejňovať. Rešpektovať tento pokyn; nežiadať opakovane jeho osobné číslo a bez nového výslovného pokynu žiadne číslo na web nedopĺňať. Kontakt ostáva e-mailom. Zákon č. 108/2024 Z. z. v [§ 5 ods. 1 písm. c)](https://static.slov-lex.sk/static/SK/ZZ/2024/108/20260731.html#paragraf-5) vyžaduje pred objednaním oznámiť telefónne číslo obchodníka, preto sa táto požiadavka nesmie označiť za splnenú. Možnosťou je samostatné firemné číslo; jeho zriadenie ani zverejnenie zatiaľ nie je schválené.
2. **WooPayments.** Opätovná kontrola účtu ukázala `restricted_soon`, platby povolené, živý režim a nedokončené overenie identity s termínom 5. októbra 2026. Dokončenie vyžaduje údaje držiteľa účtu priamo vo WooPayments.
3. **E-mailová doména.** `_dmarc.aitu.world` vracia dva DMARC záznamy (`p=quarantine` a `p=none`). Treba ich zjednotiť v DNS správe a overiť doručovanie. DNS sa nemenilo. Testy kódu e-mailov zachytávali odoslanie; nepreukazujú doručenie do schránky.
4. **Skúška celej objednávky a expedície.** Zostáva kontrolovaná objednávka v SK aj EN: platba/3DS, výdajné miesto a kuriér, doručený e-mail, faktúra, odpis skladu, štítok, refundácia a doplnenie skladu. Reálna objednávka ani platba počas tejto práce nevznikla. Mobilný priebeh treba preveriť aj na skutočnom telefóne.

**SuperFaktúra:** používanie systému je potvrdené, automatické prepojenie s WooCommerce však nie je nakonfigurované. Doklad možno vystaviť manuálne v existujúcom účte. Text stránky ani toto odovzdanie neznamenajú aktiváciu automatickej fakturácie.

Vyplnené odpovede a opravené údaje sú uložené lokálne v `.private/prelaunch-20260905/`, mimo Gitu. Živé stránky čítajú schválené obchodné údaje z nastavenia `aitu_merchant_details`; samotný Git obsahuje šablóny.

## Overenie a jeho rozsah

- 39 integračných kontrol zákazníckych funkcií na aktuálnom WordPresse: validácia a escapovanie, čiastočné aj celé odstúpenie, tajný odkaz pre neprihláseného zákazníka, duplicity, zlyhanie e-mailu a opakovanie iba neúspešnej správy, úplnosť oboch jazykov, obchodné podmienky v HTML/textovom e-maile a dedenie hmotnosti. Všetky skúšobné oznámenia a ich naplánované opakovania boli odstránené. Skutočné e-maily sa neposielali.
- 5 lokálnych kontrol registrácie filtrov pokladne a formátovania EUR/USD. Finálny text tlačidla a jedna mena pri celkovej sume overené aj v bežiacej SK/EN pokladni v Safari.
- HTML načítané pre 16 zákazníckych stránok, oba produkty a obe pokladne. Vizuálne skontrolovaný slovenský formulár, kontakt a upravená pätička. Po pokyne používateľa pokračovalo Safari výhradne na pozadí.
- Porovnanie všetkých 14 produktov potvrdilo zmenu iba hmotnosti a presnej vety o pôvode. Celý výstup spoločného skladu je identický s pôvodným. Existujúce dva kusy v Safari košíku ostali zachované.

- 15 kontrol skladu cez aktuálne WooCommerce funkcie na dočasných skrytých produktoch: odpis, doplnenie, nulová dostupnosť, editácia skladu v oboch jazykoch a spoločný identifikátor pre rezervácie. Testovacie produkty boli odstránené.
- Oddelený anonymný košík: SK kus + EN kus toho istého modelu/veľkosti; tretí kus bol odmietnutý pri zásobe dva kusy. Overená doprava SK/CZ aj dostupnosť WooPayments. Testovací košík bol vyprázdnený.
- HTML skontrolované v Safari pre SK/EN úvod, katalóg, produkt a FAQ. Tabuľka aj nákres v rozbalenej sekcii produktu boli skontrolované vizuálne v Safari. Tieto kontroly nenahrádzajú platobnú skúšku ani úplnú kontrolu na reálnom mobile.
- Finálne súbory stiahnuté späť cez SFTP a porovnané SHA-256. PHP a hlavný JavaScript prešli kontrolou syntaxe.
- Výsledky bez prihlasovacích údajov sú v [overenie-2026-09-05](overenie-2026-09-05/release-summary.json).

## Súbory a ďalšia údržba

- Kód: `aitu-woocommerce-theme/`, `mu-plugins/aitu-shared-stock.php`, `mu-plugins/aitu-storefront-privacy.php`, `mu-plugins/aitu-customer-info.php` a `mu-plugins/aitu-customer-info/`.
- Overený zdroj rozmerov a pôvodný diagram: [BY102-rozmery.json](BY102-rozmery.json), [SK HTML](BY102-size-guide-sk.html), [EN HTML](BY102-size-guide-en.html).
- GitHub: vetva [`codex/aitu-prelaunch-20260905`](https://github.com/michalprekop/AITU-eshop/tree/codex/aitu-prelaunch-20260905). Lokálna pracovná kópia obsahuje nasadené súbory; pôvodná vetva `main` nebola prepísaná ani resetovaná.
- Zálohy pôvodných súborov a dát sú lokálne v `.private/prelaunch-20260905/`, mimo Gitu. Obsolete koreňový ZIP témy z marca 2026 sa nepoužíval.
- Po pridaní nového prekladu alebo veľkosti treba aktualizovať mapovanie spoločného skladu; nesprávne spárovaná nová veľkosť sa zámerne nedá objednať. Postup je v [PREVADZKA_SKLADU.md](PREVADZKA_SKLADU.md).
- Zapnutie analytiky neskôr vyžaduje samostatné nastavenie súhlasu a overenie skutočných požiadaviek/cookies. Aktuálne nie je pripravená na automatické zapnutie.

Podrobnosti zákazníckych stránok, odstúpení, fakturácie a právne zdroje: [PREVADZKA_ZAKAZNICKYCH_STRANOK.md](PREVADZKA_ZAKAZNICKYCH_STRANOK.md).

Uzamknutie ponechať zapnuté až do výslovného pokynu na spustenie.
