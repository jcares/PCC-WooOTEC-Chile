# ============================================
# Script: Copy to Distribution - v3.0.9
# ============================================
# Este script copia todos los archivos refactorizados a dist/

param()

$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"

$SourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$DistRoot   = Join-Path $SourceRoot "dist\woo-otec-moodle"

Write-Host "`n========== COPIANDO A DIST ==========" -ForegroundColor Cyan

# Función para copiar con validación
function Copy-SafeItem {
    param(
        [string]$SourceItem,
        [string]$DestPath
    )
    
    $src = Join-Path $SourceRoot $SourceItem
    
    if (Test-Path $src) {
        if ((Get-Item $src).PSIsContainer) {
            # Es un directorio
            Get-ChildItem -Path $src -Recurse -Force | Where-Object { !$_.PSIsContainer } | ForEach-Object {
                $rel = $_.FullName.Substring($src.Length + 1)
                $target = Join-Path $DestPath $rel
                $targetDir = Split-Path -Parent $target
                
                if (!(Test-Path $targetDir)) {
                    New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
                }
                
                Copy-Item -Path $_.FullName -Destination $target -Force | Out-Null
            }
            Write-Host "✓ Copiado: $SourceItem" -ForegroundColor Green
        } else {
            # Es un archivo
            $target = Join-Path $DestPath (Split-Path -Leaf $SourceItem)
            Copy-Item -Path $src -Destination $target -Force | Out-Null
            Write-Host "✓ Copiado: $SourceItem" -ForegroundColor Green
        }
    } else {
        Write-Host "✗ No encontrado: $SourceItem" -ForegroundColor Yellow
    }
}

# Copiar directorios principales
@("admin", "includes", "frontend", "templates", "assets", "languages", "logs") | ForEach-Object {
    Copy-SafeItem -SourceItem $_ -DestPath $DistRoot
}

# Copiar archivos principales
@("woo-otec-moodle.php", "uninstall.php") | ForEach-Object {
    Copy-SafeItem -SourceItem $_ -DestPath $DistRoot
}

Write-Host "`n========== COPIA COMPLETADA ==========" -ForegroundColor Green
Write-Host "Archivos refactorizados listos en: $DistRoot" -ForegroundColor Green
Write-Host ""
