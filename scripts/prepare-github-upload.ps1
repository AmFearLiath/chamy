param(
    [string]$Source = "f:/Chamy",
    [string]$Destination = "f:/Chamy-github-clean"
)

$ErrorActionPreference = 'Stop'

if (Test-Path $Destination) {
    Remove-Item $Destination -Recurse -Force
}

New-Item -ItemType Directory -Path $Destination | Out-Null

$exclude = @(
    '.git',
    'vendor',
    'docs',
    'tests',
    '.vscode',
    '.idea',
    '.phpunit.cache',
    'storage',
    'node_modules',
    '.env',
    '.env.bak.20260309165747'
)

Get-ChildItem -Path $Source -Force | ForEach-Object {
    if ($exclude -contains $_.Name) {
        return
    }

    $target = Join-Path $Destination $_.Name
    if ($_.PSIsContainer) {
        Copy-Item $_.FullName -Destination $target -Recurse -Force
    } else {
        Copy-Item $_.FullName -Destination $target -Force
    }
}

# Minimal storage placeholders expected by app
New-Item -ItemType Directory -Path (Join-Path $Destination 'storage/cache') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $Destination 'storage/logs') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $Destination 'storage/sessions') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $Destination 'storage/media') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $Destination 'storage/trash') -Force | Out-Null
New-Item -ItemType File -Path (Join-Path $Destination 'storage/cache/.gitkeep') -Force | Out-Null
New-Item -ItemType File -Path (Join-Path $Destination 'storage/logs/.gitkeep') -Force | Out-Null
New-Item -ItemType File -Path (Join-Path $Destination 'storage/sessions/.gitkeep') -Force | Out-Null
New-Item -ItemType File -Path (Join-Path $Destination 'storage/media/.gitkeep') -Force | Out-Null
New-Item -ItemType File -Path (Join-Path $Destination 'storage/trash/.gitkeep') -Force | Out-Null

Write-Host "Clean upload folder created: $Destination"
Write-Host "Next: cd $Destination; git init; git remote add origin https://github.com/AmFearLiath/chamy.git"
