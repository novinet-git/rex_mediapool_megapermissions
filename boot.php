<?php

/**
 * Mediapool Addon.
 *
 * @author jan[dot]kristinus[at]redaxo[dot]de Jan Kristinus
 */

$addon = rex_addon::get('mediapool');

rex_complex_perm::register('media', rex_media_perm::class);
rex_complex_perm::register('media_read', rex_media_read_perm::class);
rex_perm::register('media_pool[categories_editor]', null, rex_perm::EXTRAS);

require_once __DIR__ . '/functions/function_rex_mediapool.php';

if (rex::isBackend() && rex::getUser()) {
    rex_view::addJsFile($addon->getAssetsUrl('mediapool.js'), [rex_view::JS_IMMUTABLE]);
    rex_view::setJsProperty('imageExtensions', $addon->getProperty('image_extensions'));
}
