@props(['name', 'checked' => false, 'disabled' => false, 'label' => null, 'id' => null, 'xModel' => null])

<label class="relative inline-flex items-center cursor-pointer" @if($id) for="{{ $id }}" @endif>
    <input type="checkbox" name="{{ $name }}" value="1" class="sr-only peer"
        @if($id) id="{{ $id }}" @endif
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        @if($xModel) x-model="{{ $xModel }}" @endif>
    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-600 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600 dark:bg-slate-600 dark:peer-focus:ring-teal-700/50 dark:after:border-slate-500"></div>
    @if($label)
        <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-200">{{ $label }}</span>
    @endif
</label>
