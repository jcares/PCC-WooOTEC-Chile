# PCC-WooOTEC-Chile PRO v2.0.0

## Resumen

Primer release profesional del plugin PCC-WooOTEC-Chile PRO para integrar Moodle con WooCommerce en un flujo completo de venta, sincronizacion, matricula y acceso SSO.

## Incluye

- Arquitectura modular por clases.
- Sincronizacion de categorias Moodle hacia `product_cat`.
- Sincronizacion de cursos Moodle hacia productos WooCommerce.
- SKU consistente por curso: `MOODLE-{id}`.
- Metadatos de producto:
  - `_moodle_id`
  - `_start_date`
  - `_end_date`
  - `_instructor`
- Descarga de imagen destacada desde Moodle y fallback a imagen por defecto.
- Matricula automatica en `woocommerce_order_status_completed`.
- Generacion y persistencia de `_moodle_access_url`.
- Dashboard frontend con shortcode `[pcc_mis_cursos]`.
- Logs de operacion en `wp-content/uploads/pcc-logs/`.
- Cron de sincronizacion cada 1 hora.
- Updater compatible con GitHub Releases.

## Artefacto

- Asset: `PCC-WooOTEC-Chile-PRO-2.0.0.zip`
- Ruta local: `dist/PCC-WooOTEC-Chile-PRO-2.0.0.zip`
- SHA-256: `DFB84F15B19EAF51E4D2E44879903B00584E47C6DD63DD400B63FDA1B517C0FE`

## Publicacion sugerida

1. Crear tag `v2.0.0`
2. Crear release `PCC-WooOTEC-Chile PRO v2.0.0`
3. Subir el asset `dist/PCC-WooOTEC-Chile-PRO-2.0.0.zip`
4. Publicar `release.json` si vas a usar el updater por manifiesto
