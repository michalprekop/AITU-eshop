# AITU – spoločný sklad SK/EN

`mu-plugins/aitu-shared-stock.php` používa existujúce párovanie Polylang a rovnaké atribúty veľkosti. Jeden variant je vlastníkom skladu; jeho identifikátor používa aj natívny WooCommerce pri rezervácii, odpočte a doplnení. Zmena množstva v SK alebo EN sa prenesie do oboch jazykov. Aktuálne mapovanie obsahuje 28 veľkostí pre sedem dizajnov.

## Bežná práca

Počet kusov uprav v jednom jazykovom variante cez WooCommerce. Nepočítaj SK a EN ako dva samostatné fyzické sklady. S/M/L/XL patria k jednotlivému dizajnu, nie do spoločného skladu medzi rôznymi dizajnmi.

## Nový dizajn, veľkosť alebo preklad

1. Vytvor oba jazykové produkty a prepoj ich v Polylang.
2. Zachovaj rovnaké atribúty veľkostí a počiatočný fyzický počet kusov v prepojených variantoch.
3. Prihlásený správca môže prečítať plán cez `GET /wp-json/aitu/v1/shared-stock`. Vyžaduje oprávnenie `manage_woocommerce` a štandardnú autentifikáciu WordPress REST API.
4. Po overení plánu sa mapovanie obnovuje cez `POST /wp-json/aitu/v1/shared-stock/initialize`. Vyžaduje aj `manage_options`. Ak sú aktívne rezervácie alebo sa varianty nezhodujú, zápis sa odmietne. Nikdy neobchádzať túto kontrolu zmazaním rezervácií.
5. Over dostupnosť v oboch jazykoch a kombinované množstvo v košíku.

Pri nových preložených variantoch sa nákup zablokuje do vytvorenia mapovania. Plugin nezakladajúci sa na Polylang párovaní alebo priamo zapisujúci do databázy môže spoločný sklad obísť; budúce skladové integrácie treba overiť.

## Overovacie nástroje

`tools/prelaunch/aitu-stock-integration-check.php` je uložený zdroj jednorazového testu z 5. 9. 2026. Na serveri po overení nezostal. Vytvára iba vlastné dočasné skryté produkty, obnovuje mapovanie a odstraňuje ich v bloku `finally`. Je určený na uzamknutý obchod/staging bez prebiehajúcich objednávok, nie do trvalých `mu-plugins`.

`tools/prelaunch/aitu-prelaunch-content.php` je záznam už vykonanej idempotentnej prípravy obsahu. Ani tento pomocník nemá zostať aktívny. Zmena hlavnej domovskej stránky na EN ID 207, veľkostné metadáta produktov a presun ukážkových článkov do konceptov boli vykonané cez štandardné REST API samostatne.

Pri návrate na starý kód najskôr uzamkni predaj a skontroluj rezervácie/objednávky. Samotné vypnutie spoločného skladu by znovu sprístupnilo dve jazykové kópie toho istého tovaru.
