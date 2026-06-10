import Foundation

struct AppSettings: Codable, Equatable {
    var apiBaseURL: String = AppInfo.defaultAPIBase
    var authToken: String = ""
    var deviceName: String = ProcessInfo.processInfo.hostName
}

struct AuthUser: Codable, Equatable {
    let id: Int
    let name: String
    let email: String
    let position: String?
    let role: String?
    let roleLabel: String?
    let isSuperAdmin: Bool
    let isEditor: Bool

    enum CodingKeys: String, CodingKey {
        case id, name, email, position, role
        case roleLabel = "role_label"
        case isSuperAdmin = "is_super_admin"
        case isEditor = "is_editor"
    }
}

struct ArticleItem: Codable, Identifiable, Equatable {
    let id: Int
    let title: String
    let status: String
    let authorId: Int
    let postDate: String
    let modified: String
    let slug: String

    enum CodingKeys: String, CodingKey {
        case id, title, status, slug, modified
        case authorId = "author_id"
        case postDate = "post_date"
    }
}

struct ArticleDetail: Codable, Equatable {
    let id: Int
    let title: String
    let status: String
    let content: String
    let excerpt: String
    let seo: ArticleSEO?
    let url: String?
}

struct ArticleSEO: Codable, Equatable {
    var seoTitle: String?
    var seoDescription: String?
    var focusKeyword: String?
    var ogTitle: String?
    var ogDescription: String?

    init(seoTitle: String? = nil, seoDescription: String? = nil, focusKeyword: String? = nil, ogTitle: String? = nil, ogDescription: String? = nil) {
        self.seoTitle = seoTitle
        self.seoDescription = seoDescription
        self.focusKeyword = focusKeyword
        self.ogTitle = ogTitle
        self.ogDescription = ogDescription
    }

    enum CodingKeys: String, CodingKey {
        case seoTitle = "seo_title"
        case seoDescription = "seo_description"
        case focusKeyword = "focus_keyword"
        case ogTitle = "og_title"
        case ogDescription = "og_description"
    }
}

struct SummarizeResult: Codable, Equatable {
    let summary: String
    let bullets: [String]
    let readingTimeMinutes: Int

    enum CodingKeys: String, CodingKey {
        case summary, bullets
        case readingTimeMinutes = "reading_time_minutes"
    }
}

struct PressCardItem: Codable, Identifiable, Equatable {
    let id: Int
    let userId: Int
    let cardNumber: String
    let fullName: String
    let position: String?
    let organization: String
    let issuedAt: String
    let expiresAt: String
    let status: String
    let statusLabel: String
    let verifyUrl: String
    let pdfUrl: String?
    let user: StaffAccountSummary?

    enum CodingKeys: String, CodingKey {
        case id, position, organization, status
        case userId = "user_id"
        case cardNumber = "card_number"
        case fullName = "full_name"
        case issuedAt = "issued_at"
        case expiresAt = "expires_at"
        case statusLabel = "status_label"
        case verifyUrl = "verify_url"
        case pdfUrl = "pdf_url"
        case user
    }
}

struct StaffUser: Codable, Identifiable, Equatable {
    let id: Int
    let name: String
    let email: String
    let login: String?
    let slug: String?
    let position: String?
    let role: String?
    let roleLabel: String?
    let active: Bool
    let pressCard: StaffPressCard?
    let statistics: StaffStatistics?

    enum CodingKeys: String, CodingKey {
        case id, name, email, login, slug, position, role, active, statistics
        case roleLabel = "role_label"
        case pressCard = "press_card"
    }
}

struct StaffAccountSummary: Codable, Equatable {
    let id: Int
    let name: String
    let email: String
    let login: String?
    let slug: String?
    let position: String?
    let role: String?
    let roleLabel: String?

    enum CodingKeys: String, CodingKey {
        case id, name, email, login, slug, position, role
        case roleLabel = "role_label"
    }
}

struct StaffPressCard: Codable, Equatable {
    let id: Int
    let cardNumber: String
    let status: String
    let statusLabel: String
    let expiresAt: String?
    let verifyUrl: String?

    enum CodingKeys: String, CodingKey {
        case id, status
        case cardNumber = "card_number"
        case statusLabel = "status_label"
        case expiresAt = "expires_at"
        case verifyUrl = "verify_url"
    }
}

struct StaffStatistics: Codable, Equatable {
    let totalPosts: Int
    let publishedPosts: Int
    let draftPosts: Int
    let thisMonthPosts: Int
    let thisWeekPosts: Int
    let totalViews: Int
    let totalComments: Int
    let lastPostDate: String?

    enum CodingKeys: String, CodingKey {
        case totalPosts = "total_posts"
        case publishedPosts = "published_posts"
        case draftPosts = "draft_posts"
        case thisMonthPosts = "this_month_posts"
        case thisWeekPosts = "this_week_posts"
        case totalViews = "total_views"
        case totalComments = "total_comments"
        case lastPostDate = "last_post_date"
    }
}
