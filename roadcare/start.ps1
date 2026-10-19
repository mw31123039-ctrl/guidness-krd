$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
if (-not (Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Run composer install first. On this computer, php ../tools/composer.phar install is also available.' }
if (-not (Test-Path -LiteralPath 'public/build/manifest.json')) {
    npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'The frontend build failed.' }
}
$routerPath = Join-Path $PSScriptRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
Write-Host 'RoadCare: open http://127.0.0.1:8000 in your browser. Press Ctrl+C to stop.'
Push-Location -LiteralPath (Join-Path $PSScriptRoot 'public')
try {
    # Set upload limits on the actual HTTP process, rather than only on Artisan's parent process.
    php -d upload_max_filesize=10M -d post_max_size=12M -S 127.0.0.1:8000 $routerPath
} finally {
    Pop-Location
}
