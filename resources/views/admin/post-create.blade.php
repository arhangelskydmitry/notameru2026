@extends('layouts.admin')
@section('title', 'Создание новой статьи')
@section('content')

<!-- Прелоадер для генерации SEO -->
<div id="seoLoaderOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div style="text-align: center; color: white; max-width: 500px; padding: 40px;">
        <div class="spinner-border" role="status" style="width: 4rem; height: 4rem; margin-bottom: 30px; border-width: 4px;">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        <h2 id="loaderTitle" style="font-size: 28px; margin-bottom: 15px;">Создание статьи...</h2>
        <p id="loaderText" style="font-size: 18px; opacity: 0.9;">Пожалуйста, подождите</p>
        <div id="loaderProgress" style="margin-top: 20px;">
            <div style="background: rgba(255,255,255,0.2); height: 6px; border-radius: 3px; overflow: hidden;">
                <div id="progressBar" style="background: #4CAF50; height: 100%; width: 0%; transition: width 0.3s;"></div>
            </div>
            <small id="progressText" style="display: block; margin-top: 10px; opacity: 0.7;">0%</small>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-plus"></i> Создание новой статьи</h1>
        <a href="{{ route('admin.posts') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form id="createPostForm" action="{{ route('admin.posts.store') }}" method="POST">
                @csrf
                
                <!-- Название -->
                <div class="mb-3">
                    <label for="post_title" class="form-label">
                        <i class="fas fa-heading"></i> Название статьи
                    </label>
                    <input type="text" 
                           class="form-control @error('post_title') is-invalid @enderror" 
                           id="post_title" 
                           name="post_title" 
                           value="{{ old('post_title') }}" 
                           required>
                    @error('post_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">URL будет сгенерирован автоматически из заголовка</small>
                </div>
                
                <!-- Контент -->
                <div class="mb-3">
                    <label for="post_content" class="form-label">
                        <i class="fas fa-paragraph"></i> Содержание
                    </label>
                    
                    <!-- Кнопка загрузки изображения -->
                    <div class="mb-2">
                        <button type="button" class="btn btn-primary btn-sm" id="uploadImageBtn">
                            <i class="fas fa-image"></i> Загрузить изображение
                        </button>
                        <input type="file" id="imageUploadInput" accept="image/*" style="display: none;">
                        <small class="text-muted ms-2">Или перетащите изображение в редактор</small>
                    </div>
                    
                    <textarea class="form-control tinymce-editor @error('post_content') is-invalid @enderror" 
                              id="post_content" 
                              name="post_content" 
                              rows="15">{{ old('post_content') }}</textarea>
                    @error('post_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Краткое описание -->
                <div class="mb-3">
                    <label for="post_excerpt" class="form-label">
                        <i class="fas fa-align-left"></i> Краткое описание (Excerpt)
                    </label>
                    <textarea class="form-control @error('post_excerpt') is-invalid @enderror" 
                              id="post_excerpt" 
                              name="post_excerpt" 
                              rows="3">{{ old('post_excerpt') }}</textarea>
                    @error('post_excerpt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Опционально. Используется в превью статьи.</small>
                </div>
                
                <!-- Статус -->
                <div class="mb-3">
                    <label for="post_status" class="form-label">
                        <i class="fas fa-toggle-on"></i> Статус публикации
                    </label>
                    <select class="form-select @error('post_status') is-invalid @enderror" 
                            id="post_status" 
                            name="post_status" 
                            required>
                        <option value="draft" {{ old('post_status', 'draft') == 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="publish" {{ old('post_status') == 'publish' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="future" {{ old('post_status') == 'future' ? 'selected' : '' }}>Отложенная публикация</option>
                        <option value="pending" {{ old('post_status') == 'pending' ? 'selected' : '' }}>Ожидает проверки</option>
                    </select>
                    @error('post_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Публикацию можно отложить на будущее отдельным статусом.</small>
                </div>
                
                <!-- Дата и время публикации -->
                <div class="mb-3">
                    <label for="post_date" class="form-label">
                        <i class="fas fa-calendar-alt"></i> Дата и время публикации
                    </label>
                    <input type="datetime-local" 
                           class="form-control @error('post_date') is-invalid @enderror" 
                           id="post_date" 
                           name="post_date" 
                           value="{{ old('post_date', now()->format('Y-m-d\TH:i')) }}" 
                           required>
                    @error('post_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">
                        Укажите дату и время публикации статьи. Для отложенной публикации выберите будущую дату и статус "Отложенная публикация".
                    </small>
                </div>
                
                <!-- Автор -->
                <div class="mb-3">
                    <label for="post_author" class="form-label">
                        <i class="fas fa-user"></i> Автор
                    </label>
                    @if(admin_user() && admin_user()->isAuthor())
                        {{-- Автор может создавать только от своего имени --}}
                        <input type="text" 
                               class="form-control" 
                               value="{{ admin_user()->display_name }} ({{ admin_user()->user_login }})" 
                               readonly>
                        <input type="hidden" name="post_author" value="{{ admin_user()->ID }}">
                        <small class="text-muted">Вы будете указаны как автор статьи</small>
                    @else
                        {{-- Админ и редактор могут выбирать автора --}}
                    <select class="form-select @error('post_author') is-invalid @enderror" 
                            id="post_author" 
                            name="post_author" 
                            required>
                        @foreach($authors as $author)
                            <option value="{{ $author->ID }}" {{ old('post_author', admin_user()->ID) == $author->ID ? 'selected' : '' }}>
                                {{ $author->display_name }} ({{ $author->user_login }})
                            </option>
                        @endforeach
                    </select>
                    @error('post_author')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                        <small class="text-muted">Выберите автора статьи</small>
                    @endif
                </div>
                
                <!-- Категории -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-folder"></i> Категории
                    </label>
                    <div class="row">
                        @foreach($categories as $category)
                            <div class="col-md-4">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="category_ids[]" 
                                       value="{{ $category->term_taxonomy_id }}" 
                                       id="category_{{ $category->term_taxonomy_id }}"
                                       {{ in_array($category->term_taxonomy_id, old('category_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="category_{{ $category->term_taxonomy_id }}">
                                    {{ $category->term->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Теги -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tags"></i> Теги
                    </label>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                        @if($tags->isEmpty())
                            <p class="text-muted mb-0">Теги не найдены</p>
                        @else
                            <div class="row">
                                @foreach($tags as $tag)
                                    <div class="col-md-4">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="tag_ids[]" 
                                               value="{{ $tag->term_taxonomy_id }}" 
                                               id="tag_{{ $tag->term_taxonomy_id }}"
                                               {{ in_array($tag->term_taxonomy_id, old('tag_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tag_{{ $tag->term_taxonomy_id }}">
                                            {{ $tag->term->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <small class="text-muted">Выберите теги для этой статьи</small>
                </div>
                
                <!-- Обложка статьи (Featured Image) -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-image"></i> Обложка статьи (для главной страницы)
                    </label>
                    
                    <div id="featuredImagePreview" class="mb-2" style="display: none;">
                        <img src="" alt="Featured Image" class="img-thumbnail" style="max-width: 300px; max-height: 200px; object-fit: cover;">
                    </div>
                    
                    <div class="input-group mb-2">
                        <input type="hidden" id="featured_image_url" name="featured_image_url" value="">
                        <input type="text" class="form-control" id="featured_image_display" 
                               placeholder="Загрузите обложку для отображения на главной странице" readonly>
                        <button type="button" class="btn btn-outline-primary" id="uploadFeaturedImageBtn">
                            <i class="fas fa-upload"></i> Загрузить обложку
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="removeFeaturedImageBtn" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <input type="file" id="featuredImageInput" accept="image/*" style="display: none;">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Эта обложка будет отображаться на главной странице и в карточках статей
                    </small>
                </div>
                
                <!-- Кнопки -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Создать статью
                    </button>
                    <a href="{{ route('admin.posts') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE Editor (Self-hosted) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    // Временное хранилище загруженных изображений
    let uploadedImageSizes = null;
    let tinyMCEEditor = null;
    
    tinymce.init({
        selector: '.tinymce-editor',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code fullscreen | help',
        
        // Настройки для изображений
        image_advtab: true,
        image_caption: true,
        image_title: true,
        image_description: true,
        image_class_list: [
            {title: 'Без класса', value: ''},
            {title: 'По центру', value: 'aligncenter'},
            {title: 'Слева', value: 'alignleft'},
            {title: 'Справа', value: 'alignright'}
        ],
        
        // Обязательные поля для изображения (доступность)
        a11y_advanced_options: true,
        
        setup: function(editor) {
            tinyMCEEditor = editor;
        },
        // Загрузка изображений через drag & drop или paste
        images_upload_handler: function (blobInfo, progress) {
            return uploadImageHandler(blobInfo, progress);
        },
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px; padding: 10px; }',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
    });
    
    // Обработчик кнопки загрузки
    document.addEventListener('DOMContentLoaded', function() {
        const uploadBtn = document.getElementById('uploadImageBtn');
        const fileInput = document.getElementById('imageUploadInput');
        
        uploadBtn.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                uploadImageFile(file);
            }
            // Сбрасываем input для возможности загрузить тот же файл повторно
            fileInput.value = '';
        });
        
        // Валидация формы перед отправкой
        const form = document.querySelector('#createPostForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Отменяем стандартную отправку
            
            // Синхронизируем содержимое TinyMCE с textarea
            if (tinyMCEEditor) {
                tinyMCEEditor.save();
            }
            
            // Проверяем, что контент не пустой
            const content = document.getElementById('post_content').value.trim();
            if (!content || content === '') {
                alert('Пожалуйста, заполните содержание статьи');
                return false;
            }
            
            // Показываем прелоадер
            showLoader();
            
            // Отправляем форму через AJAX
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateLoader('SEO генерируется...', 'Используем искусственный интеллект', 66);
                    
                    // Небольшая задержка для показа прогресса
                    setTimeout(() => {
                        updateLoader('Готово!', 'SEO-данные сгенерированы, перенаправление...', 100);
                        
                        setTimeout(() => {
                            window.location.href = data.redirect_url + '?seo_generated=' + (data.seo_generated ? '1' : '0');
                        }, 800);
                    }, 1500);
                } else {
                    hideLoader();
                    alert('Ошибка при создании статьи: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                hideLoader();
                alert('Ошибка при создании статьи. Проверьте консоль для деталей.');
            });
            
            return false;
        });
    });
    
    // Функции для управления прелоадером
    function showLoader() {
        const overlay = document.getElementById('seoLoaderOverlay');
        overlay.style.display = 'flex';
        updateLoader('Создание статьи...', 'Сохраняем данные', 33);
    }
    
    function updateLoader(title, text, progress) {
        document.getElementById('loaderTitle').textContent = title;
        document.getElementById('loaderText').textContent = text;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressText').textContent = Math.round(progress) + '%';
    }
    
    function hideLoader() {
        document.getElementById('seoLoaderOverlay').style.display = 'none';
    }
    
    // Загрузка обложки статьи (Featured Image)
    document.getElementById('uploadFeaturedImageBtn')?.addEventListener('click', function() {
        document.getElementById('featuredImageInput').click();
    });

    document.getElementById('featuredImageInput')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            uploadFeaturedImageForCreate(file);
        }
    });

    // Удаление обложки
    document.getElementById('removeFeaturedImageBtn')?.addEventListener('click', function() {
        if (confirm('Удалить обложку?')) {
            document.getElementById('featured_image_url').value = '';
            document.getElementById('featured_image_display').value = '';
            
            const preview = document.getElementById('featuredImagePreview');
            preview.style.display = 'none';
            
            this.style.display = 'none';
        }
    });

    // Функция загрузки обложки при создании статьи
    function uploadFeaturedImageForCreate(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        const btn = document.getElementById('uploadFeaturedImageBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
        btn.disabled = true;
        
        fetch('{{ route("admin.posts.upload-image") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sizes && data.sizes.large) {
                const imageUrl = data.sizes.large.url;
                
                // Обновляем скрытое поле и display поле
                document.getElementById('featured_image_url').value = imageUrl;
                document.getElementById('featured_image_display').value = file.name;
                
                // Обновляем превью
                const preview = document.getElementById('featuredImagePreview');
                const img = preview.querySelector('img');
                img.src = imageUrl;
                preview.style.display = 'block';
                
                // Показываем кнопку удаления
                document.getElementById('removeFeaturedImageBtn').style.display = 'inline-block';
                
                alert('Обложка загружена! Сохраните статью для применения.');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Ошибка загрузки обложки');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
    
    // Функция загрузки файла через кнопку
    function uploadImageFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("admin.posts.upload-image") }}');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                console.log('Upload progress:', percent.toFixed(2) + '%');
            }
        };
        
        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const json = JSON.parse(xhr.responseText);
                    console.log('Upload response:', json);
                    
                    if (json.sizes) {
                        // Показываем модальное окно выбора размера
                        showImageSizeDialog(json.sizes, function(selectedUrl) {
                            // Вставляем изображение в редактор
                            if (tinyMCEEditor) {
                                tinyMCEEditor.insertContent('<img src="' + selectedUrl + '" alt="" />');
                            }
                        });
                    } else if (json.location) {
                        // Если только один размер, вставляем сразу
                        if (tinyMCEEditor) {
                            tinyMCEEditor.insertContent('<img src="' + json.location + '" alt="" />');
                        }
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response Text:', xhr.responseText);
                    alert('Ошибка парсинга ответа сервера. Проверьте консоль для деталей.');
                }
            } else if (xhr.status === 422) {
                // Ошибка валидации
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    let errorMessage = 'Ошибка валидации:\n';
                    if (errorData.errors && errorData.errors.file) {
                        errorMessage += errorData.errors.file.join('\n');
                    } else if (errorData.message) {
                        errorMessage += errorData.message;
                    }
                    alert(errorMessage);
                } catch (e) {
                    alert('Ошибка загрузки файла. Проверьте размер и формат изображения.');
                }
            } else {
                console.error('HTTP Error:', xhr.status, xhr.responseText);
                alert('Ошибка загрузки: ' + xhr.status);
            }
        };
        
        xhr.onerror = () => {
            alert('Ошибка загрузки изображения');
        };
        
        xhr.send(formData);
    }
    
    // Общая функция загрузки для drag & drop и paste
    function uploadImageHandler(blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '{{ route("admin.posts.upload-image") }}');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            
            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };
            
            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('HTTP Error: ' + xhr.status);
                    return;
                }
                
                const json = JSON.parse(xhr.responseText);
                console.log('Upload response:', json);
                
                if (!json || !json.location) {
                    reject('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                
                // Сохраняем все размеры
                if (json.sizes) {
                    uploadedImageSizes = json.sizes;
                    // Показываем модальное окно выбора размера
                    showImageSizeDialog(json.sizes, resolve);
                } else {
                    resolve(json.location);
                }
            };
            
            xhr.onerror = () => {
                reject('Image upload failed');
            };
            
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        });
    }
    
    // Функция показа модального окна для выбора размера
    function showImageSizeDialog(sizes, callback) {
        const modal = document.createElement('div');
        modal.className = 'image-size-modal';
        modal.innerHTML = `
            <div class="image-size-overlay"></div>
            <div class="image-size-dialog">
                <h3>Выберите размер изображения</h3>
                <div class="image-size-options">
                    <div class="size-option" data-size="large" data-url="${sizes.large.url}">
                        <div class="size-preview" style="background-image: url('${sizes.large.url}')"></div>
                        <div class="size-info">
                            <strong>Большой</strong>
                            <span>${sizes.large.width}×${sizes.large.height}</span>
                        </div>
                    </div>
                    <div class="size-option" data-size="medium" data-url="${sizes.medium.url}">
                        <div class="size-preview" style="background-image: url('${sizes.medium.url}')"></div>
                        <div class="size-info">
                            <strong>Средний</strong>
                            <span>${sizes.medium.width}×${sizes.medium.height}</span>
                        </div>
                    </div>
                    <div class="size-option" data-size="small" data-url="${sizes.small.url}">
                        <div class="size-preview" style="background-image: url('${sizes.small.url}')"></div>
                        <div class="size-info">
                            <strong>Маленький</strong>
                            <span>${sizes.small.width}×${sizes.small.height}</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-secondary mt-3 cancel-btn">Отмена</button>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Сохраняем callback
        window.imageSizeCallback = callback;
        
        // Добавляем обработчики событий
        modal.querySelector('.image-size-overlay').addEventListener('click', closeImageSizeDialog);
        modal.querySelector('.cancel-btn').addEventListener('click', closeImageSizeDialog);
        
        // Обработчики для выбора размера
        modal.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const url = this.getAttribute('data-url');
                const size = this.getAttribute('data-size');
                console.log('Clicked size:', size, 'URL:', url);
                selectImageSize(size, url);
            });
        });
    }
    
    function selectImageSize(size, url) {
        console.log('selectImageSize called:', size, url);
        if (window.imageSizeCallback) {
            console.log('Callback exists, calling with URL:', url);
            window.imageSizeCallback(url);
        } else {
            console.error('No callback found!');
        }
        closeImageSizeDialog();
    }
    
    function closeImageSizeDialog() {
        const modal = document.querySelector('.image-size-modal');
        if (modal) {
            modal.remove();
        }
        window.imageSizeCallback = null;
        uploadedImageSizes = null;
    }
</script>

<style>
.image-size-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
}

.image-size-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.image-size-dialog {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    max-width: 700px;
    width: 90%;
}

.image-size-dialog h3 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #333;
}

.image-size-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.size-option {
    cursor: pointer;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    transition: all 0.3s;
    position: relative;
}

.size-option:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

.size-option:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
}

.size-option::after {
    content: '✓ Выбрать';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(13, 110, 253, 0.95);
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    font-weight: bold;
}

.size-option:hover::after {
    opacity: 1;
}

.size-preview {
    width: 100%;
    height: 120px;
    background-size: cover;
    background-position: center;
    border-radius: 5px;
    margin-bottom: 10px;
}

.size-info {
    text-align: center;
}

.size-info strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.size-info span {
    display: block;
    font-size: 12px;
    color: #666;
}
</style>

@endsection
