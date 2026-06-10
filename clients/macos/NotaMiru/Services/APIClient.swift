import Foundation

enum APIError: LocalizedError {
    case unauthorized
    case server(String)
    case decode

    var errorDescription: String? {
        switch self {
        case .unauthorized: return "Сессия истекла — войдите снова"
        case .server(let msg): return msg
        case .decode: return "Ошибка разбора ответа сервера"
        }
    }
}

final class APIClient {
    var baseURL: String
    var token: String = ""

    init(baseURL: String) {
        self.baseURL = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
    }

    func login(email: String, password: String, deviceName: String) async throws -> (token: String, user: AuthUser) {
        struct Body: Encodable { let email, password, device_name: String }
        struct Resp: Decodable { let token: String; let user: AuthUser }
        let resp: Resp = try await post("/auth/login", body: Body(email: email, password: password, device_name: deviceName), auth: false)
        return (resp.token, resp.user)
    }

    func me() async throws -> AuthUser {
        struct Resp: Decodable { let user: AuthUser }
        let resp: Resp = try await get("/auth/me")
        return resp.user
    }

    func logout() async throws {
        let _: EmptyResp = try await postEmpty("/auth/logout")
    }

    func articles(status: String? = nil, query: String? = nil) async throws -> [ArticleItem] {
        struct Resp: Decodable { let data: [ArticleItem] }
        var parts: [String] = []
        if let status { parts.append("status=\(status)") }
        if let query, !query.isEmpty {
            let q = query.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? query
            parts.append("q=\(q)")
        }
        let path = parts.isEmpty ? "/posts" : "/posts?" + parts.joined(separator: "&")
        let resp: Resp = try await get(path)
        return resp.data
    }

    func article(id: Int) async throws -> ArticleDetail {
        struct Resp: Decodable { let data: ArticleDetail }
        let resp: Resp = try await get("/posts/\(id)")
        return resp.data
    }

    func createArticle(title: String, content: String, status: String) async throws -> ArticleDetail {
        struct Body: Encodable { let title, content, status: String }
        struct Resp: Decodable { let data: ArticleDetail }
        let resp: Resp = try await post("/posts", body: Body(title: title, content: content, status: status))
        return resp.data
    }

    func updateArticle(id: Int, title: String, content: String, excerpt: String, status: String, seo: ArticleSEO?) async throws -> ArticleDetail {
        struct SEOBody: Encodable {
            let seo_title, seo_description, focus_keyword, og_title, og_description: String?
        }
        struct Body: Encodable {
            let title, content, excerpt, status: String
            let seo: SEOBody?
        }
        let seoBody: SEOBody? = seo.map {
            SEOBody(seo_title: $0.seoTitle, seo_description: $0.seoDescription, focus_keyword: $0.focusKeyword, og_title: $0.ogTitle, og_description: $0.ogDescription)
        }
        struct Resp: Decodable { let data: ArticleDetail }
        let resp: Resp = try await put("/posts/\(id)", body: Body(title: title, content: content, excerpt: excerpt, status: status, seo: seoBody))
        return resp.data
    }

    func generateSEO(title: String, content: String) async throws -> ArticleSEO {
        struct Body: Encodable { let title, content: String }
        struct SEOData: Decodable {
            let seo_title, seo_description, focus_keyword, og_title, og_description: String?
        }
        struct Resp: Decodable { let data: SEOData }
        let resp: Resp = try await post("/posts/seo/generate", body: Body(title: title, content: content))
        let d = resp.data
        return ArticleSEO(seoTitle: d.seo_title, seoDescription: d.seo_description, focusKeyword: d.focus_keyword, ogTitle: d.og_title, ogDescription: d.og_description)
    }

    func summarize(title: String, content: String) async throws -> SummarizeResult {
        struct Body: Encodable { let title, content: String }
        struct Resp: Decodable { let data: SummarizeResult }
        let resp: Resp = try await post("/posts/summarize", body: Body(title: title, content: content))
        return resp.data
    }

    func pressCards() async throws -> [PressCardItem] {
        struct Resp: Decodable { let data: [PressCardItem] }
        let resp: Resp = try await get("/press-cards")
        return resp.data
    }

    func journalists() async throws -> [JournalistItem] {
        struct Resp: Decodable { let data: [JournalistItem] }
        let resp: Resp = try await get("/press-cards/journalists")
        return resp.data
    }

    func createPressCard(_ input: PressCardInput) async throws -> PressCardItem {
        struct Body: Encodable {
            let user_id: Int
            let full_name, organization, issued_at, expires_at: String
            let position, notes: String?
        }
        struct Resp: Decodable { let data: PressCardItem }
        let resp: Resp = try await post("/press-cards", body: Body(
            user_id: input.userId,
            full_name: input.fullName,
            organization: input.organization,
            issued_at: input.issuedAt,
            expires_at: input.expiresAt,
            position: input.position.isEmpty ? nil : input.position,
            notes: input.notes.isEmpty ? nil : input.notes
        ))
        return resp.data
    }

    func staffUsers() async throws -> [StaffUser] {
        struct Resp: Decodable { let data: [StaffUser] }
        let resp: Resp = try await get("/users")
        return resp.data
    }

    func createStaffUser(_ input: StaffUserInput) async throws -> StaffUser {
        struct Body: Encodable {
            let name: String
            let email: String
            let login: String
            let role: String
            let position: String?
            let password: String?
            let active: Bool
        }
        struct Resp: Decodable { let data: StaffUser }
        let resp: Resp = try await post("/users", body: Body(
            name: input.name,
            email: input.email,
            login: input.login,
            role: input.role,
            position: input.position.isEmpty ? nil : input.position,
            password: input.password.isEmpty ? nil : input.password,
            active: input.active
        ))
        return resp.data
    }

    func setUserActive(id: Int, active: Bool) async throws {
        struct Body: Encodable { let active: Bool }
        let _: EmptyResp = try await patch("/users/\(id)/active", body: Body(active: active))
    }

    func assistantChat(
        text: String,
        sessionId: String?,
        context: [String: String] = [:],
        attachments: [AssistantAttachment] = []
    ) async throws -> AssistantReply {
        struct Body: Encodable {
            let text: String
            let session_id: String?
            let source: String
            let context: [String: String]
            let attachments: [AssistantAttachment]
        }
        struct DataBody: Decodable {
            let text: String
            let sessionId: String
            let memoryId: Int?
            let images: [String]?
        }
        struct Resp: Decodable {
            let ok: Bool?
            let data: DataBody
        }

        let resp: Resp = try await post("/assistant/chat", body: Body(
            text: text,
            session_id: sessionId,
            source: "macos",
            context: context,
            attachments: attachments
        ))

        return AssistantReply(
            text: resp.data.text,
            sessionId: resp.data.sessionId,
            memoryId: resp.data.memoryId,
            images: resp.data.images ?? []
        )
    }

    private struct EmptyResp: Decodable { let ok: Bool? }
    private struct EmptyBody: Encodable {}

    private func get<T: Decodable>(_ path: String) async throws -> T {
        try await request(path, method: "GET", body: nil as EmptyBody?)
    }

    private func postEmpty<T: Decodable>(_ path: String) async throws -> T {
        try await request(path, method: "POST", body: EmptyBody())
    }

    private func post<T: Decodable, B: Encodable>(_ path: String, body: B, auth: Bool = true) async throws -> T {
        try await request(path, method: "POST", body: body, auth: auth)
    }

    private func put<T: Decodable, B: Encodable>(_ path: String, body: B) async throws -> T {
        try await request(path, method: "PUT", body: body)
    }

    private func patch<T: Decodable, B: Encodable>(_ path: String, body: B) async throws -> T {
        try await request(path, method: "PATCH", body: body)
    }

    private func request<T: Decodable, B: Encodable>(_ path: String, method: String, body: B?, auth: Bool = true) async throws -> T {
        guard let url = URL(string: baseURL + path) else { throw APIError.server("Неверный URL API") }
        var req = URLRequest(url: url)
        req.httpMethod = method
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        req.setValue("application/json", forHTTPHeaderField: "Accept")
        if auth, !token.isEmpty { req.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
        if let body { req.httpBody = try JSONEncoder().encode(body) }

        let (data, response) = try await URLSession.shared.data(for: req)
        guard let http = response as? HTTPURLResponse else {
            AppLog.error("API \(method) \(path): нет HTTP-ответа")
            throw APIError.server("Нет ответа")
        }
        if http.statusCode == 401 {
            AppLog.warn("API \(method) \(path): 401 Unauthorized")
            throw APIError.unauthorized
        }
        if !(200...299).contains(http.statusCode) {
            let bodyText = String(data: data.prefix(500), encoding: .utf8) ?? ""
            AppLog.error("API \(method) \(path): HTTP \(http.statusCode) \(bodyText)")
            if let err = try? JSONDecoder().decode([String: String].self, from: data), let msg = err["message"] {
                throw APIError.server(msg)
            }
            throw APIError.server("Ошибка сервера (\(http.statusCode))")
        }
        do {
            return try JSONDecoder().decode(T.self, from: data)
        } catch {
            AppLog.error("API \(method) \(path): ошибка декодирования ответа — \(error)")
            throw APIError.decode
        }
    }
}

struct PressCardInput {
    var userId: Int
    var fullName: String
    var position: String
    var organization: String
    var issuedAt: String
    var expiresAt: String
    var notes: String
}

struct StaffUserInput {
    var name: String
    var email: String
    var login: String
    var role: String
    var position: String
    var password: String
    var active: Bool
}

struct AssistantReply: Codable, Equatable {
    let text: String
    let sessionId: String
    let memoryId: Int?
    var images: [String] = []
}

/// Вложение для нейросотрудника: текст документа или изображение (base64)
struct AssistantAttachment: Codable, Equatable {
    let type: String      // "image" | "text"
    let name: String
    let content: String   // base64 для image, текст для text
    let mime: String?
}

struct AssistantMessage: Identifiable, Equatable {
    enum Role {
        case user
        case assistant
    }

    let id = UUID()
    let role: Role
    let text: String
    var attachmentNames: [String] = []
    var images: [String] = []
}

struct JournalistItem: Codable, Identifiable, Equatable {
    let id: Int
    let name: String
    let email: String
    let login: String?
    let slug: String?
    let position: String?
    let role: String?
    let roleLabel: String?
    let activePressCardNumber: String?

    enum CodingKeys: String, CodingKey {
        case id, name, email, login, slug, position, role
        case roleLabel = "role_label"
        case activePressCardNumber = "active_press_card_number"
    }
}
