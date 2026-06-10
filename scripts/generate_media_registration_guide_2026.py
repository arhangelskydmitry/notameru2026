from pathlib import Path
import hashlib
import sys
import textwrap
from html import escape


BASE_DIR = Path(__file__).resolve().parents[1]
LIB_DIR = BASE_DIR / ".tmp_pdf_libs"
if str(LIB_DIR) not in sys.path:
    sys.path.insert(0, str(LIB_DIR))

try:
    hashlib.md5(usedforsecurity=False)
except TypeError:
    _original_md5 = hashlib.md5

    def _compat_md5(*args, **kwargs):
        kwargs.pop("usedforsecurity", None)
        return _original_md5(*args, **kwargs)

    hashlib.md5 = _compat_md5

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    LongTable,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    TableStyle,
)


FONT_PATH = "/System/Library/Fonts/Supplemental/Arial Unicode.ttf"
OUTPUT_DIR = BASE_DIR / "docs"
MD_PATH = OUTPUT_DIR / "media-registration-guide-2026.md"
PDF_PATH = OUTPUT_DIR / "media-registration-guide-2026.pdf"


PLATFORMS = [
    {
        "name": "Yandex.News / Yandex Webmaster",
        "region": "RU/CIS",
        "kind": "Новостной агрегатор / поиск",
        "status": "Открытая self-service подача",
        "requirements": [
            "Сайт должен быть подтвержден в Yandex Webmaster.",
            "RSS 2.0 по HTTP/HTTPS, размер до 10 МБ.",
            "Для новостей нужен полный текст в `yandex:full-text`, корректный `pubDate` в RFC-822, только материалы за последние 8 дней.",
            "Материалы должны быть в свободном доступе, сайт должен быть быстрым, стабильным и иметь мобильную версию.",
            "Контент должен быть новостным/аналитическим, без призывов к насилию, жаргона и явной рекламности.",
        ],
        "submission": [
            "Добавьте сайт в Yandex Webmaster.",
            "Подтвердите права через HTML-файл, метатег или DNS.",
            "Откройте `Представление в поиске -> Свежее и актуальное` и загрузите URL RSS.",
        ],
        "review_time": "Проверка фида занимает несколько дней.",
        "index_time": "Лучший режим: обновлять фид одновременно с сайтом или в течение 5 минут после публикации.",
        "notes": "Открытая подача есть, но Яндекс отдельно подчеркивает, что не гарантирует показ всех данных в выдаче.",
        "sources": [
            "https://yandex.ru/support/webmaster/search-appearance/news.html",
            "https://yandex.com/support/webmaster/en/service/rights",
        ],
    },
    {
        "name": "Dzen",
        "region": "RU",
        "kind": "Платформа рекомендаций / паблишинг",
        "status": "Открытая self-service подача",
        "requirements": [
            "Нужно завести канал и набрать не менее 10 подписчиков.",
            "Нельзя подключать сайт с IDN-доменом.",
            "Сайт должен содержать в основном оригинальный контент в свободном доступе, без спама и переспама рекламой.",
            "RSS для Дзена должен быть отдельно размечен под требования платформы; при первом подключении нужно минимум 10 материалов.",
            "Картинки и обложки должны соответствовать требованиям Дзена; роботу нужно открыть доступ к RSS и статьям.",
        ],
        "submission": [
            "Откройте Studio -> Настройки -> Свой сайт.",
            "Добавьте домен и подтвердите права через HTML-файл или метатег.",
            "Настройте RSS-трансляцию и отправьте ленту на проверку.",
        ],
        "review_time": "Публичного SLA нет; повторные проверки RSS ограничены тремя в год.",
        "index_time": "После первой загрузки материалы из RSS обновляются в течение 7 дней; скорость попадания новых публикаций публично не гарантируется.",
        "notes": "Если отказ связан с сайтом, повторная заявка может быть доступна только через 12 месяцев.",
        "sources": [
            "https://dzen.ru/help/ru/website/site-to-channel.html",
            "https://dzen.ru/help/ru/website/rss-modify.html",
            "https://dzen.ru/help/ru/website/website-requirements.html",
        ],
    },
    {
        "name": "SMI2",
        "region": "RU",
        "kind": "Новостной агрегатор / обмен трафиком",
        "status": "Индивидуальное партнерство",
        "requirements": [
            "Публичных детальных RSS-правил для внешних издателей на сайте почти нет.",
            "По открытым материалам видно, что сервис работает как партнерская сеть для онлайн-медиа и виджетов рекомендаций.",
            "На практике нужно готовить оригинальный новостной поток, юридически чистый контент и обсуждать интеграцию индивидуально.",
        ],
        "submission": [
            "Связаться с партнерским отделом по `info@smi2.net`.",
            "Согласовать формат участия: агрегатор, обмен трафиком, виджет, монетизация.",
        ],
        "review_time": "Индивидуально, публичного SLA нет.",
        "index_time": "После партнерского подключения публичной гарантии по скорости нет.",
        "notes": "Для коммерческих и редакционных интеграций у SMI2 используется договорная схема, а не открытый издательский кабинет.",
        "sources": [
            "https://smi2.ru/software-documentation",
            "https://smi2.net/projects",
        ],
    },
    {
        "name": "Rambler/Новости",
        "region": "RU",
        "kind": "Новостной агрегатор",
        "status": "Контакт через поддержку / партнерский фид",
        "requirements": [
            "Нужен RSS 2.0 с валидным XML и кодировкой UTF-8.",
            "Сервер должен отдавать `Content-Type: application/rss+xml; charset=utf-8`.",
            "У каждой новости должен быть постоянный уникальный URL, а в item — корректные `guid`, `pubDate`, `description` и другие обязательные поля.",
            "Rambler публикует собственные правила оформления новостного потока и пример XML.",
        ],
        "submission": [
            "Подготовить фид по спецификации Rambler.",
            "Отправить заявку в поддержку/feedback раздела `Новости` с названием СМИ и URL фида.",
        ],
        "review_time": "Публичного SLA нет.",
        "index_time": "Публичного SLA нет.",
        "notes": "Рамблер сохранил документацию по формату, но не показывает полноценный self-service кабинет уровня Google или Яндекса.",
        "sources": [
            "https://help.rambler.ru/news/novosti-pravila-oformleniya-novostnogo-potoka/4/",
        ],
    },
    {
        "name": "VK Communities (RSS import)",
        "region": "RU",
        "kind": "Дистрибуция в соцсети, не каталог СМИ",
        "status": "Открытая self-service настройка",
        "requirements": [
            "Нужна собственная RSS-лента сайта.",
            "Профиль создателя сообщества должен быть активен.",
            "VK прямо предупреждает, что не приветствует импорт фидов крупных СМИ в чужие сообщества.",
            "Можно публиковать как ссылку, как статью VK + ссылку или только как статью VK.",
        ],
        "submission": [
            "Открыть `Управление сообществом -> Дополнительная информация`.",
            "Включить `Импорт RSS` и указать URL RSS-файла.",
        ],
        "review_time": "Нет отдельной модерации на этапе подключения.",
        "index_time": "Автоматический импорт по внутреннему расписанию VK; публичного SLA нет.",
        "notes": "Это не новостной агрегатор в классическом смысле, но для быстрого охвата в Рунете — полезный канал дистрибуции.",
        "sources": [
            "https://vk.com/faq17968",
            "https://vk.com/@adminsclub-rss",
        ],
    },
    {
        "name": "vc.ru",
        "region": "RU",
        "kind": "Медиаплатформа / блоговая площадка",
        "status": "Открытая регистрация аккаунта",
        "requirements": [
            "Регистрация по email или через Yandex ID.",
            "Для блога компании нужны название, почта, пароль; логотип минимум 512x512, обложка 1280x512, описание до 160 символов.",
            "Запрещены спам, подражание брендам, скрытая рекламность, нарушения закона РФ.",
            "Коммерческие блоги могут получить ограничения без подписки Pro.",
            "Администрация вправе запросить документы, подтверждающие право представлять юридическое лицо.",
        ],
        "submission": [
            "Создать аккаунт или блог компании.",
            "Заполнить профиль, оформить блог и публиковать материалы в тематические разделы.",
        ],
        "review_time": "Регистрация мгновенная; модерация работает постфактум.",
        "index_time": "Публикации появляются сразу после выпуска, если не скрыты модерацией.",
        "notes": "Подходит не для индексации новостей как агрегатор, а как дополнительная редакционная площадка/внешний канал.",
        "sources": [
            "https://vc.ru/rules",
            "https://vc.ru/terms",
            "https://vc.ru/team/1148953-kak-zaverstat-i-opublikovat-tekst-v-svoi-blog-na-vcru-i-dtf",
        ],
    },
    {
        "name": "Meduza",
        "region": "RU / global audience",
        "kind": "Редакционное СМИ",
        "status": "Публичной регистрации для внешних сайтов нет",
        "requirements": [
            "Официальной self-service схемы для включения внешнего сайта в Meduza не опубликовано.",
            "Редакция подчеркивает независимость и отказ от платного влияния на редакционный контент.",
        ],
        "submission": [
            "Для замечаний и предложений — `reports@meduza.io`.",
            "Для вопросов об использовании материалов — `requests@meduza.io`.",
        ],
        "review_time": "Не применимо.",
        "index_time": "Не применимо.",
        "notes": "Если цель — цитирование, коллаборация или репаблишинг, нужно идти через редакционные контакты, а не через фид.",
        "sources": [
            "https://meduza.io/en/pages/about",
            "https://meduza.io/en/pages/codex",
        ],
    },
    {
        "name": "TASS",
        "region": "RU / global",
        "kind": "Информагентство / партнерские релизы",
        "status": "Индивидуальная редакционная или коммерческая подача",
        "requirements": [
            "Для пресс-релизов указан прямой контакт редакции.",
            "Для размещения в разделах `Новости партнеров` / `Пресс-релизы` доступны коммерческие пакеты.",
            "В опубликованных условиях описан диапазон 500-2000 знаков, до 2 фото и 1 гиперссылки для партнерских материалов.",
            "Материалы должны соответствовать редакционной политике и требованиям к правам на контент.",
        ],
        "submission": [
            "Пресс-релизы и объявления: `glav@tass.ru`.",
            "Информационные продукты и коммерческие размещения: `rusmarket@tass.ru` или `worldmarket@tass.ru`.",
        ],
        "review_time": "Индивидуально, публичного SLA нет.",
        "index_time": "После ручного одобрения/размещения.",
        "notes": "Для полноценного партнерства и новостной интеграции у ТАСС используется договорная модель, а не свободная регистрация.",
        "sources": [
            "https://www.tass.com/contacts",
            "https://cdn.tass.ru/data/files/ru/stati-novosti-relizy.pdf",
        ],
    },
    {
        "name": "RIA Novosti / Rossiya Segodnya",
        "region": "RU / global",
        "kind": "Информагентство / newsfeeds",
        "status": "Индивидуальная редакционная или коммерческая подача",
        "requirements": [
            "Публичной self-service подачи сайта в `РИА Новости` нет.",
            "Для прав на контент и newsfeeds используется форма/обратная связь у Rossiya Segodnya.",
            "По вопросам пресс-релизов и рекламы указан `sales@ria.ru`.",
        ],
        "submission": [
            "Использование news reports / newsfeeds: форма на сайте Rossiya Segodnya.",
            "Пресс-релизы и реклама: `sales@ria.ru`.",
            "Общие вопросы по другим типам контента: `office@ria.ru`.",
        ],
        "review_time": "Индивидуально, публичного SLA нет.",
        "index_time": "После ручной обработки или договорной интеграции.",
        "notes": "Это не открытый каталог для self-service регистрации новостного сайта.",
        "sources": [
            "https://rossiyasegodnya.com/press-center/",
            "http://en.ria.ru/docs/terms_of_use.html",
        ],
    },
    {
        "name": "Google News",
        "region": "Global",
        "kind": "Новостной агрегатор / поиск",
        "status": "Алгоритмическое включение, без ручной заявки на индексацию",
        "requirements": [
            "С апреля 2024 Google больше не требует подавать публикацию для появления в Google News.",
            "Контент должен соблюдать Google Search policies, spam policies и Google News policies.",
            "Нужны прозрачные byline, даты, информация об авторах/издателе, контакты.",
            "Publisher Center полезен для брендинга и управления публикацией, а для разделов можно добавлять RSS/Atom.",
            "Для feeds: до 2 МБ на фид, до 1 МБ на статью; WebSub ускоряет доставку.",
        ],
        "submission": [
            "Проверить домен в Google Search Console.",
            "Настроить Publisher Center, если нужен брендированный профиль и секции.",
            "Для самого попадания в Google News отдельная заявка не нужна.",
        ],
        "review_time": "Нет ручной модерации на включение в Google News.",
        "index_time": "Feed обычно опрашивается каждые 30 минут, либо быстрее при WebSub.",
        "notes": "Главный фокус теперь не на форме подачи, а на техническом и редакционном соответствии.",
        "sources": [
            "https://support.google.com/news/publisher-center/answer/9606538",
            "https://support.google.com/news/publisher-center/answer/6204050",
        ],
    },
    {
        "name": "Apple News",
        "region": "US/UK/CA/AU",
        "kind": "Новостная платформа",
        "status": "Открытая заявка с ручным одобрением",
        "requirements": [
            "Подходит для профессиональных журналистских изданий.",
            "Apple News не предназначен для личных блогов, витрин бизнеса, репостеров и площадок с фактическими неточностями.",
            "Издание должно быть из поддерживаемых рынков: Australia, Canada, United Kingdom, United States.",
            "Нужен Apple Account / iCloud с включенной двухфакторной аутентификацией.",
            "Публикация идет через News Publisher, Apple News API, плагины или preferred providers; часто используется RSS/CMS-связка для проверки сайта.",
        ],
        "submission": [
            "Войти в `icloud.com -> News Publisher`.",
            "Создать канал, заполнить данные издания, логотип, сайт и параметры публикации.",
            "При необходимости подключить CMS/API и отправить канал на одобрение.",
        ],
        "review_time": "Публичного SLA по одобрению канала нет.",
        "index_time": "После публикации доставка зависит от сложности статьи и внутренней системы Apple.",
        "notes": "Для русскоязычного независимого СМИ без юрприсутствия в поддерживаемых странах Apple News обычно не первый приоритет.",
        "sources": [
            "https://support.apple.com/en-in/guide/news-publisher/apde42330c66/icloud",
            "https://developers.apple.com/news-publisher",
            "https://support.apple.com/en-am/guide/news-publisher/apd88c8447e6/icloud",
        ],
    },
    {
        "name": "Bing News",
        "region": "Global",
        "kind": "Новостной агрегатор / Microsoft surfaces",
        "status": "Алгоритмическое включение, PubHub закрыт",
        "requirements": [
            "Новые заявки через PubHub больше не принимаются.",
            "Для отбора важны authority, originality, relevance, freshness, location, language.",
            "Контент должен быть оригинальным, часто обновляемым, с понятным ownership, контактами и byline.",
            "Не допускаются сайты, созданные главным образом для агрегации чужих новостей без прав.",
        ],
        "submission": [
            "Новой формы подачи нет.",
            "Рекомендуется держать сайт в Bing Webmaster Tools и ускорять обход через IndexNow.",
        ],
        "review_time": "Нет ручной очереди для новых издателей.",
        "index_time": "Публичного SLA нет; индекс проверяют через `site:domain.com` и сигналы Bing Webmaster.",
        "notes": "Для Microsoft Start и Windows/Edge feed фактически действует та же издательская модель.",
        "sources": [
            "https://www.bing.com/webmasters/help/pubhub-deprecation-9e8ed542",
            "https://www.bing.com/webmasters/help/pubhub-publisher-guidelines-32ce5239",
        ],
    },
    {
        "name": "NewsBreak",
        "region": "US",
        "kind": "Новостной агрегатор / app",
        "status": "Открытая заявка с ручным одобрением",
        "requirements": [
            "Фокус на US-based или US-focused publishers.",
            "Нужны About page, editorial staff info, корректные bylines и image credits.",
            "Контент должен быть преимущественно оригинальным, а не агрегированным.",
            "RSS должен содержать полный текст статьи и изображения; сниппеты запрещены.",
            "Материалы должны соответствовать Community Guidelines и Publisher Network Standards.",
        ],
        "submission": [
            "Подать заявку в Publisher Partner Program.",
            "После одобрения принять Terms of Service.",
            "Создать профиль издателя и загрузить RSS, затем дождаться активации.",
        ],
        "review_time": "Публичного SLA нет.",
        "index_time": "После активации ленты статьи начинают попадать автоматически; публичного SLA нет.",
        "notes": "Для неамериканских СМИ шанс входа ниже, если сайт не делает явный US-focused newsroom.",
        "sources": [
            "https://support.newsbreak.com/publisher-faq",
            "https://help.newsbreak.com/hc/en-us/articles/36837190635405-How-do-I-deliver-my-content-to-NewsBreak",
            "https://publishers.newsbreak.com/legal",
        ],
    },
    {
        "name": "SmartNews",
        "region": "Global / strongest in US & Japan",
        "kind": "Новостной агрегатор / app",
        "status": "Открытая заявка с ручным одобрением",
        "requirements": [
            "Нужен RSS, проходящий валидацию SmartFormat Validator.",
            "Издание должно публиковать в среднем 10+ оригинальных статей в месяц.",
            "В форме просят parent company, HQ address, CEO, business email, EIN/TIN.",
            "Контент должен быть доступен для проверки без закрытого paywall или нужно дать тестовый логин.",
        ],
        "submission": [
            "Заполнить форму `Tell Us More / Apply Now`.",
            "Указать site URL и SmartNews-совместимый RSS.",
        ],
        "review_time": "Ручная проверка; публичного SLA нет.",
        "index_time": "После одобрения материалы попадают в SmartView по фиду; публичного SLA нет.",
        "notes": "Требует более формального набора сведений о компании, чем большинство других агрегаторов.",
        "sources": [
            "https://business.smartnews.com/publishers",
            "https://partner-portal.smartnews.com/publishers",
        ],
    },
    {
        "name": "Flipboard",
        "region": "Global",
        "kind": "Агрегатор / discovery platform",
        "status": "Открытая self-service подача",
        "requirements": [
            "Нужен Publisher Account.",
            "Нужно добавить avatar, description и RSS feed для review.",
            "Flipboard отдельно рекомендует быстрые, mobile-friendly страницы без intrusive popups и без редиректов на альтернативный сайт.",
        ],
        "submission": [
            "Открыть `flipboard.com/publishers`.",
            "Создать или обновить Publisher Account.",
            "Создать magazine и в разделе `sources` подать RSS feed на review.",
        ],
        "review_time": "Команда Flipboard вручную проверяет фид; публичного SLA нет.",
        "index_time": "После одобрения лента автоматически наполняет magazine и начинает распределяться по темам Flipboard.",
        "notes": "Удобно подключать не только общий RSS, но и отдельные category-based feeds.",
        "sources": [
            "https://about.flipboard.com/inside-flipboard/new-feed-your-rss-feed-into-a-flipboard-magazine/",
            "https://about.flipboard.com/inside-flipboard/flipboards-self-service-platform-opens-for-publishers-around-the-world/",
        ],
    },
    {
        "name": "Dailyhunt",
        "region": "India",
        "kind": "Новостной агрегатор / local language app",
        "status": "Email-заявка",
        "requirements": [
            "Официально просят прислать название издания, сайт, контактный email и номер телефона.",
            "На практике стоит сразу добавить RSS URL и краткое описание редакции.",
        ],
        "submission": [
            "Отправить письмо на `YourFriends@Dailyhunt.in`.",
        ],
        "review_time": "Официальная формулировка: `we will reach you shortly`; SLA не указан.",
        "index_time": "После онбординга публичного SLA нет.",
        "notes": "Если приоритет — Индия и локальные языки, Dailyhunt обязателен; для русскоязычного проекта — скорее опционально.",
        "sources": [
            "https://support.dailyhunt.in/en/support/solutions/articles/4000019905-are-you-looking-forward-to-add-your-newspaper-or-your-news-portal-on-dailyhunt-",
        ],
    },
    {
        "name": "Feedly",
        "region": "Global",
        "kind": "RSS-агрегатор / reader",
        "status": "Без заявки, работает через публичный RSS",
        "requirements": [
            "Нужен публичный RSS URL или сайт с корректной RSS autodiscovery.",
            "Feedly прямо пишет: если вы publisher и хотите быть доступны в Feedly, держите RSS публичным.",
            "Для лучшей discoverability полезны favicon, title, description и корректный feed metadata.",
        ],
        "submission": [
            "Отдельная подача не нужна.",
            "Пользователи добавляют URL сайта или RSS через `Follow Sources`; для сайтов без RSS можно использовать RSS Builder.",
        ],
        "review_time": "Нет.",
        "index_time": "Как только feed добавлен пользователем, Feedly начинает его забирать; публичного SLA по polling interval нет.",
        "notes": "Это не newsroom c editorial review, а скорее must-have техническая совместимость с RSS-экосистемой.",
        "sources": [
            "https://docs.feedly.com/article/768-follow-sources-in-feedly",
            "https://docs.feedly.com/article/288-how-to-follow-a-feed-in-your-feedly-account",
        ],
    },
    {
        "name": "Inoreader",
        "region": "Global",
        "kind": "RSS-агрегатор / reader",
        "status": "Без заявки, работает через публичный RSS",
        "requirements": [
            "Нужен публичный RSS URL или просто URL сайта, если feed обнаруживается автоматически.",
            "Inoreader поддерживает WebSub и умеет быстро добавлять любые публичные ленты.",
        ],
        "submission": [
            "Отдельной издательской заявки нет.",
            "Feed можно добавить через интерфейс поиска, прямой URL RSS или API quickadd.",
        ],
        "review_time": "Нет.",
        "index_time": "Практически сразу после подписки пользователя; дальше частота зависит от fetcher и сигнала WebSub.",
        "notes": "Подходит как проверка того, что ваш RSS вообще читабелен для глобального feed-reader рынка.",
        "sources": [
            "https://www.inoreader.com/blog/2020/10/first-steps-with-inoreader-adding-feeds.html",
            "https://www.inoreader.com/feed-fetcher",
            "https://www.innoreader.com/developers/add-subscription",
        ],
    },
    {
        "name": "NewsNow",
        "region": "UK / EU / US audience",
        "kind": "Новостной агрегатор",
        "status": "Прямые заявки закрыты, действует editorial discovery",
        "requirements": [
            "Сайт должен показывать стабильно качественный контент минимум последние 6 месяцев.",
            "Нужны уникальные URL статей, `og:image` или `twitter:image`, а также RSS/Atom или dedicated headlines page.",
            "Для некоторых спорт-сайтов NewsNow требует свой логотип на видном месте.",
            "Если сайт смешивает подходящий и неподходящий контент, нужен отдельный feed только с допустимыми headline.",
        ],
        "submission": [
            "Новых прямых applications NewsNow больше не принимает.",
            "Можно подписаться на издательские уведомления и использовать forms для update/change/remove requests.",
        ],
        "review_time": "Новая очередь для подачи отсутствует.",
        "index_time": "После включения в сеть NewsNow aim — сканировать feed примерно каждые 10 минут, но без гарантии.",
        "notes": "Технически очень полезен как эталон строгих feed/headline требований, даже если подача сейчас закрыта.",
        "sources": [
            "https://newsnow.com/publishers/",
            "https://newsnow.com/publishers/applications-guidance.html",
            "https://www.newsnow.co.uk/publishers/technical-requirements.html",
        ],
    },
    {
        "name": "upday",
        "region": "Europe",
        "kind": "Новостной агрегатор / app",
        "status": "Открытая заявка с ручным review",
        "requirements": [
            "Нужен RSS/Atom feed, который проходит W3C feed validator.",
            "Сайт должен быть mobile-friendly: responsive template, mobile version или AMP.",
            "Контент должен быть freely accessible без логина.",
            "upday требует informative, objective, non-branded, general-public content.",
        ],
        "submission": [
            "Подать издательскую заявку через раздел `For Publishers`.",
            "Дождаться ручной проверки content quality team.",
        ],
        "review_time": "До 2 недель.",
        "index_time": "После интеграции upday работает по RSS и отправляет трафик обратно на сайт; публичного SLA нет.",
        "notes": "Сильный вариант для европейских newsroom, особенно если важен Samsung ecosystem traffic.",
        "sources": [
            "https://corporate.upday.com/publishers",
        ],
    },
]


PRECHECK = [
    "Подготовьте три версии фида: универсальный RSS/Atom, отдельный полнотекстовый фид для Yandex, отдельный RSS для Dzen/экспортных платформ.",
    "Подтвердите владение доменом в Google Search Console, Yandex Webmaster и Bing Webmaster.",
    "Проверьте, что у каждой статьи есть постоянный URL, заголовок, дата публикации, автор и картинка `og:image`.",
    "Сделайте публичные страницы: `About/О редакции`, `Contacts`, `Editorial policy`, `Privacy`, `Advertise`.",
    "Уберите обязательную регистрацию/paywall из фидов, которые идут в агрегаторы. Если paywall нужен, делайте отдельные открытые sections.",
    "Проверьте, что RSS валиден, не закрыт в `robots.txt`, не отдает 403/5xx и не ломается на новых символах.",
    "Настройте sitemap, WebSub и IndexNow там, где они помогают ускорить доставку.",
    "Соберите пакет брендинга: квадратный логотип, горизонтальный логотип, favicon, обложки, краткое описание редакции на RU и EN.",
    "Заведите служебные email-адреса: `editor@`, `newsroom@`, `partnerships@`, `legal@`.",
    "Подготовьте company factsheet: юрлицо, страна, адрес, CEO/главред, monthly article volume, languages, audience geography.",
    "Сначала подайте open self-service платформы: Google, Yandex, Dzen, Flipboard, upday, SmartNews, NewsBreak.",
    "Параллельно отправьте manual outreach в TASS, RIA, SMI2 и другие площадки без открытого кабинета.",
    "После подключения отслеживайте реальное попадание статей через `site:`-запросы, редакторские кабинеты и лог fetcher-ботов.",
]


PORTAL_TEMPLATE = {
    "title": "Шаблон для порталов/форм",
    "fields": [
        ("Publication name", "Название СМИ / проекта"),
        ("Primary URL", "https://example.com"),
        ("Primary RSS feed", "https://example.com/feed.xml"),
        ("Yandex full-text RSS", "https://example.com/yandex-news.xml"),
        ("Sections / category feeds", "Политика, Технологии, Экономика и т.д."),
        ("Country / language", "Russia / Russian, English"),
        ("Editorial description", "Короткое описание редакции и тематики"),
        ("Publishing frequency", "Например: 20-40 материалов в день"),
        ("Ownership", "Юрлицо, главный редактор, CEO"),
        ("Contacts", "editor@..., partnerships@..., legal@..."),
        ("Verification", "Search Console / Webmaster already verified"),
        ("Access model", "Free access, no mandatory registration"),
    ],
}


EMAIL_TEMPLATES = [
    {
        "title": "Шаблон письма для редакционного/партнерского контакта (RU)",
        "body": textwrap.dedent(
            """\
            Тема: Подключение сайта [DOMAIN] к [PLATFORM]

            Здравствуйте!

            Меня зовут [NAME], я представляю [PUBLICATION].
            Хотим рассмотреть подключение нашего сайта к [PLATFORM].

            Кратко о проекте:
            - Домен: [DOMAIN]
            - Тематика: [TOPICS]
            - Язык(и): [LANGUAGES]
            - Частота публикаций: [VOLUME]
            - Формат: оригинальные новости / аналитика / интервью
            - Доступ: свободный, без обязательной регистрации

            Технические данные:
            - Основной RSS: [RSS_URL]
            - При необходимости полнотекстовый RSS: [FULLTEXT_RSS_URL]
            - Контакты редакции: [EDITORIAL_EMAIL]
            - Страница о редакции: [ABOUT_URL]
            - Страница контактов: [CONTACTS_URL]

            Будем благодарны за информацию о требованиях к подключению, следующем шаге и формате проверки.

            С уважением,
            [NAME]
            [ROLE]
            [PUBLICATION]
            [EMAIL]
            [PHONE]
            """
        ).strip(),
    },
    {
        "title": "Шаблон письма для международной платформы (EN)",
        "body": textwrap.dedent(
            """\
            Subject: Publisher onboarding request for [DOMAIN]

            Hello,

            My name is [NAME], and I represent [PUBLICATION].
            We would like to be considered for inclusion on [PLATFORM].

            Publication overview:
            - Website: [DOMAIN]
            - Coverage: [TOPICS]
            - Languages: [LANGUAGES]
            - Publishing cadence: [VOLUME]
            - Format: original reporting / analysis / explainers
            - Access model: free access, no mandatory login to read articles

            Technical details:
            - Primary RSS feed: [RSS_URL]
            - Full-text/news feed (if needed): [FULLTEXT_RSS_URL]
            - About page: [ABOUT_URL]
            - Contact page: [CONTACTS_URL]
            - Editorial contact: [EDITORIAL_EMAIL]

            Please let us know the next onboarding step, validation requirements, and any expected review timeline.

            Best regards,
            [NAME]
            [ROLE]
            [PUBLICATION]
            [EMAIL]
            [PHONE]
            """
        ).strip(),
    },
]


def short_status(item):
    if "Открытая" in item["status"]:
        return "Open"
    if "Алгоритмическое" in item["status"]:
        return "Algo"
    if "Email" in item["status"]:
        return "Email"
    if "закрыты" in item["status"]:
        return "Closed"
    return "Manual"


def register_fonts():
    pdfmetrics.registerFont(TTFont("GuideFont", FONT_PATH))
    pdfmetrics.registerFont(TTFont("GuideFontBold", FONT_PATH))


def build_styles():
    styles = getSampleStyleSheet()
    styles.add(
        ParagraphStyle(
            name="GuideTitle",
            parent=styles["Title"],
            fontName="GuideFont",
            fontSize=18,
            leading=22,
            alignment=TA_CENTER,
            spaceAfter=8,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideSubtitle",
            parent=styles["Normal"],
            fontName="GuideFont",
            fontSize=9,
            leading=12,
            alignment=TA_CENTER,
            textColor=colors.HexColor("#555555"),
            spaceAfter=10,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideH1",
            parent=styles["Heading1"],
            fontName="GuideFont",
            fontSize=14,
            leading=18,
            textColor=colors.HexColor("#1b1b1b"),
            spaceBefore=8,
            spaceAfter=6,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideH2",
            parent=styles["Heading2"],
            fontName="GuideFont",
            fontSize=11,
            leading=14,
            textColor=colors.HexColor("#1b1b1b"),
            spaceBefore=8,
            spaceAfter=4,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideBody",
            parent=styles["BodyText"],
            fontName="GuideFont",
            fontSize=8.8,
            leading=12,
            alignment=TA_LEFT,
            spaceAfter=3,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideSmall",
            parent=styles["BodyText"],
            fontName="GuideFont",
            fontSize=7.7,
            leading=10,
            textColor=colors.HexColor("#555555"),
            spaceAfter=2,
        )
    )
    styles.add(
        ParagraphStyle(
            name="GuideBullet",
            parent=styles["BodyText"],
            fontName="GuideFont",
            fontSize=8.6,
            leading=11.5,
            leftIndent=10,
            firstLineIndent=-7,
            bulletIndent=0,
            spaceAfter=1.5,
        )
    )
    return styles


def bullet_paragraph(text, styles):
    return Paragraph(f"• {escape(text)}", styles["GuideBullet"])


def sources_line(urls):
    return " | ".join(urls)


def generate_markdown():
    lines = [
        "# Руководство по быстрой регистрации сайта в агрегаторах и каталогах СМИ",
        "",
        "Дата: 10 апреля 2026",
        "",
        "Это практический гид по 20 площадкам, значимым для дистрибуции новостей: открытые агрегаторы, платформы рекомендаций, RSS-ридеры и несколько редакционных брендов, которые вы явно попросили включить (`Meduza`, `vc.ru`, `TASS`, `RIA Novosti`).",
        "",
        "Важно: часть из них не имеет публичной self-service регистрации. Для таких площадок ниже указан реальный рабочий путь: партнерский email, редакционный контакт или алгоритмическое включение без формы заявки.",
        "",
        "## Быстрый чек-лист",
        "",
    ]
    for idx, item in enumerate(PRECHECK, start=1):
        lines.append(f"{idx}. {item}")
    lines.extend(
        [
            "",
            "## Матрица площадок",
            "",
        ]
    )

    for idx, item in enumerate(PLATFORMS, start=1):
        lines.extend(
            [
                f"## {idx}. {item['name']}",
                "",
                f"- Регион: {item['region']}",
                f"- Тип: {item['kind']}",
                f"- Статус: {item['status']}",
                "- Требования:",
            ]
        )
        for req in item["requirements"]:
            lines.append(f"  - {req}")
        lines.append("- Процесс подачи:")
        for step in item["submission"]:
            lines.append(f"  - {step}")
        lines.extend(
            [
                f"- Срок review/модерации: {item['review_time']}",
                f"- Скорость индексации/появления материалов: {item['index_time']}",
                f"- Комментарий: {item['notes']}",
                "- Источники:",
            ]
        )
        for src in item["sources"]:
            lines.append(f"  - {src}")
        lines.append("")

    lines.extend(
        [
            "## Шаблон для порталов/форм",
            "",
        ]
    )
    for key, value in PORTAL_TEMPLATE["fields"]:
        lines.append(f"- {key}: {value}")

    for tmpl in EMAIL_TEMPLATES:
        lines.extend(
            [
                "",
                f"## {tmpl['title']}",
                "",
                "```text",
                tmpl["body"],
                "```",
            ]
        )

    MD_PATH.write_text("\n".join(lines), encoding="utf-8")


def generate_pdf():
    register_fonts()
    styles = build_styles()
    doc = SimpleDocTemplate(
        str(PDF_PATH),
        pagesize=A4,
        topMargin=14 * mm,
        bottomMargin=12 * mm,
        leftMargin=12 * mm,
        rightMargin=12 * mm,
        title="Media Registration Guide 2026",
        author="Cursor",
    )

    story = []
    story.append(Paragraph("Руководство по быстрой регистрации сайта в агрегаторах и каталогах СМИ", styles["GuideTitle"]))
    story.append(
        Paragraph(
            "20 площадок, включая Google News, Yandex, Dzen, SmartNews, NewsBreak, Flipboard, TASS, RIA Novosti, Meduza и vc.ru",
            styles["GuideSubtitle"],
        )
    )
    story.append(
        Paragraph(
            "Ниже объединены четыре режима работы с площадками: открытая self-service подача, ручная модерация, алгоритмическое включение без формы и редакционно-партнерские контакты.",
            styles["GuideBody"],
        )
    )

    story.append(Paragraph("Приоритетный порядок действий", styles["GuideH1"]))
    for item in PRECHECK:
        story.append(bullet_paragraph(item, styles))

    story.append(Spacer(1, 6))
    story.append(Paragraph("Краткая матрица", styles["GuideH1"]))

    matrix_header = [
        Paragraph("<b>Площадка</b>", styles["GuideBody"]),
        Paragraph("<b>Формат входа</b>", styles["GuideBody"]),
        Paragraph("<b>Review</b>", styles["GuideBody"]),
        Paragraph("<b>Новые публикации</b>", styles["GuideBody"]),
    ]
    matrix_rows = [matrix_header]
    for item in PLATFORMS:
        matrix_rows.append(
            [
                Paragraph(escape(item["name"]), styles["GuideBody"]),
                Paragraph(escape(item["status"]), styles["GuideBody"]),
                Paragraph(escape(item["review_time"]), styles["GuideBody"]),
                Paragraph(escape(item["index_time"]), styles["GuideBody"]),
            ]
        )

    matrix = LongTable(matrix_rows, colWidths=[46 * mm, 50 * mm, 40 * mm, 44 * mm], repeatRows=1)
    matrix.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#dbeafe")),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.HexColor("#111827")),
                ("GRID", (0, 0), (-1, -1), 0.35, colors.HexColor("#cbd5e1")),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#f8fafc")]),
                ("LEFTPADDING", (0, 0), (-1, -1), 4),
                ("RIGHTPADDING", (0, 0), (-1, -1), 4),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
            ]
        )
    )
    story.append(matrix)

    story.append(PageBreak())
    story.append(Paragraph("Детали по площадкам", styles["GuideH1"]))

    for idx, item in enumerate(PLATFORMS, start=1):
        story.append(Paragraph(f"{idx}. {escape(item['name'])}", styles["GuideH2"]))
        story.append(
            Paragraph(
                f"<b>Регион:</b> {escape(item['region'])} | <b>Тип:</b> {escape(item['kind'])} | <b>Статус:</b> {escape(item['status'])}",
                styles["GuideBody"],
            )
        )
        story.append(Paragraph("<b>Требования</b>", styles["GuideBody"]))
        for req in item["requirements"]:
            story.append(bullet_paragraph(req, styles))
        story.append(Paragraph("<b>Процесс подачи</b>", styles["GuideBody"]))
        for step in item["submission"]:
            story.append(bullet_paragraph(step, styles))
        story.append(Paragraph(f"<b>Review:</b> {escape(item['review_time'])}", styles["GuideBody"]))
        story.append(Paragraph(f"<b>Индексация/публикация:</b> {escape(item['index_time'])}", styles["GuideBody"]))
        story.append(Paragraph(f"<b>Комментарий:</b> {escape(item['notes'])}", styles["GuideBody"]))
        story.append(Paragraph(f"<b>Источники:</b> {escape(sources_line(item['sources']))}", styles["GuideSmall"]))
        story.append(Spacer(1, 4))

    story.append(PageBreak())
    story.append(Paragraph("Шаблон данных для форм", styles["GuideH1"]))
    story.append(
        Paragraph(
            "Используйте этот блок как основу для Google/SmartNews/NewsBreak/upday/Flipboard и похожих заявок.",
            styles["GuideBody"],
        )
    )
    form_rows = [[Paragraph("<b>Поле</b>", styles["GuideBody"]), Paragraph("<b>Что вставлять</b>", styles["GuideBody"])]]
    for key, value in PORTAL_TEMPLATE["fields"]:
        form_rows.append([Paragraph(escape(key), styles["GuideBody"]), Paragraph(escape(value), styles["GuideBody"])])
    form_table = LongTable(form_rows, colWidths=[58 * mm, 118 * mm], repeatRows=1)
    form_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#dcfce7")),
                ("GRID", (0, 0), (-1, -1), 0.35, colors.HexColor("#cbd5e1")),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 4),
                ("RIGHTPADDING", (0, 0), (-1, -1), 4),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
            ]
        )
    )
    story.append(form_table)

    story.append(Spacer(1, 8))
    story.append(Paragraph("Готовые email-шаблоны", styles["GuideH1"]))
    for tmpl in EMAIL_TEMPLATES:
        story.append(Paragraph(escape(tmpl["title"]), styles["GuideH2"]))
        for line in tmpl["body"].splitlines():
            if line.strip():
                story.append(Paragraph(escape(line), styles["GuideBody"]))
            else:
                story.append(Spacer(1, 2))

    doc.build(story)


def main():
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    generate_markdown()
    generate_pdf()
    print(f"Wrote {MD_PATH}")
    print(f"Wrote {PDF_PATH}")


if __name__ == "__main__":
    main()
