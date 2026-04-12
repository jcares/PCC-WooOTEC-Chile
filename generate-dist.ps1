# ============================================
# Script: Generate Distribution - v3.0.8+
# ============================================

param()

$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"

$SourceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$DistRoot   = Join-Path $SourceRoot "dist\woo-otec-moodle"

function Write-Log {
    param([string]$Message,[string]$Level="INFO")
    $color="White"
    switch ($Level) {
        "ERROR"{$color="Red"}
        "WARNING"{$color="Yellow"}
        "OK"{$color="Green"}
        "INFO"{$color="Cyan"}
    }
    Write-Host "[$((Get-Date).ToString('HH:mm:ss'))][$Level] $Message" -ForegroundColor $color
}

function Copy-Directory {
    param([string]$Source, [string]$Dest)
    if (!(Test-Path $Dest)) { New-Item -ItemType Directory -Path $Dest -Force | Out-Null }
    Get-ChildItem -Path $Source -Recurse -Force | ForEach-Object {
        $target = $_.FullName -replace [regex]::Escape($Source), $Dest
        if ($_.PSIsContainer) {
            if (!(Test-Path $target)) { New-Item -ItemType Directory -Path $target -Force | Out-Null }
        } else {
            Copy-Item -Path $_.FullName -Destination $target -Force
        }
    }
}

Write-Log "======================================"
Write-Log "Build DIST v3.0.8+ (Iniciando...)"
Write-Log "======================================"

if (Test-Path $DistRoot) {
    Remove-Item $DistRoot -Recurse -Force -ErrorAction SilentlyContinue | Out-Null
    Write-Log "Directorio dist/ limpiado" "WARNING"
}

New-Item -ItemType Directory -Path $DistRoot -Force | Out-Null
Write-Log "Directorio dist/ preparado" "OK"

$DirsToSync = @("admin", "includes", "frontend", "templates", "assets", "logs", "languages")

foreach ($dir in $DirsToSync) {
    $src = Join-Path $SourceRoot $dir
    $dst = Join-Path $DistRoot $dir
    
    if (Test-Path $src) {
        Write-Log "Copiando $dir/" "INFO"
        Copy-Directory -Source $src -Dest $dst
    }
}

$RootFiles = @("woo-otec-moodle.php", "uninstall.php", "AUDIT_REPORT.md")
foreach ($file in $RootFiles) {
    $src = Join-Path $SourceRoot $file
    if (Test-Path $src) {
        Write-Log "Copiando $file" "INFO"
        Copy-Item -Path $src -Destination (Join-Path $DistRoot $file) -Force
    }
}

Write-Log "Copia completada" "OK"

$SecurityDirs = @("admin", "includes", "frontend", "templates", "assets", "logs", "languages")

foreach ($sdir in $SecurityDirs) {
    $indexPath = Join-Path $DistRoot "$sdir\index.php"
    if (!(Test-Path $indexPath)) {
        New-Item -ItemType File -Path $indexPath -Force | Out-Null
        Set-Content $indexPath ("<?php`n// Silence is golden`n")
        Write-Log "Creado $sdir/index.php" "OK"
    }
}

Write-Log "======================================"
Write-Log "Validando integridad..."
Write-Log "======================================"

$CriticalFiles = @(
    "woo-otec-moodle.php",
    "uninstall.php",
    "includes/class-logger.php",
    "includes/class-api-client.php",
    "includes/class-admin-settings.php",
    "includes/class-course-sync.php",
    "includes/class-enrollment-manager.php",
    "includes/class-email-manager.php",
    "includes/class-metadata-manager.php",
    "includes/class-template-manager.php",
    "includes/class-template-customizer.php",
    "includes/class-preview-generator.php",
    "includes/class-cron-manager.php",
    "includes/class-sso-manager.php",
    "includes/class-field-mapper.php",
    "includes/class-exception-handler.php",
    "admin/css/admin-style.css",
    "admin/css/admin-forms.css",
    "admin/partials/cron-display.php",
    "languages/woo-otec-moodle.pot",
    "AUDIT_REPORT.md"
)

$missingFiles = @()
$foundFiles = 0

foreach ($criticalFile in $CriticalFiles) {
    $filePath = Join-Path $DistRoot $criticalFile
    
    if (Test-Path $filePath) {
        Write-Log "OK $criticalFile" "OK"
        $foundFiles++
    } else {
        Write-Log "FALTA $criticalFile" "ERROR"
        $missingFiles += $criticalFile
    }
}

Write-Log "======================================"
Write-Log "Archivos: $foundFiles / $($CriticalFiles.Count)" "INFO"

if ($missingFiles.Count -gt 0) {
    Write-Log "ERRORES: $($missingFiles.Count) archivo/s faltante/s" "ERROR"
    foreach ($missing in $missingFiles) {
        Write-Log "  - $missing" "ERROR"
    }
    exit 1
}

Write-Log "======================================"
Write-Log "BUILD COMPLETADO OK" "OK"
Write-Log "Ruta: $DistRoot"
Write-Log "======================================"