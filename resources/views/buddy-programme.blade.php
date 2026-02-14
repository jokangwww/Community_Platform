<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Buddy Programme - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Pass user data to React -->
    <script>
        window.authUser = @php
            $user = auth()->user();
            echo json_encode($user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student_id' => $user->student_id,
                'is_admin' => $user->role === 'admin',
            ] : null);
        @endphp;
    </script>

    @viteReactRefresh
    @vite(['resources/js/buddy-programme/main.tsx'])
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
