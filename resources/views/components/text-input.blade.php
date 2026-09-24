@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-cocoa-200 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm transition-colors']) }}>
