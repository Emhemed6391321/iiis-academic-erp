<?php

namespace App\Services;

use App\Models\AdministrativeSetting;
use App\Models\JobPosition;
use App\Models\EmployeePlacement;
use App\Models\OfficialDocumentSignatory;
use App\Models\OrganizationalUnit;
use App\Models\SystemAuditTrail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminSettingsService
{
    const CACHE_KEY_PROFILE = 'admin_settings_institute_profile';
    const CACHE_KEY_ALL = 'admin_settings_all_map';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the central institute profile as a key-value associative array.
     */
    public static function getInstituteProfile(): array
    {
        return Cache::remember(self::CACHE_KEY_PROFILE, self::CACHE_TTL, function () {
            $settings = AdministrativeSetting::where('setting_group', 'institute_profile')
                ->orWhere('setting_group', 'branding')
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            return [
                'state_name'             => $settings['state_name'] ?? 'دولة ليبيا',
                'supervising_body'       => $settings['supervising_body'] ?? 'الهيئة العامة للأوقاف والشؤون الإسلامية',
                'supervising_department' => $settings['supervising_department'] ?? 'إدارة التعليم الأصيل',
                'institute_name'         => $settings['institute_name'] ?? 'المعهد التخصصي للعلوم الشرعية',
                'branch_label'           => $settings['branch_label'] ?? 'الفرع الرئيسي',
                'phone'                  => $settings['phone'] ?? '+218 21 000 0000',
                'email'                  => $settings['email'] ?? 'info@islamic-institute.edu.ly',
                'address'                => $settings['address'] ?? 'طرابلس - ليبيا',
                'website'                => $settings['website'] ?? 'https://islamic-institute.edu.ly',
                'pobox'                  => $settings['pobox'] ?? 'ص.ب 12345',
                'logo_url'               => $settings['logo_url'] ?? '/images/logo.png',
                'stamp_url'              => $settings['stamp_url'] ?? '',
                'header_title'           => $settings['header_title'] ?? 'المعهد التخصصي للعلوم الشرعية',
                'footer_text'            => $settings['footer_text'] ?? 'المعهد التخصصي للعلوم الشرعية - إدارة التعليم الأصيل',
            ];
        });
    }

    /**
     * Alias for getInstituteProfile() to maintain backward compatibility.
     */
    public static function getProfile(): array
    {
        return self::getInstituteProfile();
    }

    /**
     * Get a specific setting value by key with optional default fallback.
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $all = self::getAllSettingsMap();
        return $all[$key] ?? $default;
    }

    /**
     * Get all administrative settings as a key => value array.
     */
    public static function getAllSettingsMap(): array
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL, function () {
            return AdministrativeSetting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Save or update a setting and clear caches.
     */
    public static function setSetting(string $key, mixed $value, string $group = 'general', ?string $label = null, string $type = 'string', ?int $userId = null, ?string $description = null): AdministrativeSetting
    {
        $oldSetting = AdministrativeSetting::where('key', $key)->first();
        $oldValue = $oldSetting ? $oldSetting->value : null;

        $setting = AdministrativeSetting::updateOrCreate(
            ['key' => $key],
            [
                'setting_group' => $group,
                'value'         => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value,
                'label'         => $label ?? ($oldSetting->label ?? $key),
                'type'          => $type,
                'description'   => $description ?? ($oldSetting->description ?? null),
                'updated_by'    => $userId ?? auth()->id(),
            ]
        );

        self::clearCache();

        if ($oldValue !== $value) {
            self::recordAudit(
                'update_setting',
                'AdministrativeSetting',
                $setting->id,
                ['key' => $key, 'value' => $oldValue],
                ['key' => $key, 'value' => $value],
                $userId,
                "تحديث إعداد إداري: {$key}"
            );
        }

        return $setting;
    }

    /**
     * Get signatories configuration for a document type.
     */
    public static function getSignatoriesFor(string $documentCode, ?int $branchId = null): array
    {
        $signatories = OfficialDocumentSignatory::with(['jobPosition', 'user'])
            ->where('document_code', $documentCode)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($signatories as $sig) {
            // The title and the name come only from this directory. Nothing is invented when a slot is unset.
            $jobTitle = $sig->show_title
                ? (string) ($sig->custom_title_override ?: ($sig->jobPosition?->title ?? ''))
                : '';

            $placement = self::resolveSignatoryPlacement($sig, $branchId);

            $personName = $sig->user ? $sig->user->name : ($placement?->user?->name ?? '');

            // Two switches decide the name: the slot's own switch, and the placement's switch (the name is hidden if either is off).
            if (!$sig->show_name || ($placement && !$placement->show_name_on_documents)) {
                $personName = '';
            }

            // The stamp is printed at this slot only when the slot is active, its stamp switch is on and the stamp file exists.
            $result[$sig->slot_key] = [
                'slot_key'   => $sig->slot_key,
                'slot_label' => $sig->slot_label,
                'title'      => $jobTitle,
                'name'       => $personName,
                'show_name'  => (bool) $sig->show_name,
                'show_title' => (bool) $sig->show_title,
                'show_stamp' => (bool) $sig->show_stamp,
                'stamp_url'  => $sig->show_stamp ? self::getStampUrl() : '',
            ];
        }

        return $result;
    }

    /**
     * The saved official stamp, or '' when none was uploaded or its file is gone (so a broken image is never printed).
     */
    public static function getStampUrl(): string
    {
        $url = trim((string) (self::getInstituteProfile()['stamp_url'] ?? ''));
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/storage/')) {
            return Storage::disk('public')->exists(substr($url, strlen('/storage/'))) ? $url : '';
        }

        return $url;
    }

    /**
     * The stamp a document prints: the first slot of the resolved signatories that carries one, otherwise none.
     */
    public static function stampFromSignatories(array $signatories): string
    {
        foreach ($signatories as $slot) {
            if (!empty($slot['stamp_url'])) {
                return $slot['stamp_url'];
            }
        }

        return '';
    }

    /**
     * التسكين الحالي الذي يحدد صاحب الصفة في خانة التوقيع (إن وُجد).
     */
    private static function resolveSignatoryPlacement(OfficialDocumentSignatory $sig, ?int $branchId): ?EmployeePlacement
    {
        // خانة مسندة لشخص بعينه: تسكينه الحالي (في الصفة نفسها إن وُجد) هو الذي يحكم ظهور اسمه
        if ($sig->user_id) {
            $own = EmployeePlacement::with('user')
                ->where('user_id', $sig->user_id)
                ->where('is_current', true)
                ->where('status', 'active');

            $samePosition = $sig->job_position_id
                ? (clone $own)->where('job_position_id', $sig->job_position_id)->first()
                : null;

            return $samePosition ?: $own->first();
        }

        if (!$sig->job_position_id) {
            return null;
        }

        $query = EmployeePlacement::with('user')
            ->where('job_position_id', $sig->job_position_id)
            ->where('is_current', true)
            ->where('status', 'active');

        $holders = $query->get();

        if ($branchId) {
            $own = $holders->firstWhere('branch_id', $branchId);
            if ($own) {
                return $own;
            }
        }

        // A position held by one person for the whole institute is central. A position held in several branches
        // is a branch position, and another branch's holder must never sign for this branch.
        return $holders->count() === 1 ? $holders->first() : null;
    }

    /**
     * كل دور في مصفوفة الصلاحيات له صفة وظيفية معتمدة مرتبطة به، ومسماها = مسمّى الدور.
     * آمن للتكرار: ينشئ الصفة الناقصة فقط ويحدّث المسمى إن تغيّر.
     */
    public static function syncRolePositions(): void
    {
        foreach (\App\Models\Role::all() as $role) {
            $position = JobPosition::where('role_id', $role->id)->first();

            if ($position) {
                if ($position->title !== $role->display_name) {
                    $position->update(['title' => $role->display_name]);
                }
                continue;
            }

            $code = 'ROLE_' . strtoupper($role->name);
            if (JobPosition::where('code', $code)->exists()) {
                $code .= '_' . $role->id;
            }

            JobPosition::create([
                'code'                   => substr($code, 0, 50),
                'role_id'                => $role->id,
                'title'                  => $role->display_name,
                'organizational_unit_id' => null,
                'level_order'            => $role->scope_type === 'GLOBAL_SCOPE' ? 1 : 2,
                'is_active'              => true,
                'description'            => $role->description,
            ]);
        }
    }

    /**
     * Clear all settings caches.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_PROFILE);
        Cache::forget(self::CACHE_KEY_ALL);
    }

    /**
     * Log an administrative audit record with before/after state.
     */
    public static function recordAudit(string $action, string $entityType, ?int $entityId, ?array $oldValues, ?array $newValues, ?int $userId = null, ?string $notes = null): void
    {
        try {
            SystemAuditTrail::log(
                $action,
                $notes ?? "إجراء إداري على {$entityType}",
                [],
                $userId,
                null,
                $entityType,
                $entityId,
                $oldValues ?? [],
                $newValues ?? [],
                'INFO'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Audit trail error: ' . $e->getMessage());
        }
    }
}
