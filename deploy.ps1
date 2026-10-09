param (
    [string]$msg = "System update"
)

$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$server    = "root@102.203.201.67"
$appDir    = "/var/www/iiis_erp"
$php       = "/usr/local/bin/frankenphp php-cli"

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "   >>> Starting One-Click Deploy (GitHub -> Server) <<<   " -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Cyan

# 0. Deploy only from main
$branch = (git rev-parse --abbrev-ref HEAD).Trim()
if ($branch -ne "main") {
    Write-Host "Current branch is '$branch'. Deploy runs from 'main' only (merge your branch first)." -ForegroundColor Red
    exit 1
}

# 1. Commit local changes, then push whatever is ahead of GitHub
Write-Host "`n[1/3] Checking and pushing local changes to GitHub..." -ForegroundColor Yellow
git add .
$status = git status --porcelain
if ($status) {
    git commit -m "$msg"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Commit failed!" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "No new local changes to commit." -ForegroundColor Cyan
}

git fetch origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "Could not reach GitHub (git fetch failed)!" -ForegroundColor Red
    exit 1
}

# Commits that exist locally but not on GitHub (already-committed work counts too)
$behind = [int](git rev-list --count main..origin/main)
$ahead  = [int](git rev-list --count origin/main..main)
if ($behind -gt 0) {
    Write-Host "GitHub has $behind commit(s) that are not in your local main. Pull/merge them first, then deploy again." -ForegroundColor Red
    exit 1
}
if ($ahead -gt 0) {
    Write-Host "Pushing $ahead commit(s) to GitHub..." -ForegroundColor Cyan
    git push origin main
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error pushing to GitHub!" -ForegroundColor Red
        exit 1
    }
    Write-Host "Pushed to GitHub successfully!" -ForegroundColor Green
} else {
    Write-Host "GitHub is already up to date." -ForegroundColor Cyan
}

# 2. Make sure the server working tree is clean before pulling (hand edits on the VPS block the pull)
Write-Host "`n[2/3] Connecting to VPS (102.203.201.67) and checking its working tree..." -ForegroundColor Yellow

$dirty = ssh $server "cd $appDir && git status --porcelain --untracked-files=no"
if ($LASTEXITCODE -ne 0) {
    Write-Host "SSH failed - nothing was deployed. Check the connection and try again." -ForegroundColor Red
    exit 1
}
if ($dirty) {
    Write-Host "The server has local changes that would block or be overwritten by the deploy:" -ForegroundColor Red
    $dirty | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
    Write-Host "`nNothing was deployed. On the server, inspect them with 'git diff', keep a copy if needed," -ForegroundColor Yellow
    Write-Host "then discard with 'git checkout -- <file>' and run the deploy again." -ForegroundColor Yellow
    exit 1
}

# 3. Deploy on remote server (fast-forward only: never creates a merge commit on the server)
Write-Host "`n[3/3] Applying updates on the server..." -ForegroundColor Yellow

$remoteCmd = "cd $appDir && git pull --ff-only origin main && $php artisan migrate --force && $php artisan view:clear && $php artisan route:clear && $php artisan cache:clear && $php artisan config:clear && systemctl restart iiis-erp.service"

ssh $server "$remoteCmd"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n================================================================" -ForegroundColor Green
    Write-Host "   >>> Deploy successful! Site is live on http://102.203.201.67 <<<" -ForegroundColor Green
    Write-Host "================================================================" -ForegroundColor Green
} else {
    Write-Host "`nDeployment FAILED (a command on the server returned an error). Review the output above;" -ForegroundColor Red
    Write-Host "the site may be running the previous version or be partially updated." -ForegroundColor Red
    exit 1
}
