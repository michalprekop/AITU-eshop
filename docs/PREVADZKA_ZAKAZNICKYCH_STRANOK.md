# AITU – zákaznícke stránky a odstúpenia

Aktualizované 6. septembra 2026. Web zostáva uzamknutý pre verejnosť.

## Obsah a údaje

MU plugin `aitu-customer-info.php` načíta jazykové texty z `aitu-customer-info/content.json`. Stránky používajú shortcode `[aitu_customer_info kind="terms"]` a ostatné podporované druhy. Potvrdené obchodné údaje sú v nastavení WordPressu `aitu_merchant_details`; mapovanie stránok je v `aitu_info_pages`. Surové odpovede ani export osobných údajov nepatria do Gitu.

Pri zmene sídla, kontaktu alebo adresy vrátenia aktualizovať príslušné obchodné údaje. Sídlo predajcu sa nesmie automaticky zameniť s adresou skladu/odosielania. Nastavenia dopravných pluginov ostali zachované.

Po zmene zmluvných textov zvýšiť dátum/verziu v obsahu aj v `aitu_capture_contract_copy()`. Nové objednávky si uložia kópiu podmienok a poučenia o vrátení do `_aitu_contract_copy`. Zákaznícke e-maily pri čakaní na platbu, spracovaní, dokončení a zaslaní fakturačnej výzvy obsahujú túto uloženú kópiu. Staršia objednávka sa pri opakovanom poslaní neprepíše novým znením. Testy overili tento mechanizmus na objekte objednávky v pamäti; doručenie skutočného e-mailu sa ešte musí overiť.

## Rozhodnutie o telefónnom kontakte

Používateľ 6. septembra 2026 požiadal telefón nezverejňovať. Číslo ostáva prázdne a kontakt sa poskytuje e-mailom. Bez nového výslovného pokynu nedopĺňať žiadny telefón z účtu, fakturačných údajov ani iného zdroja; osobné číslo opakovane nevyžadovať. Tento pokyn nemení telefónne polia zákazníkov potrebné pre doručenie.

Pred verejným spustením zostáva otvorené splnenie informačnej povinnosti podľa § 5 ods. 1 písm. c) zákona č. 108/2024 Z. z. Prípadné samostatné firemné číslo je iba možnosťou na neskoršie rozhodnutie, nie povolením na nákup alebo zverejnenie čísla.

## Online odstúpenie

- SK: https://www.aitu.world/sk/odstupenie-od-zmluvy
- EN: https://www.aitu.world/withdraw-from-contract

Prvý krok vyžiada meno, e-mail a identifikáciu objednávky; voliteľne konkrétne vracané kusy. Prázdny rozsah znamená celú objednávku. Druhý krok ukáže plné oznámenie a tlačidlo „Potvrdiť odstúpenie od zmluvy“. Názov zmluvy/objednávky je voľný text, aby chýbajúce číslo či účet nebránili podaniu. Funkcia nehľadá ani nezverejňuje objednávky podľa cudzieho čísla.

Potvrdením sa najprv uloží súkromný záznam s časom v UTC aj Europe/Bratislava. Až potom sa volá odoslanie zákazníkovi a predajcovi. Potvrdenie je dostupné aj ako TXT cez náhodný 48-znakový odkaz; v databáze sa uchováva jeho SHA-256. Odkaz platí 90 dní. Samotná stránka má vypnutú cache, indexovanie aj odosielanie referera. Identifikačné údaje zákazníka sa nevkladajú do URL. Náhľad formulára má platnosť 24 hodín.

V administrácii sú oznámenia pod **WooCommerce → AITU – Odstúpenia**. Detaily vidia iba používatelia s oprávnením spravovať WooCommerce. Záznam obsahuje deklaráciu aj informáciu, či e-mailový systém prijal jednotlivé správy. Úspešné `wp_mail()` ešte nepotvrdzuje doručenie do schránky.

Pri chybe e-mailu zostáva oznámenie uložené, zákazník má sťahovateľnú kópiu a neúspešná správa má najviac tri opakovania cez WP-Cron po 15 minútach. Úspešná správa predajcovi sa pri opakovaní zákazníckej správy neposiela znova. Chod WP-Cron a reálne doručovanie treba zahrnúť do prevádzkovej kontroly. Opätovné potvrdenie rovnakého oznámenia nevytvorí druhý záznam.

Funkcia **nemení objednávku ani automaticky nevracia platbu**. Predajca spojí oznámenie s objednávkou, vybaví vrátenie a refundáciu podľa poučenia a zaznamená postup pri objednávke. Alternatívne oznámenie e-mailom alebo poštou ostáva dostupné. Bežné tričko z kolekcie sa nepovažuje za personalizovaný tovar iba pre existujúcu potlač/výšivku.

Prevádzkovateľ pravidelne posúdi uchovávanie oznámení a komunikácie podľa lehôt uvedených v ochrane súkromia. Plugin ich automaticky nemaže, aby nezanikli podklady k otvorenému sporu. Účtovné doklady majú vlastnú zákonnú lehotu.

## SuperFaktúra

Používanie SuperFaktúry je potvrdené. Automatické API prepojenie nebolo aktivované a žiadny doklad sa nevystavil. Do jeho nastavenia sa faktúra vystaví manuálne v existujúcom účte z údajov objednávky, so správnym režimom neplatiteľa DPH a odkazom/číslom objednávky. Pred spustením treba preveriť doručenie dokladu zákazníkovi. Pri vrátení platby zodpovedajúcim spôsobom upraviť aj účtovný doklad.

## Nasadenie, overenie a obnova

Dočasné pomocníky `tools/prelaunch/aitu-merchant-setup.php` a `aitu-customer-integration-check.php` nie sú trvalou súčasťou aktívnych MU pluginov. Ich API vyžaduje správu webu aj WooCommerce. Po migrácii a kontrolách boli odstránené. Migračná záloha v nastavení `aitu_merchant_setup_backup_v1` zachováva pôvodné dotknuté stránky, vybrané nastavenia a popisy/hmotnosti produktov. Lokálna kópia je v `.private/prelaunch-20260905/merchant-completion/`.

Pri cielenej obnove použiť zálohu konkrétneho súboru alebo konkrétnej položky. Neobnovovať celý obchod ani sklad zo starej snímky, ak medzitým vznikli objednávky. Pri vypnutí modulu by stránky so shortcode zostali bez obsahu; pred jeho odobratím treba previesť alebo obnoviť ich obsah. Súbor existujúceho uzamknutia sa počas nasadenia nemenil.

Výsledok 39 integračných kontrol je v `overenie-2026-09-05/customer-integration-result.json`. Vznikli len dočasné súkromné skúšobné oznámenia; všetky boli odstránené spolu s ich opakovaniami. Všetky volania `wp_mail()` počas skúšky boli zachytené filtrom. Žiadne skutočné objednávky, platby, faktúry ani štítky sa nevytvorili.

## Primárne zdroje textov

- [Zákon 108/2024 Z. z., znenie účinné 31. 7. – 26. 9. 2026](https://static.slov-lex.sk/static/SK/ZZ/2024/108/20260731.html): informačné povinnosti, objednávkové tlačidlo, lehoty, online odstúpenie podľa § 20a a refundácie. Pred spustením po 26. septembri znovu preveriť aktuálne znenie.
- [Občiansky zákonník, znenie účinné od 31. 7. 2026](https://static.slov-lex.sk/static/SK/ZZ/1964/40/20260731.html): §§ 619–624, vady a ich odstránenie vrátane predĺženia doby zodpovednosti po prvej oprave.
- [SOI – alternatívne riešenie spotrebiteľských sporov](https://www.soi.sk/alternativne-riesenie-spotrebitelskych-sporov), [kontakty SOI](https://www.soi.sk/kontakt). Neaktívna európska platforma ODR sa do textov nepridáva.
- [GDPR](https://eur-lex.europa.eu/eli/reg/2016/679/oj/eng), [Úrad na ochranu osobných údajov SR](https://dataprotection.gov.sk/).
- [Zákon o účtovníctve 431/2002, § 35](https://www.slov-lex.sk/ezbierky/pravne-predpisy/SK/ZZ/2002/431/20260601).
- [WooCommerce – oficiálny filter tlačidla pokladne](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/checkout-and-place-order-button/). Použitá je podporovaná registrácia filtrov, nie prepis tlačidla cez DOM.
