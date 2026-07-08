<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/symfony.php';

set('application', 'bingo');
set('shared_dirs', []);
set('shared_files', []);
set('writable_dirs', []);

host('prod')
    ->set('hostname', 'achille')
    ->set('remote_user', 'robin')
    ->set('deploy_path', '/home/bingo/www');

task('deploy:update_code', function () {
    upload([
        'bin',
        'config',
        'frankenphp',
        'migrations',
        'public',
        'src',
        'templates',
        'translations',
        'compose.prod.yaml',
        'compose.yaml',
        'composer.json',
        'composer.lock',
        'Dockerfile',
        'symfony.lock',
    ], '{{deploy_path}}');
    upload('.env.prod', '{{deploy_path}}/.env');
});
task('deploy:vendors', function () {
});

before('deploy:prepare', function () {
    runLocally('make sf c=\'asset-map:compile\'');
});

after('deploy:success', function () {
    cd('{{deploy_path}}');
    run('docker compose up --build -d');
});

after('deploy:failed', 'deploy:unlock');
