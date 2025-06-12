<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <title>@yield('title')</title>
     <!-- Font Awesome を追加 -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
     <link rel="stylesheet" href="{{ asset('css/common.css') }}">
     @yield('css')
</head>
<body>
    @include('layouts.header')
    <main>
        @yield('content')
    </main>
    
    @yield('js')
</body>
</html>