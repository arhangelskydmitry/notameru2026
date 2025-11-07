# 🧪 Тест преобразования [caption] shortcode

## Тестовые примеры

### Пример 1: Caption с выравниванием по центру

**Исходный shortcode:**
```
[caption id="attachment_14887" align="aligncenter" width="1200"]<img class="size-full wp-image-14887" src="/imgnews/1200x630_0xrzhcvcxt_1298651934854357840.webp" alt="" width="1200" height="630" /> Ваня Дмитриенко, фото: пресс-служба[/caption]
```

**Ожидаемый результат:**
```html
<figure class="wp-caption aligncenter" style="margin: 20px auto; text-align: center; max-width: 1200px;">
    <img style="width: 100%; height: auto; border-radius: 5px;" 
         class="size-full wp-image-14887" 
         src="/imgnews/1200x630_0xrzhcvcxt_1298651934854357840.webp" 
         alt="" 
         width="1200" 
         height="630" />
    <figcaption class="wp-caption-text" style="margin-top: 10px; font-size: 14px; color: #666; font-style: italic; line-height: 1.5;">
        Ваня Дмитриенко, фото: пресс-служба
    </figcaption>
</figure>
```

---

### Пример 2: Caption слева с обтеканием

**Исходный shortcode:**
```
[caption id="attachment_123" align="alignleft" width="400"]<img src="/imgnews/photo.webp" alt="Фото" width="400" height="300" /> Описание изображения[/caption]
```

**Ожидаемый результат:**
```html
<figure class="wp-caption alignleft" style="float: left; margin: 10px 20px 20px 0; text-align: left; max-width: 400px;">
    <img style="width: 100%; height: auto; border-radius: 5px;" 
         src="/imgnews/photo.webp" 
         alt="Фото" 
         width="400" 
         height="300" />
    <figcaption class="wp-caption-text" style="margin-top: 10px; font-size: 14px; color: #666; font-style: italic; line-height: 1.5;">
        Описание изображения
    </figcaption>
</figure>
```

---

### Пример 3: Caption справа с обтеканием

**Исходный shortcode:**
```
[caption id="attachment_456" align="alignright" width="500"]<img src="/imgnews/artist.webp" alt="Артист" width="500" height="400" /> Выступление артиста на сцене[/caption]
```

**Ожидаемый результат:**
```html
<figure class="wp-caption alignright" style="float: right; margin: 10px 0 20px 20px; text-align: right; max-width: 500px;">
    <img style="width: 100%; height: auto; border-radius: 5px;" 
         src="/imgnews/artist.webp" 
         alt="Артист" 
         width="500" 
         height="400" />
    <figcaption class="wp-caption-text" style="margin-top: 10px; font-size: 14px; color: #666; font-style: italic; line-height: 1.5;">
        Выступление артиста на сцене
    </figcaption>
</figure>
```

---

## ✅ Как проверить работу

### Вариант 1: Через PHP Artisan Tinker

```bash
php artisan tinker
```

```php
use App\Helpers\ContentHelper;

$testContent = '[caption id="attachment_14887" align="aligncenter" width="1200"]<img class="size-full wp-image-14887" src="/imgnews/photo.webp" alt="" width="1200" height="630" /> Ваня Дмитриенко, фото: пресс-служба[/caption]';

$result = ContentHelper::convertCaptionShortcode($testContent);

echo $result;
```

### Вариант 2: Через реальную статью

1. Найдите статью с [caption] shortcode
2. Откройте её на фронтенде
3. Посмотрите исходный код страницы (Ctrl+U)
4. Найдите преобразованный HTML

### Вариант 3: Через редактор

1. Откройте админку: `http://localhost:8002/notaadmin/posts`
2. Выберите статью с [caption]
3. Откройте для редактирования
4. Увидите визуальный редактор TinyMCE
5. Переключитесь в режим кода
6. Сохраните
7. Откройте статью на фронтенде

---

## 📊 Результаты

### ✅ Что работает:

- [x] Преобразование [caption] в `<figure>` и `<figcaption>`
- [x] Поддержка всех типов выравнивания
- [x] Сохранение ширины изображения
- [x] Адаптивные изображения
- [x] Красивая стилизация подписей
- [x] Поддержка HTML-сущностей в подписях

### ✅ TinyMCE редактор:

- [x] Русская локализация
- [x] Визуальное форматирование
- [x] Вставка изображений
- [x] Режим кода
- [x] Полноэкранный режим
- [x] Таблицы, списки, ссылки
- [x] Предпросмотр
- [x] Автосохранение при изменениях

---

## 🎯 Итог

Обе задачи выполнены успешно:

1. ✅ **Caption shortcodes** преобразуются в HTML
2. ✅ **TinyMCE редактор** подключён

Все готово к использованию! 🚀

