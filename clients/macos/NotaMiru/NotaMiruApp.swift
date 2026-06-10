import SwiftUI

@main
struct NotaMiruApp: App {
    @StateObject private var appState = AppState()

    init() {
        let version = Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "?"
        AppLog.info("Запуск NotaMiru v\(version), macOS \(ProcessInfo.processInfo.operatingSystemVersionString)")
        AppLog.info("Файл лога: \(AppLog.logFilePath)")
        // Падения по NSException попадут в лог до завершения процесса
        NSSetUncaughtExceptionHandler { exception in
            AppLog.error("КРАШ (NSException): \(exception.name.rawValue) — \(exception.reason ?? "без описания")\n\(exception.callStackSymbols.joined(separator: "\n"))")
        }
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(appState)
                .frame(minWidth: 960, minHeight: 640)
        }
        .windowStyle(.titleBar)
        .commands {
            CommandGroup(replacing: .newItem) {
                Button("Новая статья") {
                    NotificationCenter.default.post(name: .newArticle, object: nil)
                }
                .keyboardShortcut("n", modifiers: .command)
            }
        }
    }
}

extension Notification.Name {
    static let newArticle = Notification.Name("NotaMiru.newArticle")
}
