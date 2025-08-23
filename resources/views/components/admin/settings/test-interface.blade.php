@props([
    'setting' => null,
    'testType' => 'connection'
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="settingsTestInterface()">
    <!-- 測試介面標頭 -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <x-heroicon-o-beaker class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    設定測試介面
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    測試設定的連線和功能是否正常
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">測試類型：</span>
            <select x-model="currentTestType" @change="switchTestType()" class="select select-bordered select-sm">
                <option value="connection">連線測試</option>
                <option value="email">郵件測試</option>
                <option value="api">API 測試</option>
                <option value="database">資料庫測試</option>
                <option value="storage">儲存測試</option>
                <option value="cache">快取測試</option>
            </select>
        </div>
    </div>

    <!-- 連線測試 -->
    <div x-show="currentTestType === 'connection'" class="space-y-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">連線測試</h4>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">
                測試與外部服務的網路連線是否正常
            </p>
            
            <div class="space-y-3">
                <div>
                    <label class="label">
                        <span class="label-text">測試 URL</span>
                    </label>
                    <input 
                        type="url" 
                        x-model="testConfig.url"
                        placeholder="https://example.com"
                        class="input input-bordered w-full"
                    />
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">
                            <span class="label-text">超時時間 (秒)</span>
                        </label>
                        <input 
                            type="number" 
                            x-model="testConfig.timeout"
                            min="1" 
                            max="60"
                            class="input input-bordered w-full"
                        />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">重試次數</span>
                        </label>
                        <input 
                            type="number" 
                            x-model="testConfig.retries"
                            min="0" 
                            max="5"
                            class="input input-bordered w-full"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 郵件測試 -->
    <div x-show="currentTestType === 'email'" class="space-y-4">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">郵件測試</h4>
            <p class="text-sm text-green-800 dark:text-green-200 mb-4">
                測試 SMTP 設定並發送測試郵件
            </p>
            
            <div class="space-y-3">
                <div>
                    <label class="label">
                        <span class="label-text">收件者信箱</span>
                    </label>
                    <input 
                        type="email" 
                        x-model="testConfig.recipient"
                        placeholder="test@example.com"
                        class="input input-bordered w-full"
                    />
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">郵件主旨</span>
                    </label>
                    <input 
                        type="text" 
                        x-model="testConfig.subject"
                        placeholder="系統設定測試郵件"
                        class="input input-bordered w-full"
                    />
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">郵件內容</span>
                    </label>
                    <textarea 
                        x-model="testConfig.message"
                        rows="3"
                        placeholder="這是一封測試郵件，用於驗證 SMTP 設定是否正確。"
                        class="textarea textarea-bordered w-full"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- API 測試 -->
    <div x-show="currentTestType === 'api'" class="space-y-4">
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <h4 class="font-medium text-purple-900 dark:text-purple-100 mb-2">API 測試</h4>
            <p class="text-sm text-purple-800 dark:text-purple-200 mb-4">
                測試第三方 API 的連線和認證
            </p>
            
            <div class="space-y-3">
                <div>
                    <label class="label">
                        <span class="label-text">API 端點</span>
                    </label>
                    <input 
                        type="url" 
                        x-model="testConfig.endpoint"
                        placeholder="https://api.example.com/v1/test"
                        class="input input-bordered w-full"
                    />
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">
                            <span class="label-text">HTTP 方法</span>
                        </label>
                        <select x-model="testConfig.method" class="select select-bordered w-full">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">認證類型</span>
                        </label>
                        <select x-model="testConfig.authType" class="select select-bordered w-full">
                            <option value="none">無認證</option>
                            <option value="bearer">Bearer Token</option>
                            <option value="basic">Basic Auth</option>
                            <option value="api_key">API Key</option>
                        </select>
                    </div>
                </div>
                
                <div x-show="testConfig.authType !== 'none'">
                    <label class="label">
                        <span class="label-text">認證資訊</span>
                    </label>
                    <input 
                        type="password" 
                        x-model="testConfig.authValue"
                        placeholder="輸入 Token、密碼或 API Key"
                        class="input input-bordered w-full"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- 資料庫測試 -->
    <div x-show="currentTestType === 'database'" class="space-y-4">
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
            <h4 class="font-medium text-orange-900 dark:text-orange-100 mb-2">資料庫測試</h4>
            <p class="text-sm text-orange-800 dark:text-orange-200 mb-4">
                測試資料庫連線和基本操作
            </p>
            
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">
                            <span class="label-text">資料庫類型</span>
                        </label>
                        <select x-model="testConfig.dbType" class="select select-bordered w-full">
                            <option value="mysql">MySQL</option>
                            <option value="postgresql">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                            <option value="redis">Redis</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">測試類型</span>
                        </label>
                        <select x-model="testConfig.testType" class="select select-bordered w-full">
                            <option value="connection">連線測試</option>
                            <option value="read">讀取測試</option>
                            <option value="write">寫入測試</option>
                            <option value="performance">效能測試</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">測試查詢</span>
                    </label>
                    <textarea 
                        x-model="testConfig.query"
                        rows="3"
                        placeholder="SELECT 1"
                        class="textarea textarea-bordered w-full font-mono text-sm"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- 儲存測試 -->
    <div x-show="currentTestType === 'storage'" class="space-y-4">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
            <h4 class="font-medium text-indigo-900 dark:text-indigo-100 mb-2">儲存測試</h4>
            <p class="text-sm text-indigo-800 dark:text-indigo-200 mb-4">
                測試檔案儲存和雲端儲存服務
            </p>
            
            <div class="space-y-3">
                <div>
                    <label class="label">
                        <span class="label-text">儲存類型</span>
                    </label>
                    <select x-model="testConfig.storageType" class="select select-bordered w-full">
                        <option value="local">本地儲存</option>
                        <option value="s3">Amazon S3</option>
                        <option value="gcs">Google Cloud Storage</option>
                        <option value="azure">Azure Blob Storage</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">
                            <span class="label-text">測試檔案大小</span>
                        </label>
                        <select x-model="testConfig.fileSize" class="select select-bordered w-full">
                            <option value="small">小檔案 (1KB)</option>
                            <option value="medium">中檔案 (1MB)</option>
                            <option value="large">大檔案 (10MB)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">測試操作</span>
                        </label>
                        <select x-model="testConfig.operation" class="select select-bordered w-full">
                            <option value="upload">上傳測試</option>
                            <option value="download">下載測試</option>
                            <option value="delete">刪除測試</option>
                            <option value="list">列表測試</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 快取測試 -->
    <div x-show="currentTestType === 'cache'" class="space-y-4">
        <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-4">
            <h4 class="font-medium text-pink-900 dark:text-pink-100 mb-2">快取測試</h4>
            <p class="text-sm text-pink-800 dark:text-pink-200 mb-4">
                測試快取系統的讀寫效能
            </p>
            
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">
                            <span class="label-text">快取驅動</span>
                        </label>
                        <select x-model="testConfig.cacheDriver" class="select select-bordered w-full">
                            <option value="redis">Redis</option>
                            <option value="memcached">Memcached</option>
                            <option value="file">檔案快取</option>
                            <option value="database">資料庫快取</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">測試項目數</span>
                        </label>
                        <input 
                            type="number" 
                            x-model="testConfig.itemCount"
                            min="1" 
                            max="1000"
                            class="input input-bordered w-full"
                        />
                    </div>
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">測試資料大小</span>
                    </label>
                    <select x-model="testConfig.dataSize" class="select select-bordered w-full">
                        <option value="small">小資料 (1KB)</option>
                        <option value="medium">中資料 (10KB)</option>
                        <option value="large">大資料 (100KB)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- 測試控制區域 -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <button 
                @click="runTest()"
                :disabled="isRunning"
                class="btn btn-primary"
                :class="isRunning ? 'loading' : ''"
            >
                <x-heroicon-o-play class="w-4 h-4" x-show="!isRunning" />
                <span x-text="isRunning ? '測試中...' : '開始測試'"></span>
            </button>
            
            <button 
                @click="stopTest()"
                :disabled="!isRunning"
                class="btn btn-outline"
            >
                <x-heroicon-o-stop class="w-4 h-4" />
                停止測試
            </button>
            
            <button 
                @click="clearResults()"
                class="btn btn-ghost"
            >
                <x-heroicon-o-trash class="w-4 h-4" />
                清除結果
            </button>
        </div>
        
        <div class="flex items-center gap-2">
            <label class="label cursor-pointer">
                <input 
                    type="checkbox" 
                    x-model="autoSave"
                    class="checkbox checkbox-sm"
                />
                <span class="label-text ml-2">自動儲存結果</span>
            </label>
        </div>
    </div>

    <!-- 測試結果區域 -->
    <div x-show="testResults.length > 0" class="mt-6 space-y-4">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">測試結果</h4>
        
        <div class="space-y-3">
            <template x-for="(result, index) in testResults" :key="index">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div 
                                class="w-3 h-3 rounded-full"
                                :class="result.success ? 'bg-green-500' : 'bg-red-500'"
                            ></div>
                            <span class="font-medium" x-text="result.name"></span>
                            <span class="text-sm text-gray-500" x-text="result.timestamp"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="result.duration + 'ms'"></span>
                            <button 
                                @click="exportResult(index)"
                                class="btn btn-ghost btn-xs"
                                title="匯出結果"
                            >
                                <x-heroicon-o-arrow-down-tray class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-sm">
                        <div class="text-gray-600 dark:text-gray-400 mb-1">狀態：</div>
                        <div 
                            class="font-mono p-2 rounded text-xs"
                            :class="result.success ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'"
                            x-text="result.message"
                        ></div>
                        
                        <div x-show="result.details" class="mt-2">
                            <div class="text-gray-600 dark:text-gray-400 mb-1">詳細資訊：</div>
                            <pre class="bg-gray-100 dark:bg-gray-600 p-2 rounded text-xs overflow-x-auto" x-text="JSON.stringify(result.details, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- 測試統計 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h5 class="font-medium text-blue-900 dark:text-blue-100 mb-2">測試統計</h5>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-blue-800 dark:text-blue-200">總測試數</div>
                    <div class="font-semibold" x-text="testResults.length"></div>
                </div>
                <div>
                    <div class="text-green-800 dark:text-green-200">成功</div>
                    <div class="font-semibold text-green-600" x-text="testResults.filter(r => r.success).length"></div>
                </div>
                <div>
                    <div class="text-red-800 dark:text-red-200">失敗</div>
                    <div class="font-semibold text-red-600" x-text="testResults.filter(r => !r.success).length"></div>
                </div>
                <div>
                    <div class="text-blue-800 dark:text-blue-200">平均時間</div>
                    <div class="font-semibold" x-text="Math.round(testResults.reduce((sum, r) => sum + r.duration, 0) / testResults.length) + 'ms'"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function settingsTestInterface() {
    return {
        currentTestType: 'connection',
        isRunning: false,
        autoSave: true,
        testResults: [],
        testConfig: {
            // 連線測試
            url: '',
            timeout: 10,
            retries: 3,
            
            // 郵件測試
            recipient: '',
            subject: '系統設定測試郵件',
            message: '這是一封測試郵件，用於驗證 SMTP 設定是否正確。',
            
            // API 測試
            endpoint: '',
            method: 'GET',
            authType: 'none',
            authValue: '',
            
            // 資料庫測試
            dbType: 'mysql',
            testType: 'connection',
            query: 'SELECT 1',
            
            // 儲存測試
            storageType: 'local',
            fileSize: 'small',
            operation: 'upload',
            
            // 快取測試
            cacheDriver: 'redis',
            itemCount: 100,
            dataSize: 'small'
        },
        
        // 切換測試類型
        switchTestType() {
            // 重設測試配置
            this.resetTestConfig();
        },
        
        // 重設測試配置
        resetTestConfig() {
            // 根據測試類型設定預設值
            switch (this.currentTestType) {
                case 'connection':
                    this.testConfig.url = '';
                    this.testConfig.timeout = 10;
                    this.testConfig.retries = 3;
                    break;
                case 'email':
                    this.testConfig.recipient = '';
                    this.testConfig.subject = '系統設定測試郵件';
                    break;
                // 其他類型的預設值...
            }
        },
        
        // 執行測試
        async runTest() {
            this.isRunning = true;
            
            try {
                const result = await this.performTest();
                this.addTestResult(result);
                
                if (this.autoSave) {
                    this.saveTestResult(result);
                }
            } catch (error) {
                this.addTestResult({
                    name: this.currentTestType + ' 測試',
                    success: false,
                    message: error.message,
                    duration: 0,
                    timestamp: new Date().toLocaleString(),
                    details: { error: error.toString() }
                });
            } finally {
                this.isRunning = false;
            }
        },
        
        // 執行實際測試
        async performTest() {
            const startTime = Date.now();
            
            // 根據測試類型執行不同的測試邏輯
            let result;
            switch (this.currentTestType) {
                case 'connection':
                    result = await this.testConnection();
                    break;
                case 'email':
                    result = await this.testEmail();
                    break;
                case 'api':
                    result = await this.testApi();
                    break;
                case 'database':
                    result = await this.testDatabase();
                    break;
                case 'storage':
                    result = await this.testStorage();
                    break;
                case 'cache':
                    result = await this.testCache();
                    break;
                default:
                    throw new Error('未知的測試類型');
            }
            
            const duration = Date.now() - startTime;
            
            return {
                ...result,
                duration: duration,
                timestamp: new Date().toLocaleString()
            };
        },
        
        // 連線測試
        async testConnection() {
            const response = await Livewire.dispatch('test-connection', {
                url: this.testConfig.url,
                timeout: this.testConfig.timeout,
                retries: this.testConfig.retries
            });
            
            return {
                name: '連線測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // 郵件測試
        async testEmail() {
            const response = await Livewire.dispatch('test-email', {
                recipient: this.testConfig.recipient,
                subject: this.testConfig.subject,
                message: this.testConfig.message
            });
            
            return {
                name: '郵件測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // API 測試
        async testApi() {
            const response = await Livewire.dispatch('test-api', {
                endpoint: this.testConfig.endpoint,
                method: this.testConfig.method,
                authType: this.testConfig.authType,
                authValue: this.testConfig.authValue
            });
            
            return {
                name: 'API 測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // 資料庫測試
        async testDatabase() {
            const response = await Livewire.dispatch('test-database', {
                dbType: this.testConfig.dbType,
                testType: this.testConfig.testType,
                query: this.testConfig.query
            });
            
            return {
                name: '資料庫測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // 儲存測試
        async testStorage() {
            const response = await Livewire.dispatch('test-storage', {
                storageType: this.testConfig.storageType,
                fileSize: this.testConfig.fileSize,
                operation: this.testConfig.operation
            });
            
            return {
                name: '儲存測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // 快取測試
        async testCache() {
            const response = await Livewire.dispatch('test-cache', {
                cacheDriver: this.testConfig.cacheDriver,
                itemCount: this.testConfig.itemCount,
                dataSize: this.testConfig.dataSize
            });
            
            return {
                name: '快取測試',
                success: response.success,
                message: response.message,
                details: response.details
            };
        },
        
        // 停止測試
        stopTest() {
            this.isRunning = false;
            // 這裡可以實作停止測試的邏輯
        },
        
        // 新增測試結果
        addTestResult(result) {
            this.testResults.unshift(result);
            
            // 限制結果數量
            if (this.testResults.length > 50) {
                this.testResults = this.testResults.slice(0, 50);
            }
        },
        
        // 清除測試結果
        clearResults() {
            this.testResults = [];
        },
        
        // 儲存測試結果
        saveTestResult(result) {
            Livewire.dispatch('save-test-result', { result: result });
        },
        
        // 匯出測試結果
        exportResult(index) {
            const result = this.testResults[index];
            const dataStr = JSON.stringify(result, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `test-result-${result.name}-${Date.now()}.json`;
            link.click();
        }
    };
}
</script>
@endpush