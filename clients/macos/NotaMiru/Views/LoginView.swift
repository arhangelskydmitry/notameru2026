import SwiftUI

struct LoginView: View {
    @EnvironmentObject private var appState: AppState
    @State private var password = ""

    var body: some View {
        VStack(spacing: 26) {
            VStack(spacing: 12) {
                Image("NotaMiruLogo")
                    .resizable()
                    .scaledToFit()
                    .frame(width: 360, height: 88)
                Text(AppInfo.name)
                    .font(.largeTitle.bold())
                    .foregroundStyle(AppTheme.brand)
                Text("Редакционный клиент сетевого издания")
                    .foregroundStyle(AppTheme.textSecondary)
            }

            VStack(alignment: .leading, spacing: 12) {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Вход выполняется под суперадминистратором")
                        .font(.caption)
                        .foregroundStyle(AppTheme.textSecondary)
                    Text(AppInfo.fixedAdminEmail)
                        .font(.headline)
                        .foregroundStyle(AppTheme.textPrimary)
                        .textSelection(.enabled)
                }
                .padding(10)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(AppTheme.brandSoft)
                .clipShape(RoundedRectangle(cornerRadius: 8))
                .overlay(RoundedRectangle(cornerRadius: 8).stroke(AppTheme.cardStroke))
                SecureField("Пароль", text: $password)
                    .lightFieldSurface()
                    .textContentType(.password)
                TextField("URL API", text: $appState.settings.apiBaseURL)
                    .font(.caption)
                    .lightFieldSurface()
            }
            .padding(20)
            .background(AppTheme.panelBackground)
            .foregroundStyle(AppTheme.textPrimary)
            .clipShape(RoundedRectangle(cornerRadius: 18))
            .overlay(
                RoundedRectangle(cornerRadius: 18)
                    .stroke(AppTheme.cardStroke, lineWidth: 1)
            )
            .shadow(color: Color.black.opacity(0.12), radius: 18, x: 0, y: 8)
            .frame(maxWidth: 360)

            Button {
                Task {
                    await appState.login(email: AppInfo.fixedAdminEmail, password: password)
                    password = ""
                }
            } label: {
                if appState.isLoading {
                    ProgressView().controlSize(.small)
                } else {
                    Text("Войти")
                        .frame(maxWidth: 200)
                }
            }
            .buttonStyle(.borderedProminent)
            .tint(AppTheme.brand)
            .disabled(appState.isLoading || password.isEmpty)

            Button {
                appState.enterOfflineMode()
            } label: {
                Label("Работать офлайн с пресс-картой", systemImage: "person.text.rectangle")
                    .frame(maxWidth: 260)
            }
            .buttonStyle(.bordered)
            .foregroundStyle(AppTheme.brand)

            Text("v\(AppInfo.version) · \(AppInfo.defaultAPIBase)")
                .font(.caption)
                .foregroundStyle(AppTheme.textMuted)
        }
        .padding(40)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(
            LinearGradient(
                colors: [Color.white, AppTheme.pageBackground, AppTheme.brandSoft],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
        )
    }
}
