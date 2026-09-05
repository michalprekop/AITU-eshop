# AITU – písma a návštevnícke meranie

## Nasadené 5. 9. 2026

Použité písmo IBM Plex Mono vo váhach 400/500 sa načítava z témy. Súbory sú nezmenené TTF poskytnuté Google Fonts; licencia SIL OFL 1.1 je priložená v `assets/fonts/ibm-plex-mono-OFL.txt`.

Zdroj CSS: https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&display=swap

Zdroj licencie: https://github.com/google/fonts/blob/main/ofl/ibmplexmono/OFL.txt

`aitu-storefront-privacy.php` vyraďuje iba moduly Jetpack `stats` a `woocommerce-analytics` cez podporovaný filter `jetpack_active_modules`; cez `wc_order_attribution_allow_tracking` vypína sledovanie zdroja objednávky. Pripojenie Jetpack/WooPayments zostáva aktívne. Funkčné cookies košíka, prihlásenia, výberu jazyka a technológie potrebné na platbu/doručenie ostávajú súčasťou prevádzky.

Overená produktová a anglická úvodná stránka už neobsahujú návštevnícke WooCommerce Analytics skripty, frontu `_wca` ani požiadavku na Google Fonts. Nejde o právne posúdenie všetkých cookies alebo o kompletný sieťový audit pokladne. Právny text ochrany údajov a cookies sa dokončí podľa skutočného predajcu, jeho dodávateľov, lehôt uchovávania a finálneho priebehu objednávky.

Pred budúcim zapnutím merania treba určiť konkrétne údaje, cieľ a účel, zaviesť potrebný súhlas a overiť správanie pred/po jeho udelení. Aktualizácie pluginov treba znova preveriť, ak menia spôsob načítania analytiky.
