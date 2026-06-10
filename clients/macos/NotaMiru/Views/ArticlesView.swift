import SwiftUI

struct ArticlesView: View {
    @EnvironmentObject private var appState: AppState
    @State private var statusFilter = ""
    @State private var searchText = ""
    @State private var showNewArticle = false

    var body: some View {
        NavigationSplitView {
            VStack(spacing: 0) {
                HStack {
                    Picker("Статус", selection: $statusFilter) {
                        ForEach(PostStatus.all, id: \.id) { item in
                            Text(item.label).tag(item.id)
                        }
                    }
                    .pickerStyle(.menu)
                    .frame(width: 180)
                    TextField("Поиск", text: $searchText)
                        .lightFieldSurface()
                        .onSubmit { reload() }
                    Button("Обновить", action: reload)
                }
                .padding()
                .background(AppTheme.panelBackground)
                .foregroundStyle(AppTheme.textPrimary)

                List(selection: $appState.selectedArticleId) {
                    if appState.isLoading && appState.articles.isEmpty {
                        ProgressView()
                    }
                    ForEach(appState.articles) { article in
                        VStack(alignment: .leading, spacing: 4) {
                            Text(article.title)
                                .font(.headline)
                                .foregroundStyle(AppTheme.textPrimary)
                                .lineLimit(2)
                            HStack {
                                StatusBadge(status: article.status)
                                Text(article.modified.prefix(10))
                                    .font(.caption)
                                    .foregroundStyle(AppTheme.textSecondary)
                            }
                        }
                        .tag(article.id)
                    }
                }
                .scrollContentBackground(.hidden)
                .background(AppTheme.panelBackground)
            }
            .background(AppTheme.panelBackground)
            .navigationTitle("Статьи")
            .toolbar {
                ToolbarItem {
                    Button {
                        showNewArticle = true
                    } label: {
                        Label("Новая", systemImage: "plus")
                    }
                }
            }
        } detail: {
            if let id = appState.selectedArticleId {
                ArticleEditorView(articleId: id)
            } else {
                VStack(spacing: 8) {
                    Image(systemName: "doc.text")
                        .font(.largeTitle)
                        .foregroundStyle(AppTheme.textMuted)
                    Text("Выберите статью")
                        .font(.title3)
                        .foregroundStyle(AppTheme.textPrimary)
                    Text("Или создайте новую")
                        .foregroundStyle(AppTheme.textSecondary)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .background(AppTheme.pageBackground)
            }
        }
        .task { reload() }
        .onChange(of: statusFilter) { _ in reload() }
        .onReceive(NotificationCenter.default.publisher(for: .newArticle)) { _ in
            showNewArticle = true
        }
        .sheet(isPresented: $showNewArticle) {
            NewArticleSheet(isPresented: $showNewArticle) { id in
                appState.selectedArticleId = id
                reload()
            }
        }
    }

    private func reload() {
        Task {
            let status = statusFilter.isEmpty ? nil : statusFilter
            let q = searchText.isEmpty ? nil : searchText
            await appState.reloadArticles(status: status, query: q)
        }
    }
}

struct StatusBadge: View {
    let status: String

    var body: some View {
        Text(PostStatus.label(for: status))
            .font(.caption2.weight(.medium))
            .padding(.horizontal, 8)
            .padding(.vertical, 2)
            .background(color.opacity(0.18))
            .foregroundStyle(color)
            .clipShape(Capsule())
    }

    private var color: Color {
        switch status {
        case "publish": return AppTheme.success
        case "draft": return AppTheme.warning
        case "pending": return AppTheme.info
        default: return AppTheme.textSecondary
        }
    }
}

struct NewArticleSheet: View {
    @EnvironmentObject private var appState: AppState
    @Binding var isPresented: Bool
    var onCreated: (Int) -> Void

    @State private var title = ""
    @State private var content = ""
    @State private var status = "draft"
    @State private var isSaving = false

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Новая статья")
                .font(.title2.bold())
                .foregroundStyle(AppTheme.textPrimary)
            TextField("Заголовок", text: $title)
                .lightFieldSurface()
            TextEditor(text: $content)
                .font(.body.monospaced())
                .frame(minHeight: 200)
                .lightEditorSurface(cornerRadius: 6)
            Picker("Статус", selection: $status) {
                Text("Черновик").tag("draft")
                if appState.canManageStaff {
                    Text("Опубликовать").tag("publish")
                    Text("На модерации").tag("pending")
                }
            }
            HStack {
                Spacer()
                Button("Отмена") { isPresented = false }
                Button("Создать") { create() }
                    .buttonStyle(.borderedProminent)
                    .tint(AppTheme.brand)
                    .disabled(title.isEmpty || content.isEmpty || isSaving)
            }
        }
        .padding(24)
        .frame(width: 560, height: 420)
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
    }

    private func create() {
        isSaving = true
        Task {
            defer { isSaving = false }
            do {
                let article = try await appState.createArticle(title: title, content: content, status: status)
                isPresented = false
                onCreated(article.id)
            } catch {
                appState.errorMessage = error.localizedDescription
            }
        }
    }
}
