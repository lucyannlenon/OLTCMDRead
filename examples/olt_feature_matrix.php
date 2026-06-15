<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltFeatureMatrix;

require __DIR__ . '/../vendor/autoload.php';

echo (new OltFeatureMatrix())->toMarkdown();
