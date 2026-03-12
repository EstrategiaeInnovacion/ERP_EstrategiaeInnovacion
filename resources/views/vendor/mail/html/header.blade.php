@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ asset('img/logo_vertical.jpg') }}" class="logo" alt="ERP E&I Logo" style="max-height: 75px; object-fit: contain;">
@endif
</a>
</td>
</tr>
