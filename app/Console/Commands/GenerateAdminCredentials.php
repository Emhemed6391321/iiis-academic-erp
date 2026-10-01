<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateAdminCredentials extends Command
{
    protected $signature = 'app:admin-credentials {email? : البريد الإلكتروني للمستخدم} {--reset : إعادة تعيين كلمة المرور بكلمة عشوائية قوية}';
    protected $description = 'إدارة وتوليد بيانات الدخول للمستخدمين الإداريين بكلمات مرور آمنة ومشفرة';

    public function handle(): int
    {
        $email = $this->argument('email');
        $reset = $this->option('reset');

        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("المستخدم بالبريد {$email} غير موجود.");
                return 1;
            }

            if ($reset) {
                $plainPassword = Str::password(16, true, true, true, false);
                $user->password = Hash::make($plainPassword);
                $user->must_change_password = true;
                $user->save();

                $this->info("تمت إعادة تعيين كلمة المرور بنجاح للمستخدم: {$user->name}");
                $this->table(['الحقل', 'القيمة'], [
                    ['الاسم', $user->name],
                    ['البريد الإلكتروني', $user->email],
                    ['كلمة المرور المؤقتة', $plainPassword],
                    ['تغيير إلزامي عند أول دخول', 'نعم (مفعل)'],
                ]);
                $this->warn('تحذير: لا تشارك كلمة المرور عبر وسائط غير آمنة.');
            } else {
                $this->table(['الحقل', 'القيمة'], [
                    ['الاسم', $user->name],
                    ['البريد الإلكتروني', $user->email],
                    ['الدور', $user->role ? $user->role->display_name : 'غير محدد'],
                    ['النطاق', $user->hasGlobalAccessScope() ? 'GLOBAL_SCOPE' : 'BRANCH_SCOPE'],
                    ['التحقق بخطوتين (MFA)', $user->two_factor_enabled ? 'مفعل' : 'غير مفعل'],
                    ['تغيير كلمة المرور إلزامي', $user->must_change_password ? 'نعم' : 'لا'],
                ]);
            }

            return 0;
        }

        // Display list of all administrative accounts
        $users = User::with('role', 'branch')->get();
        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                $u->id,
                $u->name,
                $u->email,
                $u->role ? $u->role->display_name : 'بلا دور',
                $u->branch ? $u->branch->name : 'الإدارة العامة',
                $u->two_factor_enabled ? 'مفعل' : 'غير مفعل',
                $u->must_change_password ? 'نعم' : 'لا',
            ];
        }

        $this->info('قائمة الحسابات الإدارية المعتمدة في منظومة IIIS:');
        $this->table(['#', 'الاسم', 'البريد الإلكتروني', 'الدور الوظيفي', 'الفرع / النطاق', 'MFA', 'إلزام تغيير كلمة المرور'], $rows);
        $this->comment('لتوليد كلمة مرور جديدة لحساب معين: php artisan app:admin-credentials user@iiis.sch.ly --reset');

        return 0;
    }
}
