# 🔧 Исправление: SweetAlert2 не найден

**Проблема:** `ReferenceError: Can't find variable: Swal`

**Причина:** Библиотека SweetAlert2 не подключена в layout

---

## ✅ Исправление

Файл: `resources/views/layouts/admin.blade.php`

**Добавить строку после Bootstrap:**

```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>  <!-- ← ДОБАВИТЬ ЭТУ СТРОКУ -->
```

---

## 📝 Полный Код (строки 325-327)

```html
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
```

---

## 🚀 После Исправления

1. Загрузите исправленный `resources/views/layouts/admin.blade.php`
2. Обновите страницу (Ctrl+F5)
3. Попробуйте создать бекап снова

---

✅ Ошибка исправлена! SweetAlert2 теперь доступен на всех страницах админки.
