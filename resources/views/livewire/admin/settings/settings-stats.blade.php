<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">總設定數</p>
                <p class="text-2xl font-bold">{{ number_format($this->stats['total']) }}</p>
            </div>
            <x-heroicon-o-cog-6-tooth class="w-8 h-8 text-blue-200" />
        </div>
    </div>
    
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">已變更設定</p>
                <p class="text-2xl font-bold">{{ number_format($this->stats['changed']) }}</p>
            </div>
            <x-heroicon-o-pencil class="w-8 h-8 text-green-200" />
        </div>
    </div>
    
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">設定分類</p>
                <p class="text-2xl font-bold">{{ number_format($this->stats['categories']) }}</p>
            </div>
            <x-heroicon-o-squares-2x2 class="w-8 h-8 text-purple-200" />
        </div>
    </div>
    
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm">最近備份</p>
                <p class="text-sm font-medium">{{ $this->stats['lastBackup'] }}</p>
            </div>
            <x-heroicon-o-shield-check class="w-8 h-8 text-orange-200" />
        </div>
    </div>
</div>