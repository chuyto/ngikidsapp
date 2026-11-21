<# .SYNOPSIS
  Genera deploy/wasmer-upload/ (carpeta ligera) lista para subir a
  Wasmer Edge por carga de archivos (https://wasmer.io/new).
.DESCRIPTION
  La subida de Wasmer empaqueta todo en memoria: vendor/ (~8500 archivos)
  y node_modules/ provocan error de limite de memoria. Por eso esta
  carpeta solo lleva el codigo fuente + assets compilados + manifiestos:
  1) npm run build (assets Vite a public/build)
  2) Limpia caches Laravel y public/hot
  3) Copia el proyecto a deploy/wasmer-upload excluyendo vendor/,
     node_modules/, .git, dev/ruido y secretos
  4) Inyecta Anybuild y .env.wasmer.example
     (wasmer.toml/app.yaml quedan solo en deploy/ para `wasmer deploy` CLI)
  5) Reescribe bootstrap/cache/*.php de <ruta local> a /app (layout Wasmer)
  NOTA: vendor/ lo instala el autobuild de Wasmer desde composer.json/.lock
  al detectar el proyecto PHP. node_modules/ no se necesita en runtime
  porque public/build ya va compilado.
.NOTES
  PowerShell 5.1 compatible. Ejecutar desde la raiz del proyecto:
    powershell -ExecutionPolicy Bypass -File .\deploy\Build-WasmerDeploy.ps1
#>
$ErrorActionPreference = "Stop"

$Root   = Split-Path -Parent $PSScriptRoot
$Deploy = $PSScriptRoot
$Upload = Join-Path $Deploy "wasmer-upload"

Write-Host "== [1/5] Build de assets (npm run build) ==" -ForegroundColor Cyan
& npm run build --prefix "$Root"
if ($LASTEXITCODE -ne 0) { throw "npm run build fallo (exit $LASTEXITCODE)" }

Write-Host "== [2/5] Limpieza de caches Laravel ==" -ForegroundColor Cyan
& php "$Root\artisan" config:clear
& php "$Root\artisan" route:clear
& php "$Root\artisan" view:clear
& php "$Root\artisan" event:clear
Remove-Item -LiteralPath (Join-Path $Root "public\hot") -Force -ErrorAction SilentlyContinue

Write-Host "== [3/5] Copia ligera a wasmer-upload (sin vendor/ ni node_modules/) ==" -ForegroundColor Cyan
if (Test-Path -LiteralPath $Upload) { Remove-Item -LiteralPath $Upload -Recurse -Force }
New-Item -ItemType Directory -Path $Upload | Out-Null

# .gitignore RAIZ se excluye aparte abajo (si el uploader lo respeta,
# excluiria public/build por /public/build). Los .gitignore marcadores
# de storage/*/ y bootstrap/cache/ SI se conservan: sin ellos los
# directorios vacios se pierden en la subida y artisan falla con
# "Please provide a valid cache path" (config/view.php usa realpath).
$XD = @("node_modules", "vendor", ".git", ".github", "deploy", "tests", "docker", ".opencode", ".fleet", ".idea", ".vscode", ".zed", ".phpunit.cache")
$XF = @(".env", ".env.backup", ".env.production", "auth.json", "npm-debug.log", "yarn-error.log", ".phpunit.result.cache", ".phpactor.json", "database.sqlite", "hot", ".dockerignore", ".gitattributes", ".editorconfig", "dockerfile", "README.md", "phpunit.xml", "phpunit.mysql.xml", "pint.json", "build-icons.php", "nuevo-logo.png", "wasmer.toml", "app.yaml")
& robocopy "$Root" "$Upload" /E /NFL /NDL /NJH /NJS /XD $XD /XF $XF
if ($LASTEXITCODE -ge 8) { throw "robocopy fallo (exit $LASTEXITCODE)" }

# Solo el .gitignore de la raiz (los marcadores anidados se quedan).
Remove-Item -LiteralPath (Join-Path $Upload ".gitignore") -Force -ErrorAction SilentlyContinue

Write-Host "== [4/5] Inyeccion de manifiestos Wasmer ==" -ForegroundColor Cyan
Copy-Item -LiteralPath (Join-Path $Deploy "Anybuild") -Destination (Join-Path $Upload "Anybuild") -Force
Copy-Item -LiteralPath (Join-Path $Deploy ".env.wasmer.example") -Destination (Join-Path $Upload ".env.wasmer.example") -Force

# Restos que robocopy no puede excluir por patron profundo: logs, sqlite, keys.
# OJO: Remove-Item -LiteralPath NO expande '*'; por eso se usa Get-ChildItem.
$LogsDir = Join-Path $Upload "storage\logs"
if (Test-Path -LiteralPath $LogsDir) { Get-ChildItem -LiteralPath $LogsDir -File -ErrorAction SilentlyContinue | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force -ErrorAction SilentlyContinue }
Get-ChildItem -LiteralPath (Join-Path $Upload "storage") -Filter "*.key" -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -LiteralPath (Join-Path $Upload "database") -Filter "*.sqlite" -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -LiteralPath (Join-Path $Upload "storage\framework") -Filter "lsp-*.php" -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
foreach ($cacheDir in @("storage\framework\cache\data", "storage\framework\sessions", "storage\framework\views")) {
  $dir = Join-Path $Upload $cacheDir
  if (Test-Path -LiteralPath $dir) { Get-ChildItem -LiteralPath $dir -File -ErrorAction SilentlyContinue | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force -ErrorAction SilentlyContinue }
}
Remove-Item -LiteralPath (Join-Path $Upload "public\hot") -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath (Join-Path $Upload "public\storage") -Recurse -Force -ErrorAction SilentlyContinue

# Directorios de storage que Laravel espera.
@("framework\cache\data", "framework\sessions", "framework\views", "app\public", "app\private", "logs") | ForEach-Object {
  $d = Join-Path $Upload ("storage\" + $_)
  if (-not (Test-Path -LiteralPath $d)) { New-Item -ItemType Directory -Path $d -Force | Out-Null }
}

Write-Host "== [5/5] Reescritura de rutas bootstrap/cache a /app ==" -ForegroundColor Cyan
$CacheDir = Join-Path $Upload "bootstrap\cache"
if (Test-Path -LiteralPath $CacheDir) {
  # Restos de escrituras atomicas de Laravel: no se usan en runtime.
  Get-ChildItem -LiteralPath $CacheDir -Filter "*.tmp" -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
  Get-ChildItem -LiteralPath $CacheDir -Filter "*.php" -ErrorAction SilentlyContinue | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $content = $content.Replace($Root.Replace("\", "/"), "/app").Replace($Root, "/app")
    [System.IO.File]::WriteAllText($_.FullName, $content)
  }
}

$sizeMB = [Math]::Round(((Get-ChildItem -LiteralPath $Upload -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum / 1MB), 2)
$fileCount = (Get-ChildItem -LiteralPath $Upload -Recurse -File -ErrorAction SilentlyContinue | Measure-Object).Count
Write-Host ""
Write-Host "Listo: deploy\wasmer-upload\ ($fileCount archivos, $sizeMB MB, sin vendor/ ni node_modules/)" -ForegroundColor Green
Write-Host "Arrastra esa CARPETA en https://wasmer.io/new > Upload your project (no ZIP)" -ForegroundColor Green
Write-Host "En Build Settings deja los comandos VACIOS (el archivo Anybuild dirige el build) y activa Enable Database. Ver deploy\README-WASMER.md" -ForegroundColor Yellow
