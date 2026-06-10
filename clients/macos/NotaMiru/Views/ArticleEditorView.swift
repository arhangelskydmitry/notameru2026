import SwiftUI

struct ArticleEditorView: View {
    @EnvironmentObject private var appState: AppState
    let articleId: Int

    @State private var detail: ArticleDetail?
    @State private var title = ""
    @State private var content = ""
    @State private var excerpt = ""
    @State private var status = "draft"
    @State private var seo = ArticleSEO()
    @State private var summary: SummarizeResult?
    @State private var isLoading = true
    @State private var isSaving = false
    @State private var isGeneratingSEO = false
    @State private var isSummarizing = false
    @State private var selectedTab = 0

    var body: some View {
        Group {
            if isLoading {
                ProgressView("Загрузка…")
            } else {
                HSplitView {
                    editorPane
                    sidePane
                }
            }
        }
        .navigationTitle(title.isEmpty ? "Редактор" : title)
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
        .toolbar {
            ToolbarItemGroup {
                if let url = detail?.url, !url.isEmpty {
                    Link("На сайте", destination: URL(string: url)!)
                }
                Button("Сохранить") { save() }
                    .disabled(isSaving || title.isEmpty)
            }
        }
        .task(id: articleId) { await load() }
    }

    private var editorPane: some View {
        VStack(alignment: .leading, spacing: 12) {
            TextField("Заголовок", text: $title)
                .font(.title2.bold())
                .lightFieldSurface()
            HStack {
                Picker("Статус", selection: $status) {
                    Text("Черновик").tag("draft")
                    if appState.canManageStaff || status == "publish" {
                        Text("Опубликовано").tag("publish")
                    }
                    Text("На модерации").tag("pending")
                    Text("Запланировано").tag("future")
                }
                .pickerStyle(.segmented)
                .frame(maxWidth: 420)
            }
            TextEditor(text: $content)
                .font(.body.monospaced())
                .lightEditorSurface(cornerRadius: 8)
        }
        .padding()
        .frame(minWidth: 380)
        .background(AppTheme.pageBackground)
    }

    private var sidePane: some View {
        VStack(alignment: .leading, spacing: 0) {
            Picker("", selection: $selectedTab) {
                Text("SEO").tag(0)
                Text("Выжимка").tag(1)
                Text("Анонс").tag(2)
            }
            .pickerStyle(.segmented)
            .padding()

            ScrollView {
                switch selectedTab {
                case 0: seoPanel
                case 1: summaryPanel
                default: excerptPanel
                }
            }
        }
        .frame(minWidth: 300, idealWidth: 340)
        .background(AppTheme.panelBackground)
        .foregroundStyle(AppTheme.textPrimary)
    }

    private var seoPanel: some View {
        VStack(alignment: .leading, spacing: 12) {
            Button {
                generateSEO()
            } label: {
                Label(isGeneratingSEO ? "Генерация…" : "Сгенерировать SEO", systemImage: "sparkles")
            }
            .disabled(isGeneratingSEO || title.isEmpty)
            LabeledContent("SEO Title") {
                TextField("", text: binding(\.seoTitle))
                    .lightFieldSurface()
            }
            LabeledContent("Description") {
                TextField("", text: binding(\.seoDescription), axis: .vertical)
                    .lineLimit(3...6)
                    .lightFieldSurface()
            }
            LabeledContent("Ключевое слово") {
                TextField("", text: binding(\.focusKeyword))
                    .lightFieldSurface()
            }
            LabeledContent("OG Title") {
                TextField("", text: binding(\.ogTitle))
                    .lightFieldSurface()
            }
            LabeledContent("OG Description") {
                TextField("", text: binding(\.ogDescription), axis: .vertical)
                    .lineLimit(2...4)
                    .lightFieldSurface()
            }
        }
        .padding()
        .foregroundStyle(AppTheme.textPrimary)
    }

    private var summaryPanel: some View {
        VStack(alignment: .leading, spacing: 12) {
            Button {
                summarize()
            } label: {
                Label(isSummarizing ? "Анализ…" : "Сделать выжимку", systemImage: "text.quote")
            }
            .disabled(isSummarizing || content.isEmpty)
            if let summary {
                Text(summary.summary)
                    .font(.body)
                    .foregroundStyle(AppTheme.textPrimary)
                if !summary.bullets.isEmpty {
                    Divider()
                    ForEach(summary.bullets, id: \.self) { bullet in
                        Label(bullet, systemImage: "circle.fill")
                            .labelStyle(.titleAndIcon)
                            .font(.caption)
                            .symbolRenderingMode(.hierarchical)
                    }
                }
                Text("~\(summary.readingTimeMinutes) мин чтения")
                    .font(.caption)
                    .foregroundStyle(AppTheme.textSecondary)
            } else {
                Text("Нажмите «Сделать выжимку» для краткого содержания статьи.")
                    .foregroundStyle(AppTheme.textSecondary)
            }
        }
        .padding()
    }

    private var excerptPanel: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Краткий анонс для ленты")
                .font(.caption)
                .foregroundStyle(AppTheme.textSecondary)
            TextEditor(text: $excerpt)
                .frame(minHeight: 120)
                .lightEditorSurface(cornerRadius: 6)
        }
        .padding()
        .foregroundStyle(AppTheme.textPrimary)
    }

    private func binding(_ keyPath: WritableKeyPath<ArticleSEO, String?>) -> Binding<String> {
        Binding(
            get: { seo[keyPath: keyPath] ?? "" },
            set: { seo[keyPath: keyPath] = $0.isEmpty ? nil : $0 }
        )
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let d = try await appState.fetchArticle(id: articleId)
            detail = d
            title = d.title
            content = d.content
            excerpt = d.excerpt
            status = d.status
            seo = d.seo ?? ArticleSEO()
        } catch {
            appState.errorMessage = error.localizedDescription
        }
    }

    private func save() {
        isSaving = true
        Task {
            defer { isSaving = false }
            do {
                let updated = try await appState.updateArticle(
                    id: articleId,
                    title: title,
                    content: content,
                    excerpt: excerpt,
                    status: status,
                    seo: seoHasValues ? seo : nil
                )
                detail = updated
                await appState.reloadArticles()
            } catch {
                appState.errorMessage = error.localizedDescription
            }
        }
    }

    private var seoHasValues: Bool {
        !(seo.seoTitle ?? "").isEmpty || !(seo.seoDescription ?? "").isEmpty
    }

    private func generateSEO() {
        isGeneratingSEO = true
        Task {
            defer { isGeneratingSEO = false }
            do {
                seo = try await appState.generateSEO(title: title, content: content)
            } catch {
                appState.errorMessage = error.localizedDescription
            }
        }
    }

    private func summarize() {
        isSummarizing = true
        Task {
            defer { isSummarizing = false }
            do {
                summary = try await appState.summarize(title: title, content: content)
            } catch {
                appState.errorMessage = error.localizedDescription
            }
        }
    }
}
