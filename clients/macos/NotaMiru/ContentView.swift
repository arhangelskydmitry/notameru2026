import SwiftUI

struct ContentView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        Group {
            if appState.isBootstrapping {
                ProgressView("Nota Miru…")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if appState.isAuthenticated {
                MainView()
            } else {
                LoginView()
            }
        }
        .task { await appState.bootstrap() }
        .alert("Ошибка", isPresented: Binding(
            get: { appState.errorMessage != nil },
            set: { if !$0 { appState.errorMessage = nil } }
        )) {
            Button("OK") { appState.errorMessage = nil }
        } message: {
            Text(appState.errorMessage ?? "")
        }
    }
}
