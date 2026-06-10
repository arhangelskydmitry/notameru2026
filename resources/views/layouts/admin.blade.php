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
            padding: 15px 0;
        }

        #sidebar ul li a {
            padding: 8px 20px;
            font-size: 1em;
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
    @stack('styles')
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
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_posts'))
            <li class="{{ request()->is('notaadmin/posts*') ? 'active' : '' }}">
                <a href="{{ route('admin.posts') }}"><i class="fas fa-newspaper"></i> Статьи</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser))
            <li class="{{ request()->is('notaadmin/analytics*') ? 'active' : '' }}">
                <a href="{{ route('admin.analytics') }}"><i class="fas fa-chart-line"></i> Аналитика</a>
            </li>
            @endif

            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_all_analytics'))
            <li class="{{ request()->is('notaadmin/content-quality*') ? 'active' : '' }}">
                <a href="{{ route('admin.content-quality') }}"><i class="fas fa-chart-bar"></i> Качество контента</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/seo-analysis*') ? 'active' : '' }}">
                <a href="{{ route('admin.seo-analysis') }}"><i class="fas fa-chart-pie"></i> SEO Анализ</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_pages'))
            <li class="{{ request()->is('notaadmin/pages*') ? 'active' : '' }}">
                <a href="{{ route('admin.pages') }}"><i class="fas fa-file-alt"></i> Страницы</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_categories'))
            <li class="{{ request()->is('notaadmin/categories*') ? 'active' : '' }}">
                <a href="{{ route('admin.categories') }}"><i class="fas fa-folder"></i> Категории</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/tags*') ? 'active' : '' }}">
                <a href="{{ route('admin.tags.index') }}"><i class="fas fa-tags"></i> Теги</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/meta-descriptions*') ? 'active' : '' }}">
                <a href="{{ route('admin.meta-descriptions.index') }}"><i class="fas fa-file-alt"></i> Мета-описания</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/404-logs*') ? 'active' : '' }}">
                <a href="{{ route('admin.404-logs.index') }}"><i class="fas fa-exclamation-triangle"></i> 404 логи</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_menu'))
            <li class="{{ request()->is('notaadmin/menu*') ? 'active' : '' }}">
                <a href="{{ route('admin.menu') }}"><i class="fas fa-bars"></i> Меню</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_banners'))
            <li class="{{ request()->is('notaadmin/banners*') ? 'active' : '' }}">
                <a href="{{ route('admin.banners') }}"><i class="fas fa-rectangle-ad"></i> Баннеры</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_users'))
            <li class="{{ request()->is('notaadmin/users*') ? 'active' : '' }}">
                <a href="{{ route('admin.users') }}"><i class="fas fa-users"></i> Пользователи</a>
            </li>
            @endif

            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/press-cards*') ? 'active' : '' }}">
                <a href="{{ route('admin.press-cards.index') }}"><i class="fas fa-id-card"></i> Пресс-карты</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->isSuperAdmin())
            <li class="{{ request()->is('notaadmin/passwords*') ? 'active' : '' }}">
                <a href="{{ route('admin.passwords') }}"><i class="fas fa-key"></i> Пароли</a>
            </li>
            <li class="{{ request()->is('notaadmin/seo-settings*') ? 'active' : '' }}">
                <a href="{{ route('admin.seo-settings') }}"><i class="fas fa-robot"></i> SEO AI</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_all_analytics'))
            <li class="{{ request()->is('notaadmin/author-statistics*') ? 'active' : '' }}">
                <a href="{{ route('admin.author-statistics') }}"><i class="fas fa-chart-line"></i> Статистика авторов</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->hasPermission('view_activity_log'))
            <li class="{{ request()->is('notaadmin/activity-log*') ? 'active' : '' }}">
                <a href="{{ route('admin.activity-log') }}"><i class="fas fa-history"></i> История действий</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && ($currentAdminUser->isSuperAdmin() || $currentAdminUser->isEditor()))
            <li class="{{ request()->is('notaadmin/sitemap*') ? 'active' : '' }}">
                <a href="{{ route('admin.sitemap') }}"><i class="fas fa-sitemap"></i> Sitemap</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->isSuperAdmin())
            <li class="{{ request()->is('notaadmin/yandex*') ? 'active' : '' }}">
                <a href="{{ route('admin.yandex') }}"><i class="fab fa-yandex"></i> Яндекс сервисы</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->isSuperAdmin())
            <li class="{{ request()->is('notaadmin/backups*') ? 'active' : '' }}">
                <a href="{{ route('admin.backups.index') }}"><i class="fas fa-database"></i> Бекапы</a>
            </li>
            @endif
            
            @if(isset($currentAdminUser) && $currentAdminUser->isSuperAdmin())
            <li class="{{ request()->is('notaadmin/counters*') ? 'active' : '' }}">
                <a href="{{ route('admin.counters.index') }}"><i class="fas fa-chart-line"></i> Счетчики</a>
            </li>
            @endif
            
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
                
                @if(isset($currentAdminUser))
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                                <span class="ms-2">{{ $currentAdminUser->display_name }}</span>
                                @if($currentAdminUser->getRole())
                                    <span class="badge bg-primary ms-2">{{ $currentAdminUser->getRole()->display_name }}</span>
                                @endif
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">
                                    <i class="fas fa-briefcase"></i> {{ $currentAdminUser->getPosition() ?? 'Без должности' }}
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.profile') }}">
                                    <i class="fas fa-user-edit"></i> Мой профиль
                                </a></li>
                                @if($currentAdminUser->hasPermission('view_own_analytics'))
                                <li><a class="dropdown-item" href="{{ route('admin.my-statistics') }}">
                                    <i class="fas fa-chart-line"></i> Моя статистика
                                </a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt"></i> Выйти
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </nav>
        
        @if(admin_is_impersonating())
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-secret me-2"></i>
                    <strong>Режим имперсонации:</strong>
                    Вы работаете как <span class="fw-bold">{{ $currentAdminUser->display_name }}</span>
                    (вошли из аккаунта {{ admin_impersonator_name() ?? 'суперадмина' }}).
                </div>
                <form action="{{ route('admin.users.impersonate.stop') }}" method="POST" class="ms-3">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-sign-out-alt"></i> Выйти из режима
                    </button>
                </form>
            </div>
        @endif
        
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

