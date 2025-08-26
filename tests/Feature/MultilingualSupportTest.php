<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Helpers\DateTimeHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

/**
 * 多語言支援功能測試
 */
class MultilingualSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 設定語言為正體中文
        App::setLocale('zh_TW');
    }

    /**
     * 測試基本語言檔案載入
     */
    public function test_basic_language_file_loading(): void
    {
        $this->assertEquals('使用者管理', __('admin.users.title'));
        $this->assertEquals('管理員', __('admin.roles.names.admin'));
        $this->assertEquals('啟用', __('admin.users.active'));
        $this->assertEquals('停用', __('admin.users.inactive'));
    }

    /**
     * 測試日期時間本地化格式
     */
    public function test_datetime_localization(): void
    {
        $datetime = Carbon::create(2024, 1, 15, 14, 30, 0);
        
        // 測試預設格式
        $formatted = DateTimeHelper::formatDateTime($datetime);
        $this->assertStringContainsString('2024-01-15', $formatted);
        
        // 測試日期格式
        $dateFormatted = DateTimeHelper::formatDate($datetime);
        $this->assertStringContainsString('2024-01-15', $dateFormatted);
        
        // 測試時間格式
        $timeFormatted = DateTimeHelper::formatTime($datetime);
        $this->assertStringContainsString('14:30', $timeFormatted);
    }

    /**
     * 測試相對時間本地化
     */
    public function test_relative_time_localization(): void
    {
        $datetime = Carbon::now()->subMinutes(5);
        $relative = DateTimeHelper::formatRelative($datetime);
        
        // Carbon 的相對時間應該包含時間描述
        $this->assertStringContainsString('ago', $relative);
    }

    /**
     * 測試使用者狀態本地化
     */
    public function test_user_status_localization(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->assertEquals('啟用', $user->localized_status);
        
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $this->assertEquals('停用', $inactiveUser->localized_status);
    }

    /**
     * 測試角色名稱本地化
     */
    public function test_role_name_localization(): void
    {
        // 建立角色
        $adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => 'Administrator'
        ]);
        
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        
        // 測試本地化角色名稱
        $localizedRole = $user->localized_primary_role;
        $this->assertEquals('管理員', $localizedRole);
    }

    /**
     * 測試批量操作訊息本地化
     */
    public function test_bulk_operation_messages(): void
    {
        $this->assertEquals('已成功啟用 5 個使用者', __('admin.users.bulk_activate_success', ['count' => 5]));
        $this->assertEquals('已成功停用 3 個使用者', __('admin.users.bulk_deactivate_success', ['count' => 3]));
        $this->assertEquals('已選擇 10 個使用者', __('admin.users.selected_users', ['count' => 10]));
    }

    /**
     * 測試使用者列表專用的日期格式化
     */
    public function test_user_list_date_formatting(): void
    {
        // 測試今天的時間
        $today = Carbon::now()->subHours(2);
        $formatted = DateTimeHelper::formatForUserList($today);
        $this->assertStringContainsString('小時前', $formatted);
        
        // 測試本週的時間
        $thisWeek = Carbon::now()->subDays(2);
        $formatted = DateTimeHelper::formatForUserList($thisWeek);
        $this->assertMatchesRegularExpression('/星期[一二三四五六日]/', $formatted);
        
        // 測試今年的時間
        $thisYear = Carbon::now()->subMonths(2);
        $formatted = DateTimeHelper::formatForUserList($thisYear);
        $this->assertMatchesRegularExpression('/\d+月\d+日/', $formatted);
    }

    /**
     * 測試語言切換
     */
    public function test_language_switching(): void
    {
        // 測試中文
        App::setLocale('zh_TW');
        $this->assertEquals('使用者管理', __('admin.users.title'));
        
        // 切換到英文
        App::setLocale('en');
        $this->assertEquals('User Management', __('admin.users.title'));
        
        // 切換回中文
        App::setLocale('zh_TW');
        $this->assertEquals('使用者管理', __('admin.users.title'));
    }
}