-- Добавление существующего счетчика Яндекс Метрики в систему управления

INSERT INTO `counters` (`name`, `code`, `sort_order`, `is_active`, `position`, `created_at`, `updated_at`) 
VALUES (
    'Яндекс Метрика (ID: 93779125)',
    '<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,\'script\',\'https://mc.yandex.ru/metrika/tag.js\', \'ym\');

    ym(93779125, \'init\', {
        webvisor: true,
        clickmap: true,
        trackLinks: true,
        accurateTrackBounce: true
    });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/93779125" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->',
    0,
    1,
    'footer',
    NOW(),
    NOW()
);
