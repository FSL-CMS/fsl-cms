<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

// Soubor se zobrazí, pouze pokud nefunguje přesměrování do složky /document_root,
// nebo pokud se přesměrování schválně vypne

header('HTTP/1.1 500 Internal Server Error');
header('Content-Type: text/html; charset=utf-8');

echo "Došlo k technickým problémům, na jejich odstranění pracujeme.";