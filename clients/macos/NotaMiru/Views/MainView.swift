import SwiftUI

struct MainView: View {
    @EnvironmentObject private var appState: AppState

    private var sections: [AppSection] {
        AppSection.allCases.filter { section in
            switch section {
            case .pressCards, .documents, .users: return appState.canManageStaff
            default: return true
            }
        }
    }

    var body: some View {
        NavigationSplitView {
            List(selection: $appState.selectedSection) {
                Section {
                    Image("NotaMiruLogo")
                        .resizable()
                        .scaledToFit()
                        .frame(height: 44)
                        .padding(.vertical, 6)

                    if let user = appState.user {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(user.name)
                                .font(.headline)
                                .foregroundStyle(AppTheme.textPrimary)
                            Text(user.roleLabel ?? user.role ?? "Сотрудник")
                                .font(.caption)
                                .foregroundStyle(AppTheme.textSecondary)
                        }
                        .padding(.vertical, 4)
                    }
                }
                Section("Разделы") {
                    ForEach(sections) { section in
                        Label(section.rawValue, systemImage: section.icon)
                            .tag(section)
                    }
                }
            }
            .listStyle(.sidebar)
            .scrollContentBackground(.hidden)
            .background(AppTheme.sidebarBackground)
            .foregroundStyle(AppTheme.textPrimary)
            .navigationSplitViewColumnWidth(min: 200, ideal: 220)
            .toolbar {
                ToolbarItem(placement: .automatic) {
                    Button("Выйти") {
                        Task { await appState.logout() }
                    }
                }
            }
        } detail: {
            switch appState.selectedSection {
            case .articles:
                ArticlesView()
            case .assistant:
                AssistantView()
            case .pressCards:
                PressCardsView()
            case .documents:
                DocumentSigningView()
            case .users:
                UsersView()
            case .settings:
                SettingsView()
            }
        }
    }
}
