{{-- 角色本地化元件 --}}
<span class="role-localization" data-type="{{ $type }}" data-name="{{ $name }}">
    <span class="display-name">{{ $getDisplayName() }}</span>
    @if($showDescription && $getDescription())
        <span class="description text-sm text-gray-500 dark:text-gray-400 block">
            {{ $getDescription() }}
        </span>
    @endif
</span>