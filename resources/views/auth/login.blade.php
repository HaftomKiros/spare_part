<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Ashu Spare Part</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0c29;
            overflow: hidden;
            position: relative;
        }

        /* ── Animated gradient background ── */
        .bg-gradient {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg,
                #0f0c29 0%,
                #1a1040 25%,
                #24243e 50%,
                #0d1b3e 75%,
                #0f0c29 100%);
            background-size: 400% 400%;
            animation: gradientShift 12s ease infinite;
            z-index: 0;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ── Decorative circles ── */
        .circle {
            position: fixed;
            border-radius: 50%;
            opacity: 0.15;
            animation: floatCircle linear infinite;
            z-index: 1;
        }

        /* Big background circles */
        .circle-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, #6c63ff, transparent);
            top: -200px; left: -150px;
            animation-duration: 20s;
            opacity: 0.2;
        }
        .circle-2 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #4f46e5, transparent);
            bottom: -150px; right: -100px;
            animation-duration: 25s;
            animation-direction: reverse;
            opacity: 0.18;
        }
        .circle-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #a855f7, transparent);
            top: 50%; left: 60%;
            animation-duration: 18s;
            opacity: 0.12;
        }

        /* Glowing outlined circles */
        .ring {
            position: fixed;
            border-radius: 50%;
            border: 1.5px solid rgba(108, 99, 255, 0.3);
            animation: spinRing linear infinite;
            z-index: 1;
        }
        .ring-1 {
            width: 400px; height: 400px;
            top: -100px; right: 15%;
            animation-duration: 30s;
        }
        .ring-2 {
            width: 250px; height: 250px;
            bottom: 5%; left: 10%;
            border-color: rgba(168, 85, 247, 0.25);
            animation-duration: 22s;
            animation-direction: reverse;
        }
        .ring-3 {
            width: 180px; height: 180px;
            top: 15%; left: 20%;
            border-color: rgba(79, 70, 229, 0.2);
            animation-duration: 16s;
        }
        .ring-4 {
            width: 120px; height: 120px;
            bottom: 20%; right: 20%;
            border-color: rgba(139, 92, 246, 0.3);
            animation-duration: 12s;
            animation-direction: reverse;
        }

        /* Small glowing dots */
        .dot {
            position: fixed;
            border-radius: 50%;
            background: rgba(108, 99, 255, 0.6);
            animation: pulseDot ease-in-out infinite;
            z-index: 1;
        }
        .dot-1 { width: 8px;  height: 8px;  top: 20%; left: 15%;  animation-duration: 3s;  animation-delay: 0s; }
        .dot-2 { width: 5px;  height: 5px;  top: 70%; left: 80%;  animation-duration: 4s;  animation-delay: 1s; background: rgba(168,85,247,.7); }
        .dot-3 { width: 10px; height: 10px; top: 40%; right: 12%; animation-duration: 3.5s;animation-delay: 0.5s; }
        .dot-4 { width: 6px;  height: 6px;  bottom: 30%; left: 35%; animation-duration: 5s; animation-delay: 2s; background: rgba(139,92,246,.6); }
        .dot-5 { width: 4px;  height: 4px;  top: 85%; left: 55%;  animation-duration: 3s;  animation-delay: 1.5s; }

        @keyframes floatCircle {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-30px) scale(1.05); }
        }
        @keyframes spinRing {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes pulseDot {
            0%,100% { transform: scale(1);   opacity: 0.6; }
            50%      { transform: scale(1.8); opacity: 1; }
        }

        /* ── Left info panel (desktop only) ── */
        .info-panel {
            display: none;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px;
            max-width: 420px;
            color: #fff;
            z-index: 10;
        }

        @media (min-width: 1024px) {
            .info-panel { display: flex; }
        }

        .info-panel .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #6c63ff, #4f46e5);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(108,99,255,.5);
        }

        .info-panel h1 {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 0%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-panel p {
            color: rgba(255,255,255,.6);
            font-size: .95rem;
            line-height: 1.7;
            margin-bottom: 36px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            color: rgba(255,255,255,.8);
            font-size: .875rem;
        }

        .feature-item .icon-wrap {
            width: 34px; height: 34px;
            background: rgba(108,99,255,.2);
            border: 1px solid rgba(108,99,255,.3);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
            color: #a78bfa;
            flex-shrink: 0;
        }

        /* ── Login Card ── */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow:
                0 25px 50px rgba(0,0,0,.5),
                0 0 0 1px rgba(255,255,255,.05),
                inset 0 1px 0 rgba(255,255,255,.1);
        }

        .card-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #6c63ff, #4f46e5);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin: 0 auto 24px;
            box-shadow: 0 8px 24px rgba(108,99,255,.5);
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%,100% { box-shadow: 0 8px 24px rgba(108,99,255,.5); }
            50%      { box-shadow: 0 8px 40px rgba(108,99,255,.8), 0 0 60px rgba(108,99,255,.2); }
        }

        .card-title {
            text-align: center;
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card-subtitle {
            text-align: center;
            color: rgba(255,255,255,.45);
            font-size: .875rem;
            margin-bottom: 32px;
        }

        .card-subtitle span {
            color: #a78bfa;
            font-weight: 500;
        }

        /* Form */
        .form-label {
            color: rgba(255,255,255,.75);
            font-size: .82rem;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .input-group-text {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-right: none;
            color: rgba(255,255,255,.4);
        }

        .form-control {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-left: none;
            color: #fff;
            font-size: .9rem;
            padding: 11px 14px;
            transition: all .2s;
        }

        .form-control:focus {
            background: rgba(255,255,255,.1);
            border-color: #6c63ff;
            box-shadow: 0 0 0 3px rgba(108,99,255,.2);
            color: #fff;
            outline: none;
        }

        .form-control::placeholder { color: rgba(255,255,255,.25); }

        .input-group:focus-within .input-group-text {
            border-color: #6c63ff;
            color: #a78bfa;
        }

        .input-group:focus-within .form-control {
            border-color: #6c63ff;
        }

        /* Toggle password button */
        .btn-eye {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-left: none;
            color: rgba(255,255,255,.4);
            padding: 0 14px;
            cursor: pointer;
            transition: color .2s;
            border-radius: 0 8px 8px 0;
        }
        .btn-eye:hover { color: #a78bfa; }

        .form-check-input {
            background-color: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.2);
        }
        .form-check-input:checked {
            background-color: #6c63ff;
            border-color: #6c63ff;
        }
        .form-check-label {
            color: rgba(255,255,255,.55);
            font-size: .82rem;
        }

        /* Invalid */
        .is-invalid { border-color: #f87171 !important; }
        .invalid-feedback { color: #f87171; font-size: .78rem; }

        .alert-danger {
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: .85rem;
            padding: 10px 14px;
        }

        /* Sign In Button */
        .btn-signin {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #6c63ff, #4f46e5);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .25s;
            box-shadow: 0 4px 20px rgba(108,99,255,.4);
            position: relative;
            overflow: hidden;
        }

        .btn-signin::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
            transition: left .5s;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108,99,255,.6);
        }

        .btn-signin:hover::before { left: 100%; }
        .btn-signin:active { transform: translateY(0); }

        .card-footer-text {
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,.3);
            font-size: .78rem;
        }

        /* Input border radius fix */
        .input-group .input-group-text:first-child { border-radius: 10px 0 0 10px; }
        .input-group .form-control { border-radius: 0; }
        .input-group .form-control:last-child { border-radius: 0 10px 10px 0; }
    </style>
</head>
<body>

<!-- Gradient Background -->
<div class="bg-gradient"></div>

<!-- Decorative Circles -->
<div class="circle circle-1"></div>
<div class="circle circle-2"></div>
<div class="circle circle-3"></div>

<!-- Spinning Rings -->
<div class="ring ring-1"></div>
<div class="ring ring-2"></div>
<div class="ring ring-3"></div>
<div class="ring ring-4"></div>

<!-- Glowing Dots -->
<div class="dot dot-1"></div>
<div class="dot dot-2"></div>
<div class="dot dot-3"></div>
<div class="dot dot-4"></div>
<div class="dot dot-5"></div>

<!-- Layout -->
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;width:100%;position:relative;z-index:10">

    <!-- Left info panel (large screens) -->
    <div class="info-panel me-5">
        <div class="brand-icon">
            <i class="fa-solid fa-gears"></i>
        </div>
        <h1>Ashu Spare Part</h1>
        <p>Complete inventory management system for your spare parts and vehicle business. Track stock, manage sales, and grow your business.</p>

        <div class="feature-item">
            <div class="icon-wrap"><i class="fa fa-boxes-stacked"></i></div>
            <span>Real-time inventory tracking</span>
        </div>
        <div class="feature-item">
            <div class="icon-wrap"><i class="fa fa-receipt"></i></div>
            <span>Sales & purchase management</span>
        </div>
        <div class="feature-item">
            <div class="icon-wrap"><i class="fa fa-chart-line"></i></div>
            <span>Profit & stock reports</span>
        </div>
        <div class="feature-item">
            <div class="icon-wrap"><i class="fa fa-triangle-exclamation"></i></div>
            <span>Low stock alerts</span>
        </div>
    </div>

    <!-- Login Card -->
    <div class="login-wrapper">
        <div class="login-card">

            <!-- Logo -->
            <div class="card-logo">
                <i class="fa-solid fa-motorcycle"></i>
            </div>

            <h4 class="card-title">Welcome Back</h4>
            <p class="card-subtitle">Sign in to <span>Ashu Spare Part System</span></p>

            @if(session('error'))
                <div class="alert alert-danger mb-3">
                    <i class="fa fa-circle-xmark me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               placeholder="admin@example.com"
                               required autofocus>
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input type="password" name="password" id="passwordInput"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••" required>
                        <button type="button" class="btn-eye" onclick="togglePassword()">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-signin">
                    <i class="fa fa-right-to-bracket me-2"></i> Sign In
                </button>
            </form>

            <div class="card-footer-text">
                &copy; {{ date('Y') }} Ashu Spare Part System. All rights reserved.
            </div>
        </div>
    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>
