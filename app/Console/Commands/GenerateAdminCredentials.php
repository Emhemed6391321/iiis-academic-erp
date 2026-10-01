<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateAdminCredentials extends Command
{
    protected $signature = 'app:admin-credentials {email? : البريد الإلكتروني للمستخدم} {--reset : إعادة تعيين كلمة المرور بكلمة عشوائية قوية} {--password= : تعيين كلمة مرور محددة للمستخدم} {--disable-mfa : تعطيل التحقق بخطوتين TOTP}';
    protected $description = 'إدارة وتوليد بيانات الدخول للمستخدمين الإداريين بكلمات مرور آمنة ومشفرة';

    public function handle(): int
    {
        $email = $this->argument('email');
        $reset = $this->option('reset');
        $specifiedPassword = $this->option('password');
        $disableMfa = $this->option('disable-mfa');

        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $role = \App\Models\Role::firstOrCreate(
                    ['name' => 'super_admin'],
                    ['display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']
                );
                $initialPass = $specifiedPassword ?: '112200225124';
                $user = User::create([
                    'name' => 'المدير العام للمعهد التخصصي',
                    'email' => $email,
                    'national_id' => '119780000001',
                    'role_id' => $role->id,
                    'is_active' => true,
                    'must_change_password' => false,
                    'two_factor_enabled' => false,
                    'two_factor_secret' => null,
                    'two_factor_confirmed_at' => null,
                    'password' => $initialPass,
                ]);

                $this->info("تم إنشاء المستخدم وتعيين كلمة المرور بنجاح للمستخدم: {$user->name}");
                $this->table(['الحقل', 'القيمة'], [
                    ['الاسم', $user->name],
                    ['البريد الإلكتروني', $user->email],
                    ['كلمة المرور', $initialPass],
                ]);
                return 0;
            }

            if ($disableMfa) {
                $user->two_factor_enabled = false;
                $user->two_factor_secret = null;
                $user->two_factor_confirmed_at = null;
                $user->save();
                $this->info("✓ تم إلغاء التحقق بخطوتين (MFA/TOTP) بنجاح للمستخدم: {$user->name}");
            }

            if ($specifiedPassword) {
                $user->password = $specifiedPassword;
                $user->must_change_password = false;
                $user->two_factor_enabled = false;
                $user->two_factor_secret = null;
                $user->two_factor_confirmed_at = null;
                $user->is_active = true;
                $user->save();

                $this->info("تم تحديث كلمة المرور وتعطيل MFA بنجاح للمستخدم: {$user->name}");
                $this->table(['الحقل', 'القيمة'], [
                    ['الاسم', $user->name],
                    ['البريد الإلكتروني', $user->email],
                    ['كلمة المرور', $specifiedPassword],
                    ['التحقق بخطوتين', 'معطل'],
                ]);
                return 0;
            }

            if ($reset) {
                $plainPassword = Str::password(16, true, true, true, false);
                $user->password = $plainPassword;
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
