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
                'institute_name'         => $settings['institute_name'] ?? 'المعهد المتوسط للدراسات الإسلامية',
                'branch_label'           => $settings['branch_label'] ?? 'الفرع الرئيسي',
                'phone'                  => $settings['phone'] ?? '+218 21 000 0000',
                'email'                  => $settings['email'] ?? 'info@islamic-institute.edu.ly',
                'address'                => $settings['address'] ?? 'طرابلس - ليبيا',
                'website'                => $settings['website'] ?? 'https://islamic-institute.edu.ly',
                'pobox'                  => $settings['pobox'] ?? 'ص.ب 12345',
                'logo_url'               => $settings['logo_url'] ?? '/images/logo.png',
                'stamp_url'              => $settings['stamp_url'] ?? '',
                'header_title'           => $settings['header_title'] ?? 'المعهد المتوسط للدراسات الإسلامية',
                'footer_text'            => $settings['footer_text'] ?? 'المعهد المتوسط للدراسات الإسلامية - إدارة التعليم الأصيل',
            ];
        });
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
            $jobTitle = $sig->custom_title_override ?: ($sig->jobPosition ? $sig->jobPosition->title : 'المسؤول الإداري');
            $personName = $sig->user ? $sig->user->name : '';

            // If no user directly assigned on the signatory slot, try to find the current employee placed in that job position
            if (empty($personName) && $sig->job_position_id) {
                $query = EmployeePlacement::with('user')
                    ->where('job_position_id', $sig->job_position_id)
                    ->where('is_current', true)
                    ->where('status', 'active');
                
                if ($branchId) {
                    $placement = (clone $query)->where('branch_id', $branchId)->first() ?: $query->first();
                } else {
                    $placement = $query->first();
                }

                if ($placement && $placement->user) {
                    $personName = $placement->user->name;
                }
            }

            $result[$sig->slot_key] = [
                'slot_key'   => $sig->slot_key,
                'slot_label' => $sig->slot_label,
                'title'      => $jobTitle,
                'name'       => $personName,
            ];
        }

        return $result;
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
            SystemAuditTrail::create([
                'user_id'     => $userId ?? auth()->id() ?? 1,
                'action_type' => $action,
                'table_name'  => $entityType,
                'record_id'   => $entityId ?? 0,
                'old_values'  => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'  => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip_address'  => request()->ip() ?? '127.0.0.1',
                'user_agent'  => request()->userAgent() ?? 'System / AdminSettingsService',
                'notes'       => $notes,
            ]);
        } catch (\Throwable $e) {
            // Ignore audit trail fail if table structure is slightly different
            \Illuminate\Support\Facades\Log::warning('Audit trail error: ' . $e->getMessage());
        }
    }
}
