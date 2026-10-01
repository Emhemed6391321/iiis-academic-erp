param (
    [string]$msg = "System update"
)

$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "   >>> Starting One-Click Deploy (GitHub -> Server) <<<   " -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Cyan

# 1. Commit and push to GitHub
Write-Host "`n[1/3] Checking and pushing local changes to GitHub..." -ForegroundColor Yellow
git add .
$status = git status --porcelain
if ($status) {
    git commit -m "$msg"
    git push origin main
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error pushing to GitHub!" -ForegroundColor Red
        exit 1
    }
    Write-Host "Pushed to GitHub successfully!" -ForegroundColor Green
} else {
    Write-Host "No new local changes to commit. Proceeding to sync server." -ForegroundColor Cyan
}

# 2. Deploy on remote server
Write-Host "`n[2/3] Connecting to VPS (102.203.201.67) and applying updates..." -ForegroundColor Yellow

$remoteCmd = "cd /var/www/iiis_erp && git pull origin main && /usr/local/bin/frankenphp php-cli artisan view:clear && /usr/local/bin/frankenphp php-cli artisan cache:clear && /usr/local/bin/frankenphp php-cli artisan config:clear && systemctl restart iiis-erp.service"

ssh root@102.203.201.67 "$remoteCmd"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n================================================================" -ForegroundColor Green
    Write-Host "   >>> Deploy successful! Site is live on http://102.203.201.67 <<<" -ForegroundColor Green
    Write-Host "================================================================" -ForegroundColor Green
} else {
    Write-Host "`nDeployment finished with warnings. Please check SSH connection." -ForegroundColor Yellow
}
