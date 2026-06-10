import SwiftUI

struct SettingsView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        Form {
            Section("Подключение") {
                TextField("Базовый URL API", text: $appState.settings.apiBaseURL)
                    .lightFieldSurface()
                TextField("Имя устройства", text: $appState.settings.deviceName)
                    .lightFieldSurface()
                Button("Сохранить настройки") {
                    appState.saveSettings()
                }
            }
            Section("Учётная запись") {
                if let user = appState.user {
                    LabeledContent("Имя", value: user.name)
                    LabeledContent("Email", value: user.email)
                    LabeledContent("Роль", value: user.roleLabel ?? "—")
                }
                Button("Выйти", role: .destructive) {
                    Task { await appState.logout() }
                }
            }
            Section("О приложении") {
                LabeledContent("Версия", value: "\(AppInfo.version) (\(AppInfo.build))")
                LabeledContent("Организация", value: AppInfo.organization)
                LabeledContent("Сайт", value: "https://notame.ru")
            }
        }
        .formStyle(.grouped)
        .padding()
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
        .navigationTitle("Настройки")
    }
}
