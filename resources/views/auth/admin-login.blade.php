<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - Нота Миру</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 15px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 10;
        }
        
        .input-group .form-control {
            padding-left: 45px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h1>Админ-панель</h1>
            <p>Нота Миру</p>
        </div>
        
        <div class="login-body">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-group-icon"></i>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               placeholder="Введите ваш email"
                               required 
                               autofocus>
                    </div>
                    @error('email')
                        <div class="text-danger mt-1" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-group-icon"></i>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Введите ваш пароль"
                               required>
                    </div>
                    @error('password')
                        <div class="text-danger mt-1" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>
            
            <div class="footer-text">
                <a href="{{ route('home') }}" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-home"></i> Вернуться на сайт
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

