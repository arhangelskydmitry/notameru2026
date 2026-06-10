<!-- Cookie Notice -->
<div id="cookieNotice" class="cookie-notice" style="display: none;">
    <div class="cookie-notice-content">
        <div class="cookie-notice-text">
            <i class="fas fa-cookie-bite"></i>
            <span>
                Мы используем файлы cookie для улучшения работы сайта, анализа посещаемости и персонализации контента. 
                Продолжая использовать сайт, вы соглашаетесь с использованием cookie.
                <a href="{{ route('privacy') }}" target="_blank">Узнать больше</a>
            </span>
        </div>
        <div class="cookie-notice-buttons">
            <button type="button" class="btn btn-success btn-sm" id="acceptCookies">
                <i class="fas fa-check"></i> Принять
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="declineCookies">
                <i class="fas fa-times"></i> Отклонить
            </button>
        </div>
    </div>
</div>

<style>
.cookie-notice {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
    z-index: 10000;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cookie-notice-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.cookie-notice-text {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 14px;
    line-height: 1.6;
}

.cookie-notice-text i {
    font-size: 32px;
    flex-shrink: 0;
}

.cookie-notice-text a {
    color: #fff;
    text-decoration: underline;
    font-weight: 600;
}

.cookie-notice-text a:hover {
    color: #ffd700;
}

.cookie-notice-buttons {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.cookie-notice-buttons .btn {
    white-space: nowrap;
    font-weight: 600;
    padding: 8px 20px;
}

/* Мобильная версия */
@media (max-width: 768px) {
    .cookie-notice {
        padding: 15px;
    }
    
    .cookie-notice-content {
        flex-direction: column;
        gap: 15px;
    }
    
    .cookie-notice-text {
        font-size: 13px;
        text-align: center;
    }
    
    .cookie-notice-text i {
        font-size: 24px;
    }
    
    .cookie-notice-buttons {
        width: 100%;
        justify-content: center;
    }
    
    .cookie-notice-buttons .btn {
        flex: 1;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    const COOKIE_NAME = 'cookie_consent';
    const COOKIE_EXPIRY_DAYS = 365;
    
    // Проверяем, есть ли уже согласие
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
    }
    
    // Показываем уведомление если нет согласия
    const consent = getCookie(COOKIE_NAME);
    if (!consent) {
        document.getElementById('cookieNotice').style.display = 'block';
    }
    
    // Принять cookies
    document.getElementById('acceptCookies').addEventListener('click', function() {
        setCookie(COOKIE_NAME, 'accepted', COOKIE_EXPIRY_DAYS);
        hideCookieNotice();
        
        // Здесь можно инициализировать аналитику
        // Например: initGoogleAnalytics(), initYandexMetrika() и т.д.
        console.log('Cookie consent accepted');
    });
    
    // Отклонить cookies
    document.getElementById('declineCookies').addEventListener('click', function() {
        setCookie(COOKIE_NAME, 'declined', COOKIE_EXPIRY_DAYS);
        hideCookieNotice();
        console.log('Cookie consent declined');
    });
    
    function hideCookieNotice() {
        const notice = document.getElementById('cookieNotice');
        notice.style.animation = 'slideDown 0.5s ease-out';
        setTimeout(() => {
            notice.style.display = 'none';
        }, 500);
    }
})();
</script>

<style>
@keyframes slideDown {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}
</style>

