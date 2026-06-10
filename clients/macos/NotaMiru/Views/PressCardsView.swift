import SwiftUI
import AppKit
import CoreImage
import UniformTypeIdentifiers

struct PressCardDraft: Codable, Equatable {
    let key: String
    let userId: Int
    let fullName: String
    let position: String
    let organization: String
    let cardNumber: String
    let issuedAt: String
    let expiresAt: String
    let notes: String
    let photoPath: String?
    let photoScale: Double
    let photoOffsetX: Double
    let photoOffsetY: Double
}

enum PressCardDraftStore {
    private static let key = "notaMiruPressCardDrafts"

    static func load(key draftKey: String) -> PressCardDraft? {
        loadAll()[draftKey]
    }

    static func save(_ draft: PressCardDraft) {
        var all = loadAll()
        all[draft.key] = draft
        if let data = try? JSONEncoder().encode(all) {
            UserDefaults.standard.set(data, forKey: key)
        }
    }

    static func savePhoto(from sourceURL: URL, key draftKey: String) -> String? {
        guard let dir = photosDirectory() else { return nil }
        let ext = sourceURL.pathExtension.isEmpty ? "jpg" : sourceURL.pathExtension
        let safeKey = draftKey.replacingOccurrences(of: "/", with: "-")
        let target = dir.appendingPathComponent("\(safeKey).\(ext)")
        do {
            if FileManager.default.fileExists(atPath: target.path) {
                try FileManager.default.removeItem(at: target)
            }
            try FileManager.default.copyItem(at: sourceURL, to: target)
            return target.path
        } catch {
            return nil
        }
    }

    private static func loadAll() -> [String: PressCardDraft] {
        guard let data = UserDefaults.standard.data(forKey: key),
              let drafts = try? JSONDecoder().decode([String: PressCardDraft].self, from: data) else {
            return [:]
        }
        return drafts
    }

    private static func photosDirectory() -> URL? {
        guard let appSupport = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first else {
            return nil
        }
        let dir = appSupport.appendingPathComponent("NotaMiru/PressCardPhotos", isDirectory: true)
        do {
            try FileManager.default.createDirectory(at: dir, withIntermediateDirectories: true)
            return dir
        } catch {
            return nil
        }
    }
}

enum JournalistCacheStore {
    private static let key = "notaMiruJournalistCache"

    static func load() -> [JournalistItem] {
        guard let data = UserDefaults.standard.data(forKey: key),
              let items = try? JSONDecoder().decode([JournalistItem].self, from: data) else {
            return [
                JournalistItem(
                    id: 1,
                    name: "Дмитрий Архангельский",
                    email: AppInfo.fixedAdminEmail,
                    login: "d.arhangelsky",
                    slug: nil,
                    position: AppInfo.fixedAdminRoleLabel,
                    role: "super_admin",
                    roleLabel: AppInfo.fixedAdminRoleLabel,
                    activePressCardNumber: nil
                )
            ]
        }
        return items
    }

    static func save(_ items: [JournalistItem]) {
        if let data = try? JSONEncoder().encode(items) {
            UserDefaults.standard.set(data, forKey: key)
        }
    }
}

struct PressCardsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var makerId = UUID()

    var body: some View {
        VStack(alignment: .leading, spacing: 0) {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Пресс-карты")
                        .font(.title2.bold())
                        .foregroundStyle(AppTheme.textPrimary)
                    Text("Учёт карт остаётся на сервере, изготовление макета выполняется внутри приложения.")
                        .font(.caption)
                        .foregroundStyle(AppTheme.textSecondary)
                }
                Spacer()
                Button {
                    makerId = UUID()
                } label: {
                    Label("Новый макет", systemImage: "plus")
                }
                .buttonStyle(.borderedProminent)
                .tint(AppTheme.brand)
                Button("Обновить") {
                    Task { await appState.reloadPressCards() }
                }
            }
            .padding()
            .background(AppTheme.panelBackground)

            LocalPressCardMakerSheet(isPresented: .constant(false), showsCancelButton: false)
                .id(makerId)
        }
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
    }
}

private enum PressCardPrintSide {
    case front
    case back
}

private enum PressCardPrintPaper {
    case a4
    case card
}

struct LocalPressCardMakerSheet: View {
    @EnvironmentObject private var appState: AppState
    @Binding var isPresented: Bool
    var showsCancelButton = true

    @State private var journalists: [JournalistItem] = []
    @State private var userId = 0
    @State private var fullName = ""
    @State private var position = ""
    @State private var organization = "Интернет-издание «Нота Миру»"
    @State private var cardNumber = Self.defaultCardNumber
    @State private var issuedAt = Self.todayString
    @State private var expiresAt = Self.yearLaterString
    @State private var notes = ""
    @State private var photo: NSImage?
    @State private var photoScale = 1.0
    @State private var photoOffsetX = 0.0
    @State private var photoOffsetY = 0.0
    @State private var statusMessage: String?
    @State private var previewSide = 0
    @State private var didLoadInitialDraft = false

    private static var todayString: String {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd"
        return f.string(from: Date())
    }

    private static var yearLaterString: String {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd"
        return f.string(from: Calendar.current.date(byAdding: .year, value: 1, to: Date()) ?? Date())
    }

    private static var defaultCardNumber: String {
        let f = DateFormatter()
        f.dateFormat = "yy"
        return "НР\(f.string(from: Date())) № 011"
    }

    var body: some View {
        HStack(spacing: 18) {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
                    Text("Локальное изготовление")
                        .font(.title2.bold())
                        .foregroundStyle(AppTheme.textPrimary)

                    Text("Карта формируется внутри приложения: выберите сотрудника, загрузите фото, настройте обрезку и экспортируйте PNG.")
                        .font(.caption)
                        .foregroundStyle(AppTheme.textSecondary)

                    Picker("Журналист", selection: $userId) {
                        Text("Заполнить вручную").tag(0)
                        ForEach(journalists) { j in
                            Text("\(j.name) · \(j.login ?? j.email)").tag(j.id)
                        }
                    }
                    .onChange(of: userId) { id in
                        if let j = journalists.first(where: { $0.id == id }) {
                            fullName = j.name
                            position = j.position ?? ""
                        }
                        loadDraftForCurrentEmployee()
                    }

                    Group {
                        TextField("ФИО", text: $fullName)
                            .lightFieldSurface()
                        TextField("Должность", text: $position)
                            .lightFieldSurface()
                        TextField("СМИ / организация", text: $organization)
                            .lightFieldSurface()
                        TextField("Номер пресс-карты", text: $cardNumber)
                            .lightFieldSurface()
                        TextField("Выдана", text: $issuedAt)
                            .lightFieldSurface()
                        TextField("Действует до", text: $expiresAt)
                            .lightFieldSurface()
                        TextField("Примечания", text: $notes)
                            .lightFieldSurface()
                    }

                    Button {
                        choosePhoto()
                    } label: {
                        HStack(spacing: 12) {
                            Image(systemName: photo == nil ? "photo.badge.plus" : "photo.on.rectangle")
                                .font(.title2)
                            VStack(alignment: .leading, spacing: 3) {
                                Text(photo == nil ? "Загрузить фото для пресс-карты" : "Заменить фото")
                                    .font(.headline)
                                Text("JPG, PNG или другой формат изображения. Ниже доступны масштаб и сдвиг для обрезки.")
                                    .font(.caption)
                            }
                            Spacer()
                        }
                        .padding(12)
                        .background(AppTheme.brandSoft)
                        .foregroundStyle(AppTheme.textPrimary)
                        .clipShape(RoundedRectangle(cornerRadius: 10))
                        .overlay(RoundedRectangle(cornerRadius: 10).stroke(AppTheme.cardStroke))
                    }
                    .buttonStyle(.plain)

                    if photo != nil {
                        Button("Сбросить обрезку") {
                            photoScale = 1.0
                            photoOffsetX = 0
                            photoOffsetY = 0
                            saveDraft()
                        }
                    }

                    if photo != nil {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Обрезка фото")
                                .font(.headline)
                                .foregroundStyle(AppTheme.textPrimary)
                            Slider(value: $photoScale, in: 0.6...2.4) {
                                Text("Масштаб")
                            }
                            Slider(value: $photoOffsetX, in: -160...160) {
                                Text("Сдвиг X")
                            }
                            Slider(value: $photoOffsetY, in: -160...160) {
                                Text("Сдвиг Y")
                            }
                        }
                    }

                    if let statusMessage {
                        Text(statusMessage)
                            .font(.caption)
                            .foregroundStyle(AppTheme.textSecondary)
                    }
                }
                .padding()
            }

            VStack(spacing: 12) {
                Picker("Сторона", selection: $previewSide) {
                    Text("Лицевая").tag(0)
                    Text("Оборот").tag(1)
                }
                .pickerStyle(.segmented)
                .frame(width: 260)

                Group {
                    if previewSide == 0 {
                        PressCardPreview(
                            fullName: fullName,
                            position: position,
                            organization: organization,
                            cardNumber: cardNumber,
                            issuedAt: issuedAt,
                            expiresAt: expiresAt,
                            notes: notes,
                            photo: photo,
                            photoScale: photoScale,
                            photoOffset: CGSize(width: photoOffsetX, height: photoOffsetY)
                        )
                    } else {
                        PressCardBackView(cardNumber: cardNumber, expiresAt: expiresAt)
                    }
                }
                .frame(width: 560, height: 400)
                .shadow(color: Color.black.opacity(0.16), radius: 18, x: 0, y: 8)

                HStack {
                    if showsCancelButton {
                        Button("Отмена") { isPresented = false }
                    }
                    Spacer()
                    Button {
                        printSide(.front, paper: .a4)
                    } label: {
                        Label("Печать лицо", systemImage: "printer")
                    }
                    Button {
                        printSide(.back, paper: .a4)
                    } label: {
                        Label("Оборот A4", systemImage: "printer")
                    }
                    Button {
                        printSide(.back, paper: .card)
                    } label: {
                        Label("Оборот 10,5×7,5", systemImage: "rectangle")
                    }
                    Button {
                        exportPNG()
                    } label: {
                        Label("Экспорт PNG", systemImage: "square.and.arrow.down")
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(AppTheme.brand)
                    .disabled(fullName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            }
            .padding()
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
        .task {
            journalists = JournalistCacheStore.load()
            if let first = journalists.first, userId == 0, fullName.isEmpty {
                userId = first.id
                fullName = first.name
                position = first.position ?? ""
            }
            do {
                let loaded = try await appState.journalists()
                if !loaded.isEmpty {
                    journalists = loaded
                    JournalistCacheStore.save(loaded)
                    if let first = loaded.first, fullName.isEmpty {
                        userId = first.id
                        fullName = first.name
                        position = first.position ?? ""
                    }
                }
            } catch {
                if journalists.isEmpty {
                    journalists = JournalistCacheStore.load()
                }
            }
            loadDraftForCurrentEmployee()
            didLoadInitialDraft = true
        }
        .onChange(of: fullName) { _ in saveDraftIfReady() }
        .onChange(of: position) { _ in saveDraftIfReady() }
        .onChange(of: organization) { _ in saveDraftIfReady() }
        .onChange(of: cardNumber) { _ in saveDraftIfReady() }
        .onChange(of: issuedAt) { _ in saveDraftIfReady() }
        .onChange(of: expiresAt) { _ in saveDraftIfReady() }
        .onChange(of: notes) { _ in saveDraftIfReady() }
        .onChange(of: photoScale) { _ in saveDraftIfReady() }
        .onChange(of: photoOffsetX) { _ in saveDraftIfReady() }
        .onChange(of: photoOffsetY) { _ in saveDraftIfReady() }
    }

    private func choosePhoto() {
        let panel = NSOpenPanel()
        panel.allowedContentTypes = [.image]
        panel.allowsMultipleSelection = false
        panel.canChooseDirectories = false
        if panel.runModal() == .OK, let url = panel.url, let image = NSImage(contentsOf: url) {
            photo = image
            statusMessage = "Фото загружено: \(url.lastPathComponent)"
            saveDraft(photoSourceURL: url)
        }
    }

    private enum PrintSide {
        case front
        case back
    }

    private func printSide(_ side: PrintSide, paper: PressCardPrintPaper) {
        showPrintPresetHint(side: side, paper: paper)

        let renderedImage: NSImage = switch side {
        case .front:
            PressCardRenderer.render(
                fullName: fullName,
                position: position,
                organization: organization,
                cardNumber: cardNumber,
                issuedAt: issuedAt,
                expiresAt: expiresAt,
                notes: notes,
                photo: photo,
                photoScale: photoScale,
                photoOffset: CGSize(width: photoOffsetX, height: photoOffsetY)
            )
        case .back:
            PressCardRenderer.renderBack(cardNumber: cardNumber, expiresAt: expiresAt)
        }
        let image = PressCardRenderer.photoPrintImage(from: renderedImage)
        let printView = PressCardPrintView(images: [image], side: side == .front ? .front : .back, paper: paper)
        let operation = NSPrintOperation(view: printView)
        operation.printInfo.paperSize = printView.paperSize
        operation.printInfo.scalingFactor = 1
        operation.printInfo.horizontalPagination = paper == .card ? .clip : .automatic
        operation.printInfo.verticalPagination = paper == .card ? .clip : .automatic
        operation.printInfo.isHorizontallyCentered = paper == .a4
        operation.printInfo.isVerticallyCentered = paper == .a4
        operation.printInfo.orientation = paper == .card ? .landscape : .portrait
        operation.printInfo.topMargin = 0
        operation.printInfo.bottomMargin = 0
        operation.printInfo.leftMargin = 0
        operation.printInfo.rightMargin = 0
        operation.run()
    }

    private func showPrintPresetHint(side: PrintSide, paper: PressCardPrintPaper) {
        let alert = NSAlert()
        alert.alertStyle = .informational
        alert.messageText = "Настройки печати пресс-карты"
        let paperText = paper == .card
            ? "Размер бумаги: пользовательский 10,5×7,5 см, ориентация горизонтальная (landscape), масштаб 100%, без полей/Borderless если доступно."
            : "Размер бумаги: A4, масштаб 100%, карточка печатается в начале листа."
        let mediaText = side == .front
            ? "Лицевая сторона: выберите глянцевую фотобумагу, качество Best/Photo, без полей если доступно."
            : "Оборотная сторона: выберите матовую бумагу, качество Best/Photo, без полей если доступно."
        alert.informativeText = "\(paperText)\n\(mediaText)\nЕсли в драйвере есть управление цветом, используйте режим Photo/Vivid или ColorSync профиля фотобумаги."
        alert.addButton(withTitle: "Продолжить")
        alert.runModal()
    }

    private func renderedFrontImage() -> NSImage {
        PressCardRenderer.render(
            fullName: fullName,
            position: position,
            organization: organization,
            cardNumber: cardNumber,
            issuedAt: issuedAt,
            expiresAt: expiresAt,
            notes: notes,
            photo: photo,
            photoScale: photoScale,
            photoOffset: CGSize(width: photoOffsetX, height: photoOffsetY)
        )
    }

    private func exportPNG() {
        let panel = NSSavePanel()
        panel.allowedContentTypes = [.png]
        panel.nameFieldStringValue = "\(cardNumber.isEmpty ? "press-card" : cardNumber).png"
        guard panel.runModal() == .OK, let url = panel.url else { return }

        let image = renderedFrontImage()

        guard let tiff = image.tiffRepresentation,
              let bitmap = NSBitmapImageRep(data: tiff),
              let data = bitmap.representation(using: .png, properties: [:]) else {
            statusMessage = "Не удалось подготовить PNG."
            return
        }

        do {
            try data.write(to: url)
            statusMessage = "PNG сохранён: \(url.path)"
        } catch {
            statusMessage = "Ошибка сохранения: \(error.localizedDescription)"
        }
    }

    private var currentDraftKey: String {
        if userId != 0 {
            return "user-\(userId)"
        }
        let normalizedName = fullName
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .lowercased()
            .replacingOccurrences(of: " ", with: "-")
        return normalizedName.isEmpty ? "manual-default" : "manual-\(normalizedName)"
    }

    private func saveDraftIfReady() {
        guard didLoadInitialDraft else { return }
        saveDraft()
    }

    private func saveDraft(photoSourceURL: URL? = nil) {
        var photoPath: String?
        if let photoSourceURL {
            photoPath = PressCardDraftStore.savePhoto(from: photoSourceURL, key: currentDraftKey)
        } else {
            photoPath = PressCardDraftStore.load(key: currentDraftKey)?.photoPath
        }

        let draft = PressCardDraft(
            key: currentDraftKey,
            userId: userId,
            fullName: fullName,
            position: position,
            organization: organization,
            cardNumber: cardNumber,
            issuedAt: issuedAt,
            expiresAt: expiresAt,
            notes: notes,
            photoPath: photoPath,
            photoScale: photoScale,
            photoOffsetX: photoOffsetX,
            photoOffsetY: photoOffsetY
        )
        PressCardDraftStore.save(draft)
        statusMessage = "Макет сохранён в приложении."
    }

    private func loadDraftForCurrentEmployee() {
        guard let draft = PressCardDraftStore.load(key: currentDraftKey) else { return }
        userId = draft.userId
        fullName = draft.fullName
        position = draft.position
        organization = draft.organization
        cardNumber = draft.cardNumber
        issuedAt = draft.issuedAt
        expiresAt = draft.expiresAt
        notes = draft.notes
        photoScale = draft.photoScale
        photoOffsetX = draft.photoOffsetX
        photoOffsetY = draft.photoOffsetY
        if let photoPath = draft.photoPath {
            photo = NSImage(contentsOfFile: photoPath)
        } else {
            photo = nil
        }
        statusMessage = "Загружен сохранённый макет."
    }
}

struct PressCardPreview: View {
    let fullName: String
    let position: String
    let organization: String
    let cardNumber: String
    let issuedAt: String
    let expiresAt: String
    let notes: String
    let photo: NSImage?
    let photoScale: Double
    let photoOffset: CGSize

    var body: some View {
        GeometryReader { proxy in
            let scale = proxy.size.width / 1024
            ZStack(alignment: .topLeading) {
                Image("PressCardTemplate")
                    .resizable()
                    .frame(width: proxy.size.width, height: proxy.size.width * 731 / 1024)

                photoLayer(scale: scale)
                    .frame(width: 262 * scale, height: 343 * scale)
                    .position(x: (104 + 262 / 2) * scale, y: (150 + 343 / 2) * scale)

                Text(cardNumber.isEmpty ? Self.defaultCardNumber : cardNumber)
                    .font(Self.oswald(size: 48 * scale))
                    .foregroundStyle(Color(red: 0.85, green: 0.02, blue: 0.02))
                    .tracking(1.2 * scale)
                    .position(x: 830 * scale, y: 68 * scale)

                VStack(spacing: 1 * scale) {
                    Text(displayNameLine(0))
                        .font(Self.oswaldSemiBold(size: nameFontSize * scale))
                        .lineLimit(1)
                        .minimumScaleFactor(0.58)
                    Text(displayNameLine(1))
                        .font(Self.oswaldSemiBold(size: nameFontSize * scale))
                        .lineLimit(1)
                        .minimumScaleFactor(0.58)
                }
                .foregroundStyle(.black)
                .tracking(2.2 * scale)
                .multilineTextAlignment(.center)
                .frame(width: 470 * scale, height: 92 * scale)
                .position(x: 681 * scale, y: 356 * scale)

                Text(position.isEmpty ? "КОРРЕСПОНДЕНТ" : position.uppercased())
                    .font(Self.oswaldRegular(size: 31 * scale))
                    .foregroundStyle(.black)
                    .tracking(2 * scale)
                    .frame(width: 420 * scale)
                    .position(x: 681 * scale, y: 433 * scale)

                Text(expiresAt.isEmpty ? "31.12.2026" : expiresAt)
                    .font(Self.oswald(size: 24 * scale))
                    .foregroundStyle(.black)
                    .tracking(0.6 * scale)
                    .position(x: 349 * scale, y: 511 * scale)
            }
        }
        .aspectRatio(1024 / 731, contentMode: .fit)
        .clipShape(RoundedRectangle(cornerRadius: 3))
        .overlay(RoundedRectangle(cornerRadius: 3).stroke(AppTheme.cardStroke))
    }

    private static var defaultCardNumber: String {
        let f = DateFormatter()
        f.dateFormat = "yy"
        return "НР\(f.string(from: Date())) № 011"
    }

    private static func oswald(size: CGFloat) -> Font {
        .custom("Oswald-Bold", size: size)
    }

    private static func oswaldSemiBold(size: CGFloat) -> Font {
        .custom("Oswald-SemiBold", size: size)
    }

    private static func oswaldRegular(size: CGFloat) -> Font {
        .custom("Oswald-Regular", size: size)
    }

    private var nameFontSize: CGFloat {
        let longest = max(displayNameLine(0).count, displayNameLine(1).count)
        switch longest {
        case 0...14: return 36
        case 15...20: return 32
        case 21...26: return 28
        default: return 25
        }
    }

    private func photoLayer(scale: CGFloat) -> some View {
        ZStack {
            Color.clear
            if let photo {
                Image(nsImage: photo)
                    .resizable()
                    .scaledToFill()
                    .scaleEffect(photoScale)
                    .offset(CGSize(width: photoOffset.width * scale, height: photoOffset.height * scale))
            }
        }
        .clipped()
    }

    private func displayNameLine(_ index: Int) -> String {
        let parts = fullName
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .uppercased()
            .split(separator: " ")
            .map(String.init)
        guard !parts.isEmpty else {
            return index == 0 ? "ФАМИЛИЯ" : "ИМЯ ОТЧЕСТВО"
        }
        if index == 0 {
            return parts.first ?? ""
        }
        let rest = parts.dropFirst().joined(separator: " ")
        return rest.isEmpty ? " " : rest
    }
}

enum PressCardRenderer {
    // 10.5 x 7.5 cm at 600 DPI for crisp printing and downsampling by the print pipeline.
    static let renderSize = CGSize(width: 2480, height: 1772)

    @MainActor
    static func render(
        fullName: String,
        position: String,
        organization: String,
        cardNumber: String,
        issuedAt: String,
        expiresAt: String,
        notes: String,
        photo: NSImage?,
        photoScale: Double,
        photoOffset: CGSize
    ) -> NSImage {
        let size = renderSize
        let view = PressCardPreview(
            fullName: fullName,
            position: position,
            organization: organization,
            cardNumber: cardNumber,
            issuedAt: issuedAt,
            expiresAt: expiresAt,
            notes: notes,
            photo: photo,
            photoScale: photoScale,
            photoOffset: CGSize(width: photoOffset.width * 2048 / 560, height: photoOffset.height * 1462 / 400)
        )
        .frame(width: size.width, height: size.height)

        let renderer = ImageRenderer(content: view)
        renderer.proposedSize = ProposedViewSize(size)
        renderer.scale = 1
        return renderer.nsImage ?? NSImage(size: size)
    }

    @MainActor
    static func renderBack(cardNumber: String, expiresAt: String) -> NSImage {
        let size = renderSize
        let view = PressCardBackView(cardNumber: cardNumber, expiresAt: expiresAt)
            .frame(width: size.width, height: size.height)
        let renderer = ImageRenderer(content: view)
        renderer.proposedSize = ProposedViewSize(size)
        renderer.scale = 1
        return renderer.nsImage ?? NSImage(size: size)
    }

    static func photoPrintImage(from image: NSImage) -> NSImage {
        guard let tiff = image.tiffRepresentation,
              let input = CIImage(data: tiff),
              let filter = CIFilter(name: "CIColorControls") else {
            return image
        }

        filter.setValue(input, forKey: kCIInputImageKey)
        filter.setValue(1.16, forKey: kCIInputSaturationKey)
        filter.setValue(1.08, forKey: kCIInputContrastKey)
        filter.setValue(0.015, forKey: kCIInputBrightnessKey)

        guard let output = filter.outputImage else { return image }
        let context = CIContext(options: [.workingColorSpace: CGColorSpace(name: CGColorSpace.sRGB) as Any])
        guard let cgImage = context.createCGImage(output, from: output.extent) else { return image }
        return NSImage(cgImage: cgImage, size: image.size)
    }
}

struct PressCardBackView: View {
    let cardNumber: String
    let expiresAt: String

    var body: some View {
        GeometryReader { proxy in
            let scale = proxy.size.width / 1024
            ZStack(alignment: .topLeading) {
                LinearGradient(
                    colors: [
                        Color(red: 0.94, green: 0.96, blue: 1.0),
                        Color(red: 0.82, green: 0.88, blue: 0.98)
                    ],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                )

                VStack(alignment: .leading, spacing: 18 * scale) {
                    HStack(alignment: .center) {
                        VStack(alignment: .leading, spacing: 4 * scale) {
                            Text("ПРАВА И ОБЯЗАННОСТИ ЖУРНАЛИСТА")
                                .font(.custom("Oswald-Bold", size: 34 * scale))
                                .foregroundStyle(.black)
                                .lineLimit(1)
                            Text("\(cardNumber.isEmpty ? "НР26 № 011" : cardNumber) · действительно до \(expiresAt.isEmpty ? "31.12.2026" : expiresAt)")
                                .font(.custom("Oswald-Bold", size: 19 * scale))
                                .foregroundStyle(Color(red: 0.74, green: 0.02, blue: 0.02))
                        }
                        Spacer()
                        Image("NotaMiruLogo")
                            .resizable()
                            .scaledToFit()
                            .frame(width: 255 * scale, height: 78 * scale)
                    }

                    Rectangle()
                        .fill(Color.black.opacity(0.35))
                        .frame(height: max(1, 1.2 * scale))

                    VStack(alignment: .leading, spacing: 14 * scale) {
                        VStack(alignment: .leading, spacing: 8 * scale) {
                            Text("Права журналиста")
                                .font(.custom("Oswald-Bold", size: 25 * scale))
                            Text(Self.rightsText)
                                .font(.system(size: 17 * scale, weight: .medium))
                                .lineSpacing(3 * scale)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                        VStack(alignment: .leading, spacing: 8 * scale) {
                            Text("Обязанности журналиста")
                                .font(.custom("Oswald-Bold", size: 25 * scale))
                            Text(Self.dutiesText)
                                .font(.system(size: 17 * scale, weight: .medium))
                                .lineSpacing(3 * scale)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }
                    .foregroundStyle(.black)

                    Spacer(minLength: 0)

                    Text("Основание: Закон РФ от 27.12.1991 № 2124-1 «О средствах массовой информации», ст. 47 и ст. 49. Удостоверение предъявляется по требованию должностных лиц и действительно только при выполнении редакционного задания.")
                        .font(.system(size: 13 * scale, weight: .semibold))
                        .foregroundStyle(.black.opacity(0.82))
                        .lineSpacing(3 * scale)
                        .fixedSize(horizontal: false, vertical: true)
                }
                .padding(.horizontal, 70 * scale)
                .padding(.vertical, 48 * scale)
            }
        }
        .aspectRatio(1024 / 731, contentMode: .fit)
        .clipShape(RoundedRectangle(cornerRadius: 3))
        .overlay(RoundedRectangle(cornerRadius: 3).stroke(AppTheme.cardStroke))
    }

    private static let rightsText = """
    • Искать, запрашивать, получать и распространять информацию.
    • Посещать органы власти, организации, предприятия, учреждения и пресс-службы.
    • Быть принятым должностными лицами в связи с запросом информации.
    • Производить записи, фото- и видеосъёмку, если это не запрещено законом.
    • Проверять достоверность сообщаемой информации.
    """

    private static let dutiesText = """
    • Соблюдать устав редакции и законодательство Российской Федерации.
    • Проверять достоверность сообщаемой информации.
    • Указывать источник информации, если она оглашается впервые.
    • Сохранять конфиденциальность информации и источника в случаях, установленных законом.
    • Предъявлять редакционное удостоверение при профессиональной деятельности.
    """
}

final class PressCardPrintView: NSView {
    private let images: [NSImage]
    private let side: PressCardPrintSide
    private let paper: PressCardPrintPaper
    // A4 portrait in PostScript points (72 pt/in). Card is 10.5 x 7.5 cm with trimming bleed.
    private let a4Inset: CGFloat = 18
    private let cardSize = CGSize(width: 297.64, height: 212.60)

    var paperSize: NSSize {
        switch paper {
        case .a4:
            return NSSize(width: 595.28, height: 841.89)
        case .card:
            return NSSize(width: cardSize.width, height: cardSize.height)
        }
    }

    fileprivate init(images: [NSImage], side: PressCardPrintSide, paper: PressCardPrintPaper) {
        self.images = images
        self.side = side
        self.paper = paper
        let size = paper == .a4
            ? CGSize(width: 595.28, height: 841.89 * CGFloat(images.count))
            : CGSize(width: cardSize.width, height: cardSize.height * CGFloat(images.count))
        super.init(frame: NSRect(origin: .zero, size: size))
    }

    required init?(coder: NSCoder) {
        nil
    }

    override func knowsPageRange(_ range: NSRangePointer) -> Bool {
        range.pointee = NSRange(location: 1, length: images.count)
        return true
    }

    override func rectForPage(_ page: Int) -> NSRect {
        NSRect(x: 0, y: CGFloat(page - 1) * paperSize.height, width: paperSize.width, height: paperSize.height)
    }

    override func draw(_ dirtyRect: NSRect) {
        super.draw(dirtyRect)
        NSGraphicsContext.current?.imageInterpolation = .high
        NSGraphicsContext.current?.shouldAntialias = true

        for (index, image) in images.enumerated() {
            let pageOriginY = CGFloat(index) * paperSize.height
            let rect: NSRect
            switch paper {
            case .a4:
                let x = side == .front ? a4Inset : paperSize.width - cardSize.width - a4Inset
                rect = NSRect(
                    x: x,
                    y: pageOriginY + paperSize.height - cardSize.height - a4Inset,
                    width: cardSize.width,
                    height: cardSize.height
                )
            case .card:
                rect = NSRect(x: 0, y: pageOriginY, width: paperSize.width, height: paperSize.height)
            }
            image.draw(in: rect, from: NSRect(origin: .zero, size: image.size), operation: .copy, fraction: 1)
        }
    }
}
