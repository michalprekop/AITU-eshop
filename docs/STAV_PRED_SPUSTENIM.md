# AITU – stav pred spustením

Aktualizované 5. septembra 2026. Zmeny sú nasadené na aitu.world za existujúcim uzamknutím. **Verejné spustenie neprebehlo.** Verejná požiadavka vracia HTTP 503 a „AITU ESHOP OPENING SOON“; súbor uzamknutia je nezmenený. Nastavenia dopravných pluginov sa nemenili.

## Hotové na webe

- SK a EN používajú spoločný fyzický sklad. 56 jazykových variantov je spárovaných na 28 záznamov model/veľkosť; celkový fyzický sklad zostal 28 kusov.
- Košík počíta centové sumy bez zaokrúhlenia dopravy na celé eurá. Overené sadzby z existujúceho pluginu: SK výdajné miesto 4,49 € / kuriér 6,49 €; CZ výdajné miesto 5,49 € / kuriér 6,49 €.
- Všetkých 14 jazykových produktov má skutočnú tabuľku BY102 v centimetroch, originálny nákres výrobcu a poradie S, M, L, XL. Neželané popisy pod nákresom sú odstránené.
- Opravené domovské stránky, SK katalóg, odkaz bannera, prázdne kategórie a odkazy v pätičke. Domovská stránka aj katalóg zobrazujú sedem skutočných dizajnov v každom jazyku.
- Doplnené SK/EN stránky O AITU, Časté otázky, Doprava a platba a Stav objednávky. Sledovanie objednávky používa natívny formulár WooCommerce.
- Opravené načítanie skriptov v pokladni, zobrazovanie chýb WooCommerce a hlásenie úspešného pridania do košíka.
- Doplnené H1, popisy stránok, údaje Product pre vyhľadávače a anglická domovská stránka. `/en/` smeruje na anglický úvod. Ukážkové stránky aj oba články „Ahoj svet!“ sú v konceptoch.
- Voliteľné návštevnícke meranie Jetpack/WooCommerce a sledovanie zdroja objednávky sú vypnuté. Existujúce pripojenie WooPayments zostalo zachované. IBM Plex Mono sa načítava z vlastného servera s priloženou licenciou.
- Použité dočasné administrátorské pomocné pluginy boli po overení odstránené.

## Čo ešte bráni uzavretiu prípravy

1. **Údaje predajcu a prevádzky.** Vo [formulári](formular-pred-spustenim.html) chýbajú potvrdené obchodné meno/IČO/daňové údaje, sídlo, kontakt, adresa vrátenia, expedícia a fakturácia. Bez nich zostávajú obchodné podmienky, ochrana súkromia, cookies, vrátenie a reklamácie nedokončené. V nastaveniach WooCommerce sú Terms/Privacy zatiaľ nezadané. Do verejných textov sa nevložili vymyslené údaje ani odkazy na nedokončené dokumenty.
2. **WooPayments.** Aktuálny audit účtu ukázal `restricted_soon` a nedokončené overenie identity s termínom 5. októbra 2026. Dokončiť priamo vo WooPayments; údaje z dokladov nepatria do formulára.
3. **E-mailová doména.** `_dmarc.aitu.world` vracia dva DMARC záznamy (`p=quarantine` a `p=none`). Treba ich zjednotiť v DNS správe a následne overiť doručovanie. DNS sa počas tejto práce nemenilo.
4. **Fyzický produkt a expedícia.** Potvrdiť miesto potlače/výšivky pre presný opis pôvodu. Existujúce tvrdenie o výrobe na Slovensku sa bez potvrdenia neprepísalo. Hmotnosť zabaleného trička zatiaľ nie je overená; 240 g/m² je gramáž látky, nie hmotnosť zásielky. Vyskúšať reálny postup vytvorenia štítku pri zachovaní nastavení dopravcu.
5. **Celý priebeh objednávky.** Po doplnení údajov a overení platieb vykonať kontrolovanú objednávku v SK aj EN: platba/3DS, výdajné miesto aj kuriér, e-mail, doklad, odpis skladu, štítok, storno/refundácia a vrátenie skladu. V tejto práci nevznikla objednávka ani platba.

Formulár si pamätá odpovede v tomto prehliadači a umožňuje stiahnuť textový súbor. Nič sám neposiela. Prehľad otázok je aj v [Markdown verzii](DOTAZNIK_PRED_SPUSTENIM.md).

## Overenie a jeho rozsah

- 15 kontrol skladu cez aktuálne WooCommerce funkcie na dočasných skrytých produktoch: odpis, doplnenie, nulová dostupnosť, editácia skladu v oboch jazykoch a spoločný identifikátor pre rezervácie. Testovacie produkty boli odstránené.
- Oddelený anonymný košík: SK kus + EN kus toho istého modelu/veľkosti; tretí kus bol odmietnutý pri zásobe dva kusy. Overená doprava SK/CZ aj dostupnosť WooPayments. Testovací košík bol vyprázdnený.
- HTML skontrolované v Safari pre SK/EN úvod, katalóg, produkt a FAQ. Tabuľka aj nákres v rozbalenej sekcii produktu boli skontrolované vizuálne v Safari. Tieto kontroly nenahrádzajú platobnú skúšku ani úplnú kontrolu na reálnom mobile.
- Finálne súbory stiahnuté späť cez SFTP a porovnané SHA-256. PHP a hlavný JavaScript prešli kontrolou syntaxe.
- Výsledky bez prihlasovacích údajov sú v [overenie-2026-09-05](overenie-2026-09-05/release-summary.json).

## Súbory a ďalšia údržba

- Kód: `aitu-woocommerce-theme/`, `mu-plugins/aitu-shared-stock.php`, `mu-plugins/aitu-storefront-privacy.php`.
- Overený zdroj rozmerov a pôvodný diagram: [BY102-rozmery.json](BY102-rozmery.json), [SK HTML](BY102-size-guide-sk.html), [EN HTML](BY102-size-guide-en.html).
- GitHub: vetva [`codex/aitu-prelaunch-20260905`](https://github.com/michalprekop/AITU-eshop/tree/codex/aitu-prelaunch-20260905). Lokálna pracovná kópia obsahuje nasadené súbory; pôvodná vetva `main` nebola prepísaná ani resetovaná.
- Zálohy pôvodných súborov a dát sú lokálne v `.private/prelaunch-20260905/`, mimo Gitu. Obsolete koreňový ZIP témy z marca 2026 sa nepoužíval.
- Po pridaní nového prekladu alebo veľkosti treba aktualizovať mapovanie spoločného skladu; nesprávne spárovaná nová veľkosť sa zámerne nedá objednať. Postup je v [PREVADZKA_SKLADU.md](PREVADZKA_SKLADU.md).
- Zapnutie analytiky neskôr vyžaduje samostatné nastavenie súhlasu a overenie skutočných požiadaviek/cookies. Aktuálne nie je pripravená na automatické zapnutie.

Uzamknutie ponechať zapnuté až do výslovného pokynu na spustenie.
