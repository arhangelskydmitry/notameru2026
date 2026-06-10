import SwiftUI
import AppKit
import PDFKit
import UniformTypeIdentifiers

struct AssistantView: View {
    @EnvironmentObject private var appState: AppState
    @State private var text = ""
    @State private var pendingAttachments: [AssistantAttachment] = []

    var body: some View {
        VStack(spacing: 0) {
            header

            ScrollViewReader { proxy in
                ScrollView {
                    LazyVStack(alignment: .leading, spacing: 14) {
                        if appState.assistantMessages.isEmpty {
                            emptyState
                        } else {
                            ForEach(appState.assistantMessages) { message in
                                AssistantBubble(message: message)
                                    .id(message.id)
                            }
                        }
                    }
                    .padding(24)
                }
                .background(AppTheme.pageBackground)
                .onChange(of: appState.assistantMessages.count) { _ in
                    if let last = appState.assistantMessages.last {
                        withAnimation { proxy.scrollTo(last.id, anchor: .bottom) }
                    }
                }
            }

            composer
        }
        .background(AppTheme.pageBackground)
    }

    private var header: some View {
        HStack(spacing: 14) {
            Image(systemName: "sparkles")
                .font(.system(size: 26, weight: .semibold))
                .foregroundStyle(AppTheme.accent)
                .frame(width: 48, height: 48)
                .background(AppTheme.cardBackground)
                .clipShape(RoundedRectangle(cornerRadius: 16))

            VStack(alignment: .leading, spacing: 4) {
                Text("Нейросотрудник Нота Миру")
                    .font(.title2.bold())
                    .foregroundStyle(AppTheme.textPrimary)
                Text("Релизы, фото, SEO и публикации. Принимает DOCX, PDF, изображения и ссылки на фото.")
                    .foregroundStyle(AppTheme.textSecondary)
            }

            Spacer()
        }
        .padding(24)
        .background(AppTheme.sidebarBackground)
    }

    private var emptyState: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Чем могу помочь?")
                .font(.title3.bold())
                .foregroundStyle(AppTheme.textPrimary)
            Text("Прикрепите пресс-релиз (DOCX/PDF) или фото скрепкой, вставьте ссылку на изображение — я разберу материал. Могу сгенерировать иллюстрацию по описанию или референсу.")
                .foregroundStyle(AppTheme.textSecondary)
            if appState.user?.isSuperAdmin == true {
                Text("Режим разработчика: начните сообщение с «разработка: …» — задача уйдёт Cursor-агенту (Fable 5).")
                    .font(.callout)
                    .foregroundStyle(AppTheme.info)
            }
        }
        .padding(22)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(AppTheme.cardBackground)
        .clipShape(RoundedRectangle(cornerRadius: 18))
    }

    // MARK: composer

    private var composer: some View {
        VStack(spacing: 8) {
            if !pendingAttachments.isEmpty {
                attachmentChips
            }

            HStack(alignment: .bottom, spacing: 12) {
                Button {
                    attachFile()
                } label: {
                    Image(systemName: "paperclip")
                        .font(.system(size: 17, weight: .semibold))
                        .foregroundStyle(AppTheme.brand)
                        .frame(width: 40, height: 40)
                        .background(.white)
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                        .overlay(RoundedRectangle(cornerRadius: 12).stroke(AppTheme.brand.opacity(0.4)))
                }
                .buttonStyle(.plain)
                .help("Прикрепить DOCX, PDF или фото")

                TextEditor(text: $text)
                    .font(.body)
                    .foregroundStyle(Color.black)
                    .scrollContentBackground(.hidden)
                    .frame(minHeight: 52, maxHeight: 120)
                    .padding(10)
                    .background(Color.white)
                    .clipShape(RoundedRectangle(cornerRadius: 14))
                    .overlay(
                        RoundedRectangle(cornerRadius: 14)
                            .stroke(AppTheme.brand.opacity(0.45), lineWidth: 1.5)
                    )

                Button {
                    send()
                } label: {
                    Label(appState.isLoading ? "Думаю..." : "Отправить", systemImage: "paperplane.fill")
                }
                .buttonStyle(.borderedProminent)
                .controlSize(.large)
                .disabled(appState.isLoading ||
                          (text.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty && pendingAttachments.isEmpty))
            }
        }
        .padding(18)
        .background(AppTheme.sidebarBackground)
    }

    private var attachmentChips: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                ForEach(Array(pendingAttachments.enumerated()), id: \.offset) { index, attachment in
                    HStack(spacing: 6) {
                        Image(systemName: attachment.type == "image" ? "photo" : "doc.text")
                        Text(attachment.name)
                            .lineLimit(1)
                            .frame(maxWidth: 180)
                        Button {
                            pendingAttachments.remove(at: index)
                        } label: {
                            Image(systemName: "xmark.circle.fill")
                        }
                        .buttonStyle(.plain)
                    }
                    .font(.callout)
                    .foregroundStyle(AppTheme.textPrimary)
                    .padding(.horizontal, 10)
                    .padding(.vertical, 6)
                    .background(.white)
                    .clipShape(Capsule())
                    .overlay(Capsule().stroke(AppTheme.cardStroke))
                }
            }
        }
    }

    // MARK: actions

    private func attachFile() {
        let panel = NSOpenPanel()
        var types: [UTType] = [.pdf, .png, .jpeg, .tiff, .heic]
        if let docx = UTType(filenameExtension: "docx") { types.append(docx) }
        if let rtf = UTType(filenameExtension: "rtf") { types.append(rtf) }
        panel.allowedContentTypes = types
        panel.allowsMultipleSelection = true
        panel.message = "Прикрепите релиз (DOCX/PDF) или фотографии"
        guard panel.runModal() == .OK else { return }

        for url in panel.urls.prefix(6 - pendingAttachments.count) {
            if let attachment = Self.makeAttachment(from: url) {
                pendingAttachments.append(attachment)
                AppLog.info("Ассистент: прикреплён файл \(attachment.name) (\(attachment.type))")
            } else {
                AppLog.warn("Ассистент: не удалось обработать вложение \(url.lastPathComponent)")
            }
        }
    }

    /// DOCX/PDF/RTF → текст; изображения → JPEG base64 (с уменьшением до 1600 px)
    static func makeAttachment(from url: URL) -> AssistantAttachment? {
        let ext = url.pathExtension.lowercased()
        let name = url.lastPathComponent

        if ["png", "jpg", "jpeg", "tiff", "heic"].contains(ext) {
            guard let image = NSImage(contentsOf: url),
                  let jpeg = downscaledJPEG(image, maxSide: 1600) else { return nil }
            return AssistantAttachment(type: "image", name: name,
                                       content: jpeg.base64EncodedString(), mime: "image/jpeg")
        }

        if ext == "pdf" {
            guard let pdf = PDFDocument(url: url) else { return nil }
            var pages: [String] = []
            for i in 0..<min(pdf.pageCount, 40) {
                if let pageText = pdf.page(at: i)?.string { pages.append(pageText) }
            }
            let joined = pages.joined(separator: "\n")
            guard !joined.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else { return nil }
            return AssistantAttachment(type: "text", name: name, content: joined, mime: nil)
        }

        let docType: NSAttributedString.DocumentType = ext == "rtf" ? .rtf : .officeOpenXML
        guard let attributed = try? NSAttributedString(
            url: url,
            options: [.documentType: docType],
            documentAttributes: nil
        ) else { return nil }
        return AssistantAttachment(type: "text", name: name, content: attributed.string, mime: nil)
    }

    private static func downscaledJPEG(_ image: NSImage, maxSide: CGFloat) -> Data? {
        guard let tiff = image.tiffRepresentation,
              let rep = NSBitmapImageRep(data: tiff) else { return nil }
        let width = CGFloat(rep.pixelsWide)
        let height = CGFloat(rep.pixelsHigh)
        let scale = min(1, maxSide / max(width, height))

        if scale >= 1 {
            return rep.representation(using: .jpeg, properties: [.compressionFactor: 0.82])
        }

        let newSize = NSSize(width: width * scale, height: height * scale)
        let resized = NSImage(size: newSize)
        resized.lockFocus()
        image.draw(in: NSRect(origin: .zero, size: newSize))
        resized.unlockFocus()
        guard let resizedTiff = resized.tiffRepresentation,
              let resizedRep = NSBitmapImageRep(data: resizedTiff) else { return nil }
        return resizedRep.representation(using: .jpeg, properties: [.compressionFactor: 0.82])
    }

    private func send() {
        let message = text
        let attachments = pendingAttachments
        text = ""
        pendingAttachments = []
        Task { await appState.sendAssistantMessage(message, attachments: attachments) }
    }
}

private struct AssistantBubble: View {
    let message: AssistantMessage

    private var isUser: Bool { message.role == .user }

    var body: some View {
        HStack {
            if isUser { Spacer(minLength: 80) }

            VStack(alignment: .leading, spacing: 6) {
                Text(isUser ? "Вы" : "Нейросотрудник")
                    .font(.caption.bold())
                    .foregroundStyle(isUser ? .white.opacity(0.8) : AppTheme.textSecondary)

                Text(message.text)
                    .textSelection(.enabled)
                    .foregroundStyle(isUser ? .white : AppTheme.textPrimary)

                if !message.attachmentNames.isEmpty {
                    ForEach(message.attachmentNames, id: \.self) { name in
                        Label(name, systemImage: "paperclip")
                            .font(.caption)
                            .foregroundStyle(isUser ? .white.opacity(0.85) : AppTheme.textMuted)
                    }
                }

                ForEach(message.images, id: \.self) { urlString in
                    if let url = URL(string: urlString) {
                        VStack(alignment: .leading, spacing: 4) {
                            AsyncImage(url: url) { phase in
                                switch phase {
                                case .success(let image):
                                    image.resizable().scaledToFit()
                                case .failure:
                                    Label("Не удалось загрузить изображение", systemImage: "photo")
                                        .font(.caption)
                                        .foregroundStyle(AppTheme.textMuted)
                                default:
                                    ProgressView()
                                        .frame(height: 80)
                                }
                            }
                            .frame(maxWidth: 420)
                            .clipShape(RoundedRectangle(cornerRadius: 10))

                            Link("Открыть в браузере", destination: url)
                                .font(.caption)
                        }
                    }
                }
            }
            .padding(14)
            .background(isUser ? AppTheme.accent : AppTheme.cardBackground)
            .clipShape(RoundedRectangle(cornerRadius: 16))

            if !isUser { Spacer(minLength: 80) }
        }
    }
}
