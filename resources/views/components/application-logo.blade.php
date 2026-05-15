{{--
Tujuan: Komponen logo aplikasi berbasis image file. Dipakai di guest auth pages + breeze navigation.
Caller: layouts/guest.blade.php (login/register/forgot/reset/confirm/verify), layouts/navigation.blade.php (profile).
Dependensi: public/images/logo.webp.
Main Functions: Render <img> dengan class yang di-pass via $attributes.
Side Effects: Tidak ada.
--}}
<img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name', 'AndBlog') }}" {{ $attributes->merge(['class' => 'h-12 w-auto']) }}>
