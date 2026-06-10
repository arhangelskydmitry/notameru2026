import Foundation

enum SettingsStore {
    private static let key = "notaMiruSettings"

    static func load() -> AppSettings {
        guard let data = UserDefaults.standard.data(forKey: key),
              let settings = try? JSONDecoder().decode(AppSettings.self, from: data) else {
            var defaults = AppSettings()
            defaults.authToken = KeychainStore.loadToken() ?? ""
            return defaults
        }
        var s = settings
        if s.authToken.isEmpty { s.authToken = KeychainStore.loadToken() ?? "" }
        return s
    }

    static func save(_ settings: AppSettings) {
        KeychainStore.saveToken(settings.authToken)
        var copy = settings
        copy.authToken = ""
        if let data = try? JSONEncoder().encode(copy) {
            UserDefaults.standard.set(data, forKey: key)
        }
    }

    static func clearAuth() {
        KeychainStore.deleteToken()
        var s = load()
        s.authToken = ""
        save(s)
    }
}
