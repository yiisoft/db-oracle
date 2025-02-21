<?php

declare(strict_types=1);

if (getenv('ENVIRONMENT', true) === 'local') {
    putenv('YII_ORACLE_HOST=oracle');
}
