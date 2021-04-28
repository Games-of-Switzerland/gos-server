<?php

/**
 * @file
 * On login, redirect user to the admin content page.
 */

/**
 * Implements hook_simple_sitemap_sitemap_types_alter().
 *
 * Add the NextJS Url generator to default hreflang sitemap generations.
 */
function gos_site_simple_sitemap_sitemap_types_alter(array &$sitemap_types): void {
  if (isset($sitemap_types['default_hreflang'])) {
    $sitemap_types['default_hreflang']['urlGenerators'] = [
      'nextjs',
    ];
  }
}
