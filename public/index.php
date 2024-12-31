<?php

use App\Kernel;

// On déclare le fuseau horaire afin de manipuler les heures à l'aide des fonctions natives de PHP
date_default_timezone_set('Europe/Paris');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
