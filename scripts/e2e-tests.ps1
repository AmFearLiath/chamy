# E2E test script for Chamy CMS (PowerShell)
# Performs: login, dashboard fetch, create page, verify list

$ErrorActionPreference = 'Stop'

$base = 'http://localhost:8080'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Write-Host "Fetching login page..."
$loginPage = Invoke-WebRequest -Uri "$base/admin/login" -UseBasicParsing -WebSession $session -TimeoutSec 10
$csrf = ($loginPage.Content | Select-String 'name="csrf_token" value="([^"]+)"').Matches[0].Groups[1].Value
Write-Host "CSRF token: $csrf"

Write-Host "Submitting login..."
$body = @{ csrf_token = $csrf; username = 'admin'; password = 'admin' }
$loginResp = Invoke-WebRequest -Uri "$base/admin/login" -Method POST -Body $body -WebSession $session -MaximumRedirection 5 -UseBasicParsing -TimeoutSec 15
Write-Host "Login status: $($loginResp.StatusCode) -> $($loginResp.BaseResponse.ResponseUri)"

Write-Host "Fetching dashboard..."
$dash = Invoke-WebRequest -Uri "$base/admin" -WebSession $session -UseBasicParsing -TimeoutSec 10
if ($dash.Content -match 'Dashboard' ) { Write-Host "Dashboard OK" } else { Write-Host "Dashboard missing" }

Write-Host "Opening create page form..."
$createForm = Invoke-WebRequest -Uri "$base/admin/content/page/create" -WebSession $session -UseBasicParsing -TimeoutSec 10
$csrf2 = ($createForm.Content | Select-String 'name="csrf_token" value="([^"]+)"').Matches[0].Groups[1].Value

Write-Host "Creating Testseite..."
$storeBody = @{ 'csrf_token' = $csrf2; 'data[title]' = 'E2E Testseite'; 'data[slug]' = 'e2e-testseite'; 'data[excerpt]' = 'E2E Test'; 'data[body]' = 'Automated test page.'; 'state' = 'draft' }
$store = Invoke-WebRequest -Uri "$base/admin/content/page/store" -Method POST -Body $storeBody -WebSession $session -MaximumRedirection 5 -UseBasicParsing -TimeoutSec 15
Write-Host "Store status: $($store.StatusCode) -> $($store.BaseResponse.ResponseUri)"

Write-Host "Verifying content list..."
$list = Invoke-WebRequest -Uri "$base/admin/content/page" -WebSession $session -UseBasicParsing -TimeoutSec 10
if ($list.Content -match 'E2E Testseite') { Write-Host "Content created and visible in list" } else { Write-Host "Created content not found in list"; exit 2 }

Write-Host "E2E tests completed successfully."; exit 0
