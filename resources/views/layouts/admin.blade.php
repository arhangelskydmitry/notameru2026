<!doctype html>
<html lang="ru">
<head>
    <title>@yield('title', 'Админ панель') - Нота Миру</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Sidebar styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #f5f5f5;
            color: #333;
            transition: all 0.3s;
            min-height: 100vh;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #e8e8e8;
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: #333;
            text-decoration: none;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: #0d6efd;
        }

        #sidebar ul li.active > a {
            color: #fff;
            background: #0d6efd;
        }

        a[data-toggle="collapse"] {
            position: relative;
        }

        .dropdown-toggle::after {
            display: block;
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
        }

        .logo {
            width: 90%;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 0;
            margin-bottom: 40px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .wrapper {
            display: flex;
            align-items: stretch;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
        
        /* Фикс для огромных стрелок пагинации */
        .pagination svg,
        nav[aria-label="pagination"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block !important;
        }
        
        .pagination .page-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 32px !important;
            padding: 0.375rem 0.75rem !important;
        }
    </style>
</head>
<body>
    
<div class="wrapper d-flex align-items-stretch">
    <nav id="sidebar">
        <div class="sidebar-header">
            <img src="{{ asset('images/logo.png') }}" alt="Нота Миру" class="logo mb-3">
            <h3 class="text-center" style="font-family: 'Poppins', sans-serif; font-weight: 300; letter-spacing: 2px; color: #333;">УПРАВЛЕНИЕ</h3>
        </div>

        <ul class="list-unstyled components">
            <li class="{{ request()->is('notaadmin') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}"><i class="fas fa-dashboard"></i> Главная</a>
            </li>
            
            <li class="{{ request()->is('notaadmin/posts*') ? 'active' : '' }}">
                <a href="{{ route('admin.posts') }}"><i class="fas fa-newspaper"></i> Статьи</a>
            </li>
            
            <li class="{{ request()->is('notaadmin/pages*') ? 'active' : '' }}">
                <a href="{{ route('admin.pages') }}"><i class="fas fa-file-alt"></i> Страницы</a>
            </li>
            
            <li class="{{ request()->is('notaadmin/categories*') ? 'active' : '' }}">
                <a href="{{ route('admin.categories') }}"><i class="fas fa-folder"></i> Категории</a>
            </li>
            
            <li class="{{ request()->is('notaadmin/menu*') ? 'active' : '' }}">
                <a href="{{ route('admin.menu') }}"><i class="fas fa-bars"></i> Меню</a>
            </li>
            
            <li>
                <a href="{{ route('home') }}" target="_blank"><i class="fas fa-external-link-alt"></i> Открыть сайт</a>
            </li>
        </ul>

        <div class="p-3 text-center">
            <p class="small text-muted">
                Copyright &copy; {{ date('Y') }}<br>
                Нота Миру
            </p>
        </div>
    </nav>

    <!-- Page Content  -->
    <div id="content" class="p-4 p-md-5">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="text-secondary ms-3 mb-0">@yield('title', 'Админ панель')</h4>
            </div>
        </nav>
        
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <strong>Успешно!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Ошибка!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @yield('content')
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
        
        // Auto-hide success alerts after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
@stack('scripts')
</body>
</html>

