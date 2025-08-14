<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InputValidationService;
use Illuminate\Validation\ValidationException;

class InputValidationServiceTest extends TestCase
{
    protected InputValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = app(InputValidationService::class);
    }

    /** @test */
    public function it_validates_search_input()
    {
        // 有效的搜尋輸入
        $validInput = 'john@example.com';
        $result = $this->validationService->validateSearchInput($validInput);
        $this->assertEquals($validInput, $result);

        // 無效的搜尋輸入
        $this->expectException(ValidationException::class);
        $this->validationService->validateSearchInput('<script>alert("xss")</script>');
    }

    /** @test */
    public function it_validates_user_ids()
    {
        // 有效的使用者 ID 陣列
        $validIds = [1, 2, 3];
        $result = $this->validationService->validateUserIds($validIds);
        $this->assertEquals($validIds, $result);

        // 無效的使用者 ID 陣列
        $this->expectException(ValidationException::class);
        $this->validationService->validateUserIds(['invalid', 'ids']);
    }

    /** @test */
    public function it_detects_malicious_content()
    {
        $maliciousInputs = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            '<iframe src="evil.com"></iframe>',
            'eval(malicious_code)',
        ];

        foreach ($maliciousInputs as $input) {
            $this->assertTrue($this->validationService->containsMaliciousContent($input));
        }

        // 正常內容應該不被標記為惡意
        $this->assertFalse($this->validationService->containsMaliciousContent('normal text'));
    }

    /** @test */
    public function it_sanitizes_string_input()
    {
        $input = '  <script>alert("test")</script>  ';
        $result = $this->validationService->sanitizeString($input);
        
        // 應該移除前後空白和 HTML 標籤
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('  ', $result);
    }

    /** @test */
    public function it_validates_confirm_text()
    {
        $confirmText = 'testuser';
        $expectedText = 'testuser';
        
        // 相符的確認文字應該通過驗證
        $this->assertTrue($this->validationService->validateConfirmText($confirmText, $expectedText));
        
        // 不相符的確認文字應該拋出例外
        $this->expectException(ValidationException::class);
        $this->validationService->validateConfirmText('wrong', $expectedText);
    }
}