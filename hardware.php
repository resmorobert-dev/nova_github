<?php
/** Configuración de lector de huella. */
define('HUELLA_MODO', getenv('HUELLA_MODO') ?: 'sensor');
define('HUELLA_API_KEY', getenv('HUELLA_API_KEY') ?: '');
define('HUELLA_PERMITIR_DOCUMENTO', filter_var(getenv('HUELLA_PERMITIR_DOCUMENTO') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('HUELLA_MAX_SLOTS', (int) (getenv('HUELLA_MAX_SLOTS') ?: 127));
