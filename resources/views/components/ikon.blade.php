@props(['e', 'class' => ''])
{{-- Ikon OpenMoji dari karakter emoji. Ukuran mengikuti ukuran huruf sekitarnya; ubah lewat class, mis. class="size-8". --}}
{!! \App\Support\Emoji::html($e, $class) !!}
