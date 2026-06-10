import Foundation
import SwiftUI

enum AppSection: String, CaseIterable, Identifiable {
    case articles = "Статьи"
    case assistant = "Нейросотрудник"
    case pressCards = "Пресс-карты"
    case documents = "Документы"
    case users = "Пользователи"
    case settings = "Настройки"

    var id: String { rawValue }

    var icon: String {
        switch self {
        case .articles: return "doc.text"
        case .assistant: return "sparkles"
        case .pressCards: return "person.crop.rectangle"
        case .documents: return "signature"
        case .users: return "person.2"
        case .settings: return "gearshape"
        }
    }
}

@MainActor
final class AppState: ObservableObject {
    @Published var settings: AppSettings
    @Published var user: AuthUser?
    @Published var isAuthenticated = false
    @Published var isBootstrapping = true
    @Published var errorMessage: String?
    @Published var selectedSection: AppSection = .articles
    @Published var selectedArticleId: Int?
    @Published var articles: [ArticleItem] = []
    @Published var pressCards: [PressCardItem] = []
    @Published var staffUsers: [StaffUser] = []
    @Published var assistantMessages: [AssistantMessage] = []
    @Published var assistantSessionId: String?
    @Published var isLoading = false
    @Published var isOfflineMode = false

    private var api: APIClient

    init() {
        let loaded = SettingsStore.load()
        settings = loaded
        api = APIClient(baseURL: loaded.apiBaseURL)
        api.token = loaded.authToken
    }

    var canManageStaff: Bool {
        isOfflineMode || user?.isEditor == true || user?.isSuperAdmin == true
    }

    private func isFixedSuperAdmin(_ user: AuthUser) -> Bool {
        user.email.caseInsensitiveCompare(AppInfo.fixedAdminEmail) == .orderedSame && user.isSuperAdmin
    }

    func bootstrap() async {
        isBootstrapping = true
        defer { isBootstrapping = false }

        api.baseURL = settings.apiBaseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        api.token = settings.authToken

        guard !settings.authToken.isEmpty else {
            isAuthenticated = false
            isOfflineMode = false
            selectedSection = .pressCards
            return
        }

        do {
            let currentUser = try await api.me()
            guard isFixedSuperAdmin(currentUser) else {
                logoutLocally()
                errorMessage = "Приложение работает только под \(AppInfo.fixedAdminEmail) (\(AppInfo.fixedAdminRoleLabel))."
                return
            }
            user = currentUser
            isAuthenticated = true
            isOfflineMode = false
            selectedSection = .pressCards
        } catch {
            logoutLocally()
            errorMessage = "Сессия истекла — войдите снова"
        }
    }

    func enterOfflineMode() {
        isOfflineMode = true
        user = AuthUser(
            id: 0,
            name: "Дмитрий Архангельский",
            email: AppInfo.fixedAdminEmail,
            position: AppInfo.fixedAdminRoleLabel,
            role: "super_admin",
            roleLabel: AppInfo.fixedAdminRoleLabel,
            isSuperAdmin: true,
            isEditor: true
        )
        isAuthenticated = true
        selectedSection = .pressCards
    }

    func login(email: String, password: String) async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        api.baseURL = settings.apiBaseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        do {
            let normalizedEmail = AppInfo.fixedAdminEmail
            let result = try await api.login(email: normalizedEmail, password: password, deviceName: settings.deviceName)
            guard isFixedSuperAdmin(result.user) else {
                api.token = ""
                settings.authToken = ""
                SettingsStore.clearAuth()
                errorMessage = "Приложение работает только под \(AppInfo.fixedAdminEmail) (\(AppInfo.fixedAdminRoleLabel))."
                return
            }
            settings.authToken = result.token
            api.token = result.token
            SettingsStore.save(settings)
            user = result.user
            isAuthenticated = true
            isOfflineMode = false
        } catch {
            logoutLocally()
            errorMessage = error.localizedDescription
        }
    }

    func logout() async {
        try? await api.logout()
        logoutLocally()
    }

    func logoutLocally() {
        SettingsStore.clearAuth()
        settings.authToken = ""
        api.token = ""
        isOfflineMode = false
        user = nil
        isAuthenticated = false
        articles = []
        pressCards = []
        staffUsers = []
        assistantMessages = []
        assistantSessionId = nil
        selectedArticleId = nil
    }

    func saveSettings() {
        api.baseURL = settings.apiBaseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        SettingsStore.save(settings)
    }

    func reloadArticles(status: String? = nil, query: String? = nil) async {
        isLoading = true
        defer { isLoading = false }
        do {
            articles = try await api.articles(status: status, query: query)
        } catch {
            handleError(error)
        }
    }

    func reloadPressCards() async {
        guard canManageStaff else { return }
        isLoading = true
        defer { isLoading = false }
        do {
            pressCards = try await api.pressCards()
        } catch {
            handleError(error)
        }
    }

    func reloadStaffUsers() async {
        guard canManageStaff else { return }
        isLoading = true
        defer { isLoading = false }
        do {
            staffUsers = try await api.staffUsers()
        } catch {
            handleError(error)
        }
    }

    func createArticle(title: String, content: String, status: String) async throws -> ArticleDetail {
        try await api.createArticle(title: title, content: content, status: status)
    }

    func updateArticle(id: Int, title: String, content: String, excerpt: String, status: String, seo: ArticleSEO?) async throws -> ArticleDetail {
        try await api.updateArticle(id: id, title: title, content: content, excerpt: excerpt, status: status, seo: seo)
    }

    func fetchArticle(id: Int) async throws -> ArticleDetail {
        try await api.article(id: id)
    }

    func generateSEO(title: String, content: String) async throws -> ArticleSEO {
        try await api.generateSEO(title: title, content: content)
    }

    func summarize(title: String, content: String) async throws -> SummarizeResult {
        try await api.summarize(title: title, content: content)
    }

    func createPressCard(_ input: PressCardInput) async throws -> PressCardItem {
        try await api.createPressCard(input)
    }

    func createStaffUser(_ input: StaffUserInput) async throws -> StaffUser {
        let user = try await api.createStaffUser(input)
        await reloadStaffUsers()
        return user
    }

    func journalists() async throws -> [JournalistItem] {
        if !api.token.isEmpty {
            do {
                let items = try await api.journalists()
                if !items.isEmpty {
                    isOfflineMode = false
                    return items
                }
            } catch {
                if !isOfflineMode {
                    throw error
                }
            }
        }

        return [
            JournalistItem(
                id: 1,
                name: user?.name ?? "Дмитрий Архангельский",
                email: user?.email ?? AppInfo.fixedAdminEmail,
                login: "d.arhangelsky",
                slug: nil,
                position: user?.position ?? AppInfo.fixedAdminRoleLabel,
                role: user?.role,
                roleLabel: user?.roleLabel,
                activePressCardNumber: nil
            )
        ]
    }

    func setUserActive(id: Int, active: Bool) async throws {
        try await api.setUserActive(id: id, active: active)
        await reloadStaffUsers()
    }

    func sendAssistantMessage(
        _ text: String,
        context: [String: String] = [:],
        attachments: [AssistantAttachment] = []
    ) async {
        let trimmed = text.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty || !attachments.isEmpty else { return }

        AppLog.info("Ассистент: отправка сообщения (\(trimmed.count) симв., вложений: \(attachments.count))")
        assistantMessages.append(AssistantMessage(
            role: .user,
            text: trimmed.isEmpty ? "(вложения)" : trimmed,
            attachmentNames: attachments.map(\.name)
        ))
        isLoading = true
        defer { isLoading = false }

        do {
            let reply = try await api.assistantChat(
                text: trimmed,
                sessionId: assistantSessionId,
                context: context,
                attachments: attachments
            )
            assistantSessionId = reply.sessionId
            assistantMessages.append(AssistantMessage(role: .assistant, text: reply.text, images: reply.images))
        } catch {
            AppLog.error("Ассистент: ошибка ответа — \(error.localizedDescription)")
            handleError(error)
            assistantMessages.append(AssistantMessage(role: .assistant, text: "Не удалось связаться с нейросотрудником: \(error.localizedDescription)"))
        }
    }

    private func handleError(_ error: Error) {
        if case APIError.unauthorized = error {
            logoutLocally()
        }
        errorMessage = error.localizedDescription
    }
}

enum PostStatus {
    static let all: [(id: String, label: String)] = [
        ("", "Все"),
        ("draft", "Черновики"),
        ("publish", "Опубликовано"),
        ("pending", "На модерации"),
        ("future", "Запланировано"),
    ]

    static func label(for status: String) -> String {
        all.first(where: { $0.id == status })?.label ?? status
    }
}
