@extends('layouts.admin')
@section('title', 'Настройки SEO AI')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-robot"></i> Настройки SEO AI</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row">
        <!-- Основные настройки -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cog"></i> Настройки провайдеров ИИ
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.seo-settings.update') }}" method="POST">
                        @csrf
                        
                        <!-- Выбор провайдера -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-brain"></i> Предпочтительный провайдер ИИ
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card h-100 {{ $settings['preferred_provider'] === 'gigachat' ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="preferred_provider" 
                                                       id="provider_gigachat" value="gigachat"
                                                       {{ $settings['preferred_provider'] === 'gigachat' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="provider_gigachat">
                                                    🇷🇺 GigaChat (Сбер)
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">
                                                Российский ИИ от Сбербанка. Лучшее качество для русского языка.
                                                Требует регистрации на developers.sber.ru
                                            </p>
                                            @if($providers['gigachat']['configured'])
                                                <span class="badge bg-success mt-2"><i class="fas fa-check"></i> Настроен</span>
                                            @else
                                                <span class="badge bg-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Не настроен</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 {{ $settings['preferred_provider'] === 'openai' ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="preferred_provider" 
                                                       id="provider_openai" value="openai"
                                                       {{ $settings['preferred_provider'] === 'openai' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="provider_openai">
                                                    🌐 OpenAI (GPT)
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">
                                                OpenAI GPT-4o-mini. Резервный вариант.
                                                Требует API ключ в .env файле (OPENAI_API_KEY)
                                            </p>
                                            @if($providers['openai']['configured'])
                                                <span class="badge bg-success mt-2"><i class="fas fa-check"></i> Настроен</span>
                                            @else
                                                <span class="badge bg-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Не настроен</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 {{ $settings['preferred_provider'] === 'chatinfo' ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="preferred_provider" 
                                                       id="provider_chatinfo" value="chatinfo"
                                                       {{ $settings['preferred_provider'] === 'chatinfo' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="provider_chatinfo">
                                                    💬 ChatInfo (GPT-4o)
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">
                                                ChatInfo GPT-4o. Российский сервис, оплата из России.
                                                Требует API ключ в настройках (CHATINFO_API_KEY)
                                            </p>
                                            @if(isset($providers['chatinfo']) && $providers['chatinfo']['configured'])
                                                <span class="badge bg-success mt-2"><i class="fas fa-check"></i> Настроен</span>
                                            @else
                                                <span class="badge bg-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Не настроен</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Настройки GigaChat -->
                        <div id="gigachatSettings" class="mb-4" style="display: {{ $settings['preferred_provider'] === 'gigachat' ? 'block' : 'none' }};">
                            <h5 class="mb-3">
                                <i class="fas fa-key"></i> Настройки GigaChat API
                                <a href="https://developers.sber.ru/portal/products/gigachat-api" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                    <i class="fas fa-external-link-alt"></i> Документация
                                </a>
                            </h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Как получить доступ:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Зарегистрируйтесь на <a href="https://developers.sber.ru" target="_blank">developers.sber.ru</a></li>
                                    <li>Создайте проект и подключите GigaChat API</li>
                                    <li>Получите Client ID и Client Secret</li>
                                    <li>Скопируйте данные в поля ниже</li>
                                </ol>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gigachat_client_id" class="form-label">Client ID (Authorization Data)</label>
                                <input type="text" class="form-control" id="gigachat_client_id" name="gigachat_client_id"
                                       value="{{ $settings['gigachat_client_id'] }}"
                                       placeholder="Введите Client ID от Sber API">
                            </div>
                            
                            <div class="mb-3">
                                <label for="gigachat_client_secret" class="form-label">Client Secret</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="gigachat_client_secret" name="gigachat_client_secret"
                                           value="{{ $settings['gigachat_client_secret'] }}"
                                           placeholder="Введите Client Secret">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('gigachat_client_secret')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gigachat_scope" class="form-label">Scope (тип доступа)</label>
                                <select class="form-select" id="gigachat_scope" name="gigachat_scope">
                                    <option value="GIGACHAT_API_PERS" {{ $settings['gigachat_scope'] === 'GIGACHAT_API_PERS' ? 'selected' : '' }}>
                                        GIGACHAT_API_PERS (Персональный)
                                    </option>
                                    <option value="GIGACHAT_API_CORP" {{ $settings['gigachat_scope'] === 'GIGACHAT_API_CORP' ? 'selected' : '' }}>
                                        GIGACHAT_API_CORP (Корпоративный)
                                    </option>
                                    <option value="GIGACHAT_API_B2B" {{ $settings['gigachat_scope'] === 'GIGACHAT_API_B2B' ? 'selected' : '' }}>
                                        GIGACHAT_API_B2B (B2B)
                                    </option>
                                </select>
                                <small class="text-muted">Выберите тип вашего доступа к API</small>
                            </div>
                        </div>
                        
                        <!-- Настройки ChatInfo -->
                        <div id="chatinfoSettings" class="mb-4" style="display: {{ $settings['preferred_provider'] === 'chatinfo' ? 'block' : 'none' }};">
                            <h5 class="mb-3">
                                <i class="fas fa-key"></i> Настройки ChatInfo API
                                <a href="https://chatinfo.ru/api-docs" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                    <i class="fas fa-external-link-alt"></i> Документация
                                </a>
                            </h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Как получить API ключ:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Зарегистрируйтесь на <a href="https://chatinfo.ru" target="_blank">chatinfo.ru</a></li>
                                    <li>Подключите тариф "Престиж" (API доступен только на этом тарифе)</li>
                                    <li>Получите API ключ на странице <a href="https://chatinfo.ru/subscription" target="_blank">подписки</a></li>
                                    <li>Добавьте ключ в настройки или в файл <code>.env</code>: <code>CHATINFO_API_KEY=your-key-here</code></li>
                                </ol>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                @php
                                    $chatinfoKey = config('services.chatinfo.api_key', env('CHATINFO_API_KEY'));
                                @endphp
                                <input type="text" class="form-control" value="{{ $chatinfoKey ? '***' . substr($chatinfoKey, -4) : 'Не настроен' }}" readonly>
                                <small class="text-muted">API ключ настраивается в файле .env (CHATINFO_API_KEY) или через настройки</small>
                            </div>
                        </div>
                        
                        <!-- Настройки OpenAI -->
                        <div id="openaiSettings" class="mb-4" style="display: {{ $settings['preferred_provider'] === 'openai' ? 'block' : 'none' }};">
                            <h5 class="mb-3">
                                <i class="fas fa-key"></i> Настройки OpenAI API
                                <a href="https://platform.openai.com/api-keys" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                    <i class="fas fa-external-link-alt"></i> Документация
                                </a>
                            </h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Как получить API ключ:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Зарегистрируйтесь на <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></li>
                                    <li>Перейдите в раздел API Keys</li>
                                    <li>Создайте новый API ключ</li>
                                    <li>Добавьте ключ в файл <code>.env</code>: <code>OPENAI_API_KEY=your-key-here</code></li>
                                </ol>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                @php
                                    $openaiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
                                @endphp
                                <input type="text" class="form-control" value="{{ $openaiKey ? '***' . substr($openaiKey, -4) : 'Не настроен' }}" readonly>
                                <small class="text-muted">API ключ настраивается в файле .env (OPENAI_API_KEY)</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить настройки
                            </button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success" id="testGigaChatBtn" style="display: {{ $settings['preferred_provider'] === 'gigachat' ? 'inline-block' : 'none' }};">
                                <i class="fas fa-plug"></i> Тест GigaChat
                            </button>
                                <button type="button" class="btn btn-outline-success" id="testOpenAIBtn" style="display: {{ $settings['preferred_provider'] === 'openai' ? 'inline-block' : 'none' }};">
                                <i class="fas fa-plug"></i> Тест OpenAI
                            </button>
                                <button type="button" class="btn btn-outline-success" id="testChatInfoBtn" style="display: {{ $settings['preferred_provider'] === 'chatinfo' ? 'inline-block' : 'none' }};">
                                <i class="fas fa-plug"></i> Тест ChatInfo
                            </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Справка -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-magic"></i> Возможности SEO AI
                </div>
                <div class="card-body">
                    <h6><i class="fas fa-check-circle text-success"></i> Автоматическая генерация:</h6>
                    <ul class="small">
                        <li>SEO-заголовок (оптимизированный)</li>
                        <li>Meta Description</li>
                        <li>Ключевые слова</li>
                        <li>Open Graph данные</li>
                        <li>Twitter Card данные</li>
                    </ul>
                    
                    <h6 class="mt-3"><i class="fas fa-sync text-primary"></i> Автозаполнение:</h6>
                    <p class="small mb-0">
                        При редактировании SEO-полей связанные поля автоматически заполняются.
                        Например: SEO Title → OG Title → Twitter Title
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-question-circle"></i> Почему GigaChat?
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li><strong>Русский язык</strong> — оптимизирован для русскоязычного контента</li>
                        <li><strong>Российские серверы</strong> — данные остаются в России</li>
                        <li><strong>Высокое качество</strong> — современная модель от Сбера</li>
                        <li><strong>Стабильность</strong> — не зависит от зарубежных сервисов</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Переключение между провайдерами
function updateProviderUI(provider) {
    console.log('Переключение на провайдера:', provider);
    
    const gigachatSettings = document.getElementById('gigachatSettings');
    const openaiSettings = document.getElementById('openaiSettings');
    const chatinfoSettings = document.getElementById('chatinfoSettings');
    const testGigaChatBtn = document.getElementById('testGigaChatBtn');
    const testOpenAIBtn = document.getElementById('testOpenAIBtn');
    const testChatInfoBtn = document.getElementById('testChatInfoBtn');
    const gigachatCard = document.querySelector('#provider_gigachat')?.closest('.card');
    const openaiCard = document.querySelector('#provider_openai')?.closest('.card');
    const chatinfoCard = document.querySelector('#provider_chatinfo')?.closest('.card');
    
    if (provider === 'gigachat') {
        // Показываем настройки GigaChat
        if (gigachatSettings) {
            gigachatSettings.style.display = 'block';
            console.log('Показаны настройки GigaChat');
        }
        if (openaiSettings) {
            openaiSettings.style.display = 'none';
        }
        
        // Показываем кнопку теста GigaChat
        if (testGigaChatBtn) {
            testGigaChatBtn.style.display = 'inline-block';
        }
        if (testOpenAIBtn) {
            testOpenAIBtn.style.display = 'none';
        }
        if (testChatInfoBtn) {
            testChatInfoBtn.style.display = 'none';
        }
        
        // Выделяем карточку GigaChat
        if (gigachatCard) {
            gigachatCard.classList.add('border-primary');
            gigachatCard.classList.add('shadow-sm');
        }
        if (openaiCard) {
            openaiCard.classList.remove('border-primary');
            openaiCard.classList.remove('shadow-sm');
        }
        if (chatinfoCard) {
            chatinfoCard.classList.remove('border-primary');
            chatinfoCard.classList.remove('shadow-sm');
        }
    } else if (provider === 'openai') {
        // Показываем настройки OpenAI
        if (gigachatSettings) {
            gigachatSettings.style.display = 'none';
        }
        if (openaiSettings) {
            openaiSettings.style.display = 'block';
            console.log('Показаны настройки OpenAI');
        }
        if (chatinfoSettings) {
            chatinfoSettings.style.display = 'none';
        }
        
        // Показываем кнопку теста OpenAI
        if (testGigaChatBtn) {
            testGigaChatBtn.style.display = 'none';
        }
        if (testOpenAIBtn) {
            testOpenAIBtn.style.display = 'inline-block';
        }
        if (testChatInfoBtn) {
            testChatInfoBtn.style.display = 'none';
        }
        
        // Выделяем карточку OpenAI
        if (gigachatCard) {
            gigachatCard.classList.remove('border-primary');
            gigachatCard.classList.remove('shadow-sm');
        }
        if (openaiCard) {
            openaiCard.classList.add('border-primary');
            openaiCard.classList.add('shadow-sm');
        }
        if (chatinfoCard) {
            chatinfoCard.classList.remove('border-primary');
            chatinfoCard.classList.remove('shadow-sm');
        }
    } else if (provider === 'chatinfo') {
        // Показываем настройки ChatInfo
        if (gigachatSettings) {
            gigachatSettings.style.display = 'none';
        }
        if (openaiSettings) {
            openaiSettings.style.display = 'none';
        }
        if (chatinfoSettings) {
            chatinfoSettings.style.display = 'block';
            console.log('Показаны настройки ChatInfo');
        }
        
        // Показываем кнопку теста ChatInfo
        if (testGigaChatBtn) {
            testGigaChatBtn.style.display = 'none';
        }
        if (testOpenAIBtn) {
            testOpenAIBtn.style.display = 'none';
        }
        if (testChatInfoBtn) {
            testChatInfoBtn.style.display = 'inline-block';
        }
        
        // Выделяем карточку ChatInfo
        if (gigachatCard) {
            gigachatCard.classList.remove('border-primary');
            gigachatCard.classList.remove('shadow-sm');
        }
        if (openaiCard) {
            openaiCard.classList.remove('border-primary');
            openaiCard.classList.remove('shadow-sm');
        }
        if (chatinfoCard) {
            chatinfoCard.classList.add('border-primary');
            chatinfoCard.classList.add('shadow-sm');
        }
    }
}

// Обработчики для радиокнопок
document.addEventListener('DOMContentLoaded', function() {
    const gigachatRadio = document.getElementById('provider_gigachat');
    const openaiRadio = document.getElementById('provider_openai');
    const chatinfoRadio = document.getElementById('provider_chatinfo');
    
    if (gigachatRadio) {
        gigachatRadio.addEventListener('change', function() {
            if (this.checked) {
                updateProviderUI('gigachat');
            }
        });
    }
    
    if (openaiRadio) {
        openaiRadio.addEventListener('change', function() {
            if (this.checked) {
                updateProviderUI('openai');
            }
        });
    }
    
    if (chatinfoRadio) {
        chatinfoRadio.addEventListener('change', function() {
            if (this.checked) {
                updateProviderUI('chatinfo');
            }
        });
    }
    
    // Инициализация при загрузке
    const selectedProvider = document.querySelector('input[name="preferred_provider"]:checked');
    if (selectedProvider) {
        updateProviderUI(selectedProvider.value);
    }
});

// Тест GigaChat
document.getElementById('testGigaChatBtn')?.addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
    btn.disabled = true;
    
    fetch('{{ route("admin.seo-settings.test-provider") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ provider: 'gigachat' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Ошибка: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

// Тест ChatInfo
document.getElementById('testChatInfoBtn')?.addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
    btn.disabled = true;
    
    fetch('{{ route("admin.seo-settings.test-provider") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ provider: 'chatinfo' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Ошибка: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

// Тест OpenAI
document.getElementById('testOpenAIBtn')?.addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
    btn.disabled = true;
    
    fetch('{{ route("admin.seo-settings.test-provider") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ provider: 'openai' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Ошибка: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});
</script>
@endsection
