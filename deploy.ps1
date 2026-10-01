# ==============================================================================
# سكربت النشر الآلي الموحد (One-Click Deploy to GitHub & Production Server)
# منظومة المعهد التخصصي للدراسات الإسلامية - IIIS Academic ERP
# ==============================================================================

param (
    [string]$msg = "تحديث النظام وترقية الميزات"
)

$Host.UI.RawUI.WindowTitle = "Deploying IIIS ERP..."
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "   >>> بدء عملية النشر الآلي الموحد (GitHub -> Production) <<<   " -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# 1. فحص ورفع التعديلات المحلية إلى GitHub
Write-Host "[1/3] فحص وإرسال التعديلات إلى مستودع GitHub..." -ForegroundColor Yellow
git add .
$changes = git status --porcelain
if ($changes) {
    git commit -m "$msg"
    git push origin main
    if ($LASTEXITCODE -ne 0) {
        Write-Host "خطأ أثناء الرفع إلى GitHub! يرجى التحقق من الاتصال." -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ تم رفع التعديلات بنجاح إلى GitHub!" -ForegroundColor Green
} else {
    Write-Host "✓ لا توجد تعديلات محلية جديدة معلقة، سيتم مزامنة السيرفر مباشرة." -ForegroundColor Cyan
}

# 2. تحديث السيرفر الإنتاجي عبر SSH
Write-Host ""
Write-Host "[2/3] الاتصال بالسيرفر (102.203.201.67) وسحب التحديثات وتطبيقها..." -ForegroundColor Yellow

$serverCommands = "cd /var/www/iiis_erp && git pull origin main && /usr/local/bin/frankenphp php-cli artisan view:clear && /usr/local/bin/frankenphp php-cli artisan cache:clear && /usr/local/bin/frankenphp php-cli artisan config:clear && systemctl restart iiis-erp.service && echo 'SUCCESS_DEPLOY'"

ssh root@102.203.201.67 "$serverCommands"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host "   >>> تم النشر والتحديث بنجاح تام على السيرفر! <<<   " -ForegroundColor Green
    Write-Host "   الرابط المباشر: http://102.203.201.67                          " -ForegroundColor Yellow
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "حدث تنبيه أثناء الاتصال بالسيرفر. تأكد من إمكانية الوصول عبر SSH." -ForegroundColor Yellow
}
