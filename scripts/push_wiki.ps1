<#
Build and publish the complete GitHub Wiki content directly to the Wiki repository.
No local docs/wiki directory is required.

Usage:
  .\scripts\push_wiki.ps1
  .\scripts\push_wiki.ps1 -DryRun
#>
param(
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "Preparing GitHub Wiki publish..."

function Get-WikiUrl {
    $origin = git remote get-url origin 2>$null
    if (-not $origin) {
        throw "Could not determine 'origin' remote."
    }

    if ($origin -match "\.git$") {
        return ($origin -replace "\.git$", ".wiki.git")
    }

    return ($origin + ".wiki.git")
}

function Get-WikiPages {
    $pages = [ordered]@{}

    $pages['Home.md'] = @"
# Chamy Wiki

Welcome to the Chamy project wiki.

Use the sidebar to navigate architecture, modules, API, development, deployment, security, and troubleshooting guides.

## Quick Start

1. Clone repository
2. Install dependencies
3. Configure environment
4. Run migrations
5. Start local server

See Getting-Started for details.
"@

    $pages['_Sidebar.md'] = @"
- [Home](Home)
- [Getting Started](Getting-Started)
- [Architecture](Architecture)
- [Admin Guide](Admin-Guide)
- [Developer Guide](Developer-Guide)
- [Module Reference](Module-Reference)
- [API Reference](API-Reference)
- [Tutorials](Tutorials)
- [Deployment](Deployment)
- [Security and Operations](Security-and-Ops)
- [Troubleshooting](Troubleshooting)
- [FAQ](FAQ)
"@

    $pages['_Footer.md'] = @"
---
Copyright (c) 2026 Liath

This wiki is maintained by the project owner.
"@

    $pages['Getting-Started.md'] = @"
# Getting Started

## Requirements
- PHP 8.x
- Composer
- Node.js + pnpm
- Database

## Setup
```bash
composer install
pnpm install
php chamy migrate
php -S localhost:8080 -t public public/index.php
```
"@

    $pages['Architecture.md'] = @"
# Architecture

## Core Layers
- core/: bootstrap, kernel, managers
- modules/: feature modules
- themes/: admin and frontend templates
- storage/: runtime data (cache, logs, secrets)

## Request Flow
1. Front controller
2. Kernel boot
3. Router dispatch
4. Controller action
5. Twig render / API response
"@

    $pages['Admin-Guide.md'] = @"
# Admin Guide

## Main Areas
- Settings and Asset Library
- Module Manager
- Roles and Permissions
- Content management

## Secrets
Store secrets in storage/secrets only.
"@

    $pages['Developer-Guide.md'] = @"
# Developer Guide

## Standards
- PSR-12 style
- Unit + integration tests
- Keep modules isolated

## Useful Commands
```bash
composer install
php chamy migrate
pnpm run dev
```
"@

    $pages['Module-Reference.md'] = @"
# Module Reference

Create one page per module with:
- Purpose
- Routes
- Permissions
- Database tables
- Hooks
- Upgrade notes
"@

    $pages['API-Reference.md'] = @"
# API Reference

Document each endpoint with:
- Method + URL
- Auth requirements
- Request payload
- Response schema
- Error cases

Include practical request/response examples.
"@

    $pages['Tutorials.md'] = @"
# Tutorials

Recommended tutorials:
- Build a custom module
- Add admin navigation entry
- Create and run migration
- Add a Twig admin page
"@

    $pages['Deployment.md'] = @"
# Deployment

## Checklist
- Production env configured
- Migrations applied
- Cache warmed up
- Health endpoint checked

Health endpoint: /api/v1/system/health
"@

    $pages['Security-and-Ops.md'] = @"
# Security and Operations

## Security Basics
- Never commit secrets
- Rotate keys periodically
- Keep dependencies updated

## Ops Basics
- Monitor logs
- Use backups
- Test restore regularly
"@

    $pages['Troubleshooting.md'] = @"
# Troubleshooting

## Common Issues
- Migration failures
- Permission mismatch
- Missing module sidebar entries
- Asset build errors

Include known fixes and commands.
"@

    $pages['FAQ.md'] = @"
# FAQ

## Where are API keys stored?
storage/secrets/

## How to validate system health?
GET /api/v1/system/health
"@

    return $pages
}

$wikiUrl = Get-WikiUrl
$tmp = Join-Path $env:TEMP ("chamy_wiki_" + [Guid]::NewGuid().ToString())

Write-Host "Cloning wiki repo: $wikiUrl"
git clone $wikiUrl $tmp | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Failed to clone wiki repository. Ensure Wiki is enabled and you have access."
}

$pages = Get-WikiPages

Write-Host "Writing wiki pages..."
foreach ($entry in $pages.GetEnumerator()) {
    $path = Join-Path $tmp $entry.Key
    Set-Content -Path $path -Value $entry.Value -Encoding UTF8
}

Push-Location $tmp
try {
    git add --all
    git status --short | Out-Host

    if ($DryRun) {
        Write-Host "DryRun enabled. No commit or push executed."
        exit 0
    }

    git commit -m "Publish complete Chamy wiki" 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Pushing wiki updates..."
        $currentBranch = git rev-parse --abbrev-ref HEAD
        if (-not $currentBranch) { $currentBranch = 'master' }
        git push origin $currentBranch | Out-Host
        if ($LASTEXITCODE -ne 0) {
            throw "Wiki push failed."
        }
    } else {
        Write-Host "No changes to commit. Wiki already up to date."
    }
}
finally {
    Pop-Location
}

Write-Host "Wiki publish finished."
