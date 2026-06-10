import SwiftUI
import AppKit
import CoreText
import PDFKit
import UniformTypeIdentifiers

// MARK: - Каталог активов (сканы листа, подписи, печати)

enum SigningAssetKind: String, Codable, CaseIterable {
    case sheet
    case signature
    case stamp

    var label: String {
        switch self {
        case .sheet: return "Чистый лист (фон)"
        case .signature: return "Подпись"
        case .stamp: return "Печать"
        }
    }
}

struct SigningAsset: Identifiable, Codable, Equatable {
    let id: UUID
    let kind: SigningAssetKind
    var name: String
    let fileName: String
    /// Перевод пикселей скана в типографские пункты (реальный размер).
    /// Для сканов A4 шириной 744 px: 595.2 / 744 = 0.8 pt/px.
    var pointsPerPixel: Double?
    /// Подписант (владелец подписи/печати), напр. «ИП Архангельский Д.Н.»
    var signer: String?
}

@MainActor
final class SigningAssetStore: ObservableObject {
    @Published private(set) var assets: [SigningAsset] = []

    static let a4ScanPointsPerPixel = 595.2 / 744.0

    private let directory: URL
    private let indexURL: URL
    private var thumbnailCache: [UUID: NSImage] = [:]

    init() {
        let base = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first
            ?? FileManager.default.temporaryDirectory
        directory = base.appendingPathComponent("NotaMiru/SigningAssets", isDirectory: true)
        indexURL = directory.appendingPathComponent("index.json")
        try? FileManager.default.createDirectory(at: directory, withIntermediateDirectories: true)
        load()
    }

    static let defaultSigner = "ИП Архангельский Д.Н."

    func assets(of kind: SigningAssetKind, signer: String? = nil) -> [SigningAsset] {
        assets.filter { asset in
            asset.kind == kind && (signer == nil || (asset.signer ?? Self.defaultSigner) == signer)
        }
    }

    /// Список подписантов (по имеющимся подписям и печатям)
    var signers: [String] {
        var seen: [String] = []
        for asset in assets where asset.kind != .sheet {
            let signer = asset.signer ?? Self.defaultSigner
            if !seen.contains(signer) { seen.append(signer) }
        }
        return seen.isEmpty ? [Self.defaultSigner] : seen
    }

    func asset(id: UUID?) -> SigningAsset? {
        guard let id else { return nil }
        return assets.first { $0.id == id }
    }

    func image(for id: UUID?) -> NSImage? {
        guard let id, let asset = assets.first(where: { $0.id == id }) else { return nil }
        return NSImage(contentsOf: directory.appendingPathComponent(asset.fileName))
    }

    /// Миниатюра для меню и списков
    func thumbnail(for id: UUID?, height: CGFloat = 40) -> NSImage? {
        guard let id else { return nil }
        if let cached = thumbnailCache[id] { return cached }
        guard let full = image(for: id), full.size.height > 0 else { return nil }

        let scale = height / full.size.height
        let size = NSSize(width: max(1, full.size.width * scale), height: height)
        let thumb = NSImage(size: size)
        thumb.lockFocus()
        NSColor.white.setFill()
        NSRect(origin: .zero, size: size).fill()
        full.draw(in: NSRect(origin: .zero, size: size),
                  from: .zero, operation: .multiply, fraction: 1)
        thumb.unlockFocus()
        thumbnailCache[id] = thumb
        return thumb
    }

    /// Реальная ширина актива в пунктах (для печати в натуральную величину)
    func realWidthPoints(for id: UUID?) -> CGFloat? {
        guard let id, let asset = assets.first(where: { $0.id == id }),
              let image = image(for: id),
              let rep = image.representations.first else { return nil }
        let ppp = asset.pointsPerPixel ?? Self.a4ScanPointsPerPixel
        return CGFloat(rep.pixelsWide) * CGFloat(ppp)
    }

    func importAsset(kind: SigningAssetKind, signer: String? = nil) -> SigningAsset? {
        let panel = NSOpenPanel()
        panel.allowedContentTypes = [.png, .jpeg, .tiff, .heic, .image]
        panel.allowsMultipleSelection = false
        panel.message = "Выберите изображение: \(kind.label.lowercased())"
        guard panel.runModal() == .OK, let source = panel.url else { return nil }
        AppLog.info("Импорт актива (\(kind.rawValue)): \(source.lastPathComponent)")

        let fileName: String
        if kind == .sheet {
            // лист сохраняем как есть — фактура бумаги нужна как фон
            let ext = source.pathExtension.isEmpty ? "png" : source.pathExtension
            fileName = UUID().uuidString + "." + ext
            do {
                try FileManager.default.copyItem(at: source, to: directory.appendingPathComponent(fileName))
            } catch {
                AppLog.error("Не удалось скопировать скан листа", error)
                return nil
            }
        } else {
            // подписи и печати нормализуем: белая точка → 255, фон растворяется при multiply
            guard let original = NSImage(contentsOf: source),
                  let normalized = Self.inkEnhanced(original, kind: kind),
                  let png = normalized.representation(using: .png, properties: [:]) else {
                AppLog.error("Импорт актива: не удалось обработать изображение \(source.lastPathComponent)")
                return nil
            }
            fileName = UUID().uuidString + ".png"
            do {
                try png.write(to: directory.appendingPathComponent(fileName))
            } catch {
                AppLog.error("Не удалось сохранить обработанный актив", error)
                return nil
            }
        }

        var ppp = Self.a4ScanPointsPerPixel
        if let image = NSImage(contentsOf: source),
           let rep = image.representations.first,
           rep.pixelsWide > 0, rep.size.width > 0,
           abs(rep.size.width - CGFloat(rep.pixelsWide)) > 1 {
            ppp = Double(rep.size.width / CGFloat(rep.pixelsWide))
        }

        let asset = SigningAsset(
            id: UUID(),
            kind: kind,
            name: source.deletingPathExtension().lastPathComponent,
            fileName: fileName,
            pointsPerPixel: ppp,
            signer: kind == .sheet ? nil : (signer ?? Self.defaultSigner)
        )
        assets.append(asset)
        save()
        return asset
    }

    func delete(id: UUID?) {
        guard let id, let asset = assets.first(where: { $0.id == id }) else { return }
        AppLog.info("Удаление актива из каталога: \(asset.name) (\(asset.kind.rawValue))")
        try? FileManager.default.removeItem(at: directory.appendingPathComponent(asset.fileName))
        assets.removeAll { $0.id == id }
        thumbnailCache[id] = nil
        save()
    }

    /// Обработка чернил при импорте (вариант V2, согласован с каталогом):
    /// нормализация белого + лёгкое усиление x1.2; подписи получают
    /// лёгкий синий оттенок ручки. Фон остаётся чисто белым для multiply.
    static func inkEnhanced(_ image: NSImage, kind: SigningAssetKind) -> NSBitmapImageRep? {
        guard let rep = whiteNormalized(image), let data = rep.bitmapData else { return nil }

        let boost = 1.2
        let penBlue: [Double] = [40, 70, 165]
        let tintMix = 0.45

        let bytesPerRow = rep.bytesPerRow
        for y in 0..<rep.pixelsHigh {
            for x in 0..<rep.pixelsWide {
                let p = y * bytesPerRow + x * 4
                var rgb = [Double](repeating: 0, count: 3)
                for c in 0..<3 {
                    let density = min(1.0, boost * (1.0 - Double(data[p + c]) / 255.0))
                    rgb[c] = (1.0 - density) * 255.0
                }
                if kind == .signature {
                    // лёгкий синий оттенок: только для чернил, фон не трогаем
                    let density = 1.0 - (rgb[0] + rgb[1] + rgb[2]) / (3 * 255.0)
                    let mix = tintMix * min(1.0, density / 0.08)
                    for c in 0..<3 {
                        let tinted = 255.0 - density * (255.0 - penBlue[c])
                        rgb[c] = rgb[c] * (1.0 - mix) + tinted * mix
                    }
                }
                for c in 0..<3 {
                    data[p + c] = UInt8(max(0, min(255, rgb[c])))
                }
            }
        }
        return rep
    }

    /// Уровни как в Photoshop: тянем белую точку к 255 по каждому каналу,
    /// чтобы бумага скана полностью растворялась при multiply-наложении.
    static func whiteNormalized(_ image: NSImage) -> NSBitmapImageRep? {
        guard let tiff = image.tiffRepresentation,
              let src = NSBitmapImageRep(data: tiff) else { return nil }
        let w = src.pixelsWide
        let h = src.pixelsHigh
        guard w > 0, h > 0 else { return nil }

        guard let rep = NSBitmapImageRep(
            bitmapDataPlanes: nil, pixelsWide: w, pixelsHigh: h,
            bitsPerSample: 8, samplesPerPixel: 4, hasAlpha: true, isPlanar: false,
            colorSpaceName: .deviceRGB, bytesPerRow: 0, bitsPerPixel: 0
        ) else { return nil }

        NSGraphicsContext.saveGraphicsState()
        NSGraphicsContext.current = NSGraphicsContext(bitmapImageRep: rep)
        src.draw(in: NSRect(x: 0, y: 0, width: w, height: h))
        NSGraphicsContext.restoreGraphicsState()

        guard let data = rep.bitmapData else { return nil }
        let bytesPerRow = rep.bytesPerRow

        var samples: [[UInt8]] = [[], [], []]
        let strideStep = max(1, min(w, h) / 200)
        for y in Swift.stride(from: 0, to: h, by: strideStep) {
            for x in Swift.stride(from: 0, to: w, by: strideStep) {
                let p = y * bytesPerRow + x * 4
                let r = data[p], g = data[p + 1], b = data[p + 2]
                if (Int(r) + Int(g) + Int(b)) / 3 > 165 {
                    samples[0].append(r); samples[1].append(g); samples[2].append(b)
                }
            }
        }
        guard !samples[0].isEmpty else { return rep }
        var scale = [Double](repeating: 1, count: 3)
        for c in 0..<3 {
            let sorted = samples[c].sorted()
            let median = Double(sorted[sorted.count / 2])
            scale[c] = 255.0 / max(1.0, median * 0.97)
        }

        for y in 0..<h {
            for x in 0..<w {
                let p = y * bytesPerRow + x * 4
                for c in 0..<3 {
                    data[p + c] = UInt8(min(255.0, Double(data[p + c]) * scale[c]))
                }
                data[p + 3] = 255
            }
        }
        return rep
    }

    private func load() {
        guard let data = try? Data(contentsOf: indexURL),
              let decoded = try? JSONDecoder().decode([SigningAsset].self, from: data) else { return }
        assets = decoded
        // Миграция: активы без подписанта относим к подписанту по умолчанию
        var migrated = false
        for index in assets.indices where assets[index].kind != .sheet && assets[index].signer == nil {
            assets[index].signer = Self.defaultSigner
            migrated = true
        }
        if migrated {
            AppLog.info("Каталог: активы без подписанта помечены как «\(Self.defaultSigner)»")
            save()
        }
    }

    private func save() {
        guard let data = try? JSONEncoder().encode(assets) else { return }
        try? data.write(to: indexURL)
    }
}

// MARK: - Размещение подписи/печати на странице

struct OverlayPlacement: Identifiable, Equatable, Codable {
    var id = UUID()
    var assetID: UUID
    var page: Int          // 0-based
    var x: Double          // 0...1 от ширины страницы (центр)
    var y: Double          // 0...1 от высоты страницы, от верха (центр)
    var scalePercent: Double = 100
    /// Цветность чернил, %: 100 — как в каталоге, меньше — бледнее, больше — насыщеннее
    var inkPercent: Double = 100

    init(assetID: UUID, page: Int, x: Double = 0.65, y: Double = 0.82, scalePercent: Double = 100) {
        self.assetID = assetID
        self.page = page
        self.x = x
        self.y = y
        self.scalePercent = scalePercent
    }

    enum CodingKeys: String, CodingKey {
        case id, assetID, page, x, y, scalePercent, inkPercent
    }

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(UUID.self, forKey: .id)
        assetID = try c.decode(UUID.self, forKey: .assetID)
        page = try c.decode(Int.self, forKey: .page)
        x = try c.decode(Double.self, forKey: .x)
        y = try c.decode(Double.self, forKey: .y)
        scalePercent = try c.decodeIfPresent(Double.self, forKey: .scalePercent) ?? 100
        // проекты, сохранённые до появления цветности, читаются как 100%
        inkPercent = try c.decodeIfPresent(Double.self, forKey: .inkPercent) ?? 100
    }
}

// MARK: - Сохранённые документы (проекты)

struct DocumentProject: Identifiable, Codable, Equatable {
    let id: UUID
    var name: String
    var updatedAt: Date
    var kind: String          // "text" | "pdf"
    var fileName: String      // .rtf или .pdf
    var sheetID: UUID?
    var placements: [OverlayPlacement]
}

@MainActor
final class DocumentProjectStore: ObservableObject {
    @Published private(set) var projects: [DocumentProject] = []

    private let directory: URL
    private let indexURL: URL

    init() {
        let base = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first
            ?? FileManager.default.temporaryDirectory
        directory = base.appendingPathComponent("NotaMiru/Documents", isDirectory: true)
        indexURL = directory.appendingPathComponent("index.json")
        try? FileManager.default.createDirectory(at: directory, withIntermediateDirectories: true)
        load()
    }

    func saveText(name: String, existingID: UUID?, text: NSAttributedString,
                  sheetID: UUID?, placements: [OverlayPlacement]) -> DocumentProject? {
        let range = NSRange(location: 0, length: text.length)
        guard let data = try? text.data(
            from: range,
            documentAttributes: [.documentType: NSAttributedString.DocumentType.rtf]
        ) else {
            AppLog.error("Сохранение проекта «\(name)»: не удалось сериализовать RTF")
            return nil
        }
        return upsert(name: name, existingID: existingID, kind: "text", ext: "rtf",
                      data: data, sheetID: sheetID, placements: placements)
    }

    func savePDF(name: String, existingID: UUID?, pdf: PDFDocument,
                 sheetID: UUID?, placements: [OverlayPlacement]) -> DocumentProject? {
        guard let data = pdf.dataRepresentation() else {
            AppLog.error("Сохранение проекта «\(name)»: не удалось получить данные PDF")
            return nil
        }
        return upsert(name: name, existingID: existingID, kind: "pdf", ext: "pdf",
                      data: data, sheetID: sheetID, placements: placements)
    }

    func loadText(_ project: DocumentProject) -> NSAttributedString? {
        let url = directory.appendingPathComponent(project.fileName)
        return try? NSAttributedString(
            url: url,
            options: [.documentType: NSAttributedString.DocumentType.rtf],
            documentAttributes: nil
        )
    }

    func loadPDF(_ project: DocumentProject) -> PDFDocument? {
        PDFDocument(url: directory.appendingPathComponent(project.fileName))
    }

    func delete(_ project: DocumentProject) {
        AppLog.info("Удаление сохранённого документа: \(project.name) (\(project.id))")
        try? FileManager.default.removeItem(at: directory.appendingPathComponent(project.fileName))
        projects.removeAll { $0.id == project.id }
        save()
    }

    private func upsert(name: String, existingID: UUID?, kind: String, ext: String,
                        data: Data, sheetID: UUID?, placements: [OverlayPlacement]) -> DocumentProject? {
        let id = existingID ?? UUID()
        let fileName = existing(id)?.fileName ?? (id.uuidString + "." + ext)
        do {
            try data.write(to: directory.appendingPathComponent(fileName))
        } catch {
            AppLog.error("Сохранение проекта «\(name)»: ошибка записи файла", error)
            return nil
        }
        AppLog.info("Проект сохранён: «\(name)» (\(kind), \(data.count) байт, наложений: \(placements.count))")

        let project = DocumentProject(
            id: id, name: name, updatedAt: Date(), kind: kind,
            fileName: fileName, sheetID: sheetID, placements: placements
        )
        projects.removeAll { $0.id == id }
        projects.insert(project, at: 0)
        save()
        return project
    }

    private func existing(_ id: UUID) -> DocumentProject? {
        projects.first { $0.id == id }
    }

    private func load() {
        guard let data = try? Data(contentsOf: indexURL),
              let decoded = try? JSONDecoder().decode([DocumentProject].self, from: data) else { return }
        projects = decoded.sorted { $0.updatedAt > $1.updatedAt }
    }

    private func save() {
        projects.sort { $0.updatedAt > $1.updatedAt }
        guard let data = try? JSONEncoder().encode(projects) else { return }
        try? data.write(to: indexURL)
    }
}

// MARK: - Rich-text редактор («маленький Word»)

@MainActor
final class RichTextContext: ObservableObject {
    weak var textView: NSTextView?

    static let defaultFont = NSFont(name: "Times New Roman", size: 13) ?? NSFont.systemFont(ofSize: 13)

    func toggleBold() { toggleTrait(.boldFontMask) }
    func toggleItalic() { toggleTrait(.italicFontMask) }

    func toggleUnderline() {
        guard let tv = textView else { return }
        let range = tv.selectedRange()
        if range.length == 0 {
            var attrs = tv.typingAttributes
            let current = attrs[.underlineStyle] as? Int ?? 0
            attrs[.underlineStyle] = current == 0 ? NSUnderlineStyle.single.rawValue : 0
            tv.typingAttributes = attrs
            return
        }
        guard let storage = tv.textStorage else { return }
        let first = storage.attribute(.underlineStyle, at: range.location, effectiveRange: nil) as? Int ?? 0
        storage.beginEditing()
        storage.addAttribute(.underlineStyle, value: first == 0 ? NSUnderlineStyle.single.rawValue : 0, range: range)
        storage.endEditing()
        tv.didChangeText()
    }

    func changeFontSize(by delta: CGFloat) {
        guard let tv = textView else { return }
        let range = tv.selectedRange()
        let manager = NSFontManager.shared
        if range.length == 0 {
            var attrs = tv.typingAttributes
            let font = attrs[.font] as? NSFont ?? Self.defaultFont
            attrs[.font] = manager.convert(font, toSize: max(8, font.pointSize + delta))
            tv.typingAttributes = attrs
            return
        }
        guard let storage = tv.textStorage else { return }
        storage.beginEditing()
        storage.enumerateAttribute(.font, in: range) { value, subRange, _ in
            let font = value as? NSFont ?? Self.defaultFont
            storage.addAttribute(.font, value: manager.convert(font, toSize: max(8, font.pointSize + delta)), range: subRange)
        }
        storage.endEditing()
        tv.didChangeText()
    }

    func alignLeft() { textView?.alignLeft(nil); textView?.didChangeText() }
    func alignCenter() { textView?.alignCenter(nil); textView?.didChangeText() }
    func alignJustified() { textView?.alignJustified(nil); textView?.didChangeText() }

    private func toggleTrait(_ trait: NSFontTraitMask) {
        guard let tv = textView else { return }
        let manager = NSFontManager.shared
        let range = tv.selectedRange()
        if range.length == 0 {
            var attrs = tv.typingAttributes
            let font = attrs[.font] as? NSFont ?? Self.defaultFont
            let has = manager.traits(of: font).contains(trait)
            attrs[.font] = has ? manager.convert(font, toNotHaveTrait: trait) : manager.convert(font, toHaveTrait: trait)
            tv.typingAttributes = attrs
            return
        }
        guard let storage = tv.textStorage else { return }
        let firstFont = storage.attribute(.font, at: range.location, effectiveRange: nil) as? NSFont ?? Self.defaultFont
        let removing = manager.traits(of: firstFont).contains(trait)
        storage.beginEditing()
        storage.enumerateAttribute(.font, in: range) { value, subRange, _ in
            let font = value as? NSFont ?? Self.defaultFont
            let updated = removing ? manager.convert(font, toNotHaveTrait: trait) : manager.convert(font, toHaveTrait: trait)
            storage.addAttribute(.font, value: updated, range: subRange)
        }
        storage.endEditing()
        tv.didChangeText()
    }
}

struct RichTextEditor: NSViewRepresentable {
    @Binding var text: NSAttributedString
    let context: RichTextContext
    var onEdit: () -> Void

    func makeNSView(context coordContext: Context) -> NSScrollView {
        let scroll = NSTextView.scrollableTextView()
        let tv = scroll.documentView as! NSTextView
        tv.isRichText = true
        tv.allowsUndo = true
        tv.usesFindBar = true
        tv.importsGraphics = false
        tv.drawsBackground = true
        tv.backgroundColor = .white
        tv.insertionPointColor = .black
        tv.textColor = .black
        tv.font = RichTextContext.defaultFont
        tv.typingAttributes = [.font: RichTextContext.defaultFont, .foregroundColor: NSColor.black]
        tv.textContainerInset = NSSize(width: 10, height: 10)
        tv.delegate = coordContext.coordinator
        tv.textStorage?.setAttributedString(text)
        self.context.textView = tv
        return scroll
    }

    func updateNSView(_ scroll: NSScrollView, context coordContext: Context) {
        guard let tv = scroll.documentView as? NSTextView else { return }
        self.context.textView = tv
        if !coordContext.coordinator.isEditing, !tv.attributedString().isEqual(to: text) {
            tv.textStorage?.setAttributedString(text)
        }
    }

    func makeCoordinator() -> Coordinator { Coordinator(self) }

    final class Coordinator: NSObject, NSTextViewDelegate {
        var parent: RichTextEditor
        var isEditing = false

        init(_ parent: RichTextEditor) { self.parent = parent }

        func textDidChange(_ notification: Notification) {
            guard let tv = notification.object as? NSTextView else { return }
            isEditing = true
            parent.text = tv.attributedString().copy() as! NSAttributedString
            parent.onEdit()
            isEditing = false
        }
    }
}

// MARK: - Композиция документа

enum DocumentSource: Equatable {
    case text
    case pdf(name: String)

    var isPDF: Bool {
        if case .pdf = self { return true }
        return false
    }
}

struct DocumentComposition {
    static let a4 = CGSize(width: 595.2, height: 841.8)
    private static let margin: CGFloat = 57

    struct Overlay {
        let image: NSImage
        let x: Double
        let y: Double
        let widthPt: CGFloat
        let ink: Double    // 1.0 = как в каталоге, <1 бледнее, >1 насыщеннее
    }

    let textFrames: [CTFrame]
    let pdfDocument: PDFDocument?
    let sheet: NSImage?
    let overlaysByPage: [Int: [Overlay]]

    var pageCount: Int {
        if let pdf = pdfDocument { return max(1, pdf.pageCount) }
        return max(1, textFrames.count)
    }

    func pageSize(_ index: Int) -> CGSize {
        if let pdf = pdfDocument, let page = pdf.page(at: index) {
            let bounds = page.bounds(for: .mediaBox)
            let rotated = page.rotation % 180 != 0
            return rotated ? CGSize(width: bounds.height, height: bounds.width) : bounds.size
        }
        return Self.a4
    }

    static func make(
        source: DocumentSource,
        richText: NSAttributedString,
        pdfDocument: PDFDocument?,
        sheet: NSImage?,
        overlays: [(placement: OverlayPlacement, image: NSImage, realWidthPt: CGFloat)]
    ) -> DocumentComposition {
        var frames: [CTFrame] = []
        var pdf: PDFDocument?

        switch source {
        case .text:
            frames = paginate(richText)
        case .pdf:
            pdf = pdfDocument
        }

        var byPage: [Int: [Overlay]] = [:]
        for item in overlays {
            let widthPt = item.realWidthPt * CGFloat(item.placement.scalePercent / 100)
            byPage[item.placement.page, default: []].append(
                Overlay(image: item.image, x: item.placement.x, y: item.placement.y,
                        widthPt: widthPt, ink: item.placement.inkPercent / 100)
            )
        }

        return DocumentComposition(
            textFrames: frames,
            pdfDocument: pdf,
            sheet: source.isPDF ? nil : sheet,
            overlaysByPage: byPage
        )
    }

    private static var textRect: CGRect {
        CGRect(x: margin, y: margin, width: a4.width - margin * 2, height: a4.height - margin * 2)
    }

    private static func paginate(_ input: NSAttributedString) -> [CTFrame] {
        let text: NSAttributedString
        if input.length == 0 {
            text = NSAttributedString(string: " ", attributes: [.font: RichTextContext.defaultFont])
        } else {
            text = input
        }
        let framesetter = CTFramesetterCreateWithAttributedString(text)
        let path = CGPath(rect: textRect, transform: nil)

        var frames: [CTFrame] = []
        var location = 0
        while location < text.length {
            let frame = CTFramesetterCreateFrame(framesetter, CFRange(location: location, length: 0), path, nil)
            let visible = CTFrameGetVisibleStringRange(frame)
            if visible.length == 0 { break }
            frames.append(frame)
            location = visible.location + visible.length
        }
        if frames.isEmpty {
            frames.append(CTFramesetterCreateFrame(framesetter, CFRange(location: 0, length: 0), path, nil))
        }
        return frames
    }

    func draw(page index: Int, in ctx: CGContext) {
        let size = pageSize(index)
        let pageRect = CGRect(origin: .zero, size: size)

        ctx.setFillColor(CGColor(gray: 1, alpha: 1))
        ctx.fill(pageRect)

        if let pdf = pdfDocument, let page = pdf.page(at: index) {
            ctx.saveGState()
            page.draw(with: .mediaBox, to: ctx)
            ctx.restoreGState()
        } else {
            if let sheetCG = Self.cgImage(sheet) {
                let iw = CGFloat(sheetCG.width)
                let ih = CGFloat(sheetCG.height)
                let fill = max(size.width / iw, size.height / ih)
                let drawRect = CGRect(
                    x: (size.width - iw * fill) / 2,
                    y: (size.height - ih * fill) / 2,
                    width: iw * fill,
                    height: ih * fill
                )
                ctx.saveGState()
                ctx.clip(to: pageRect)
                ctx.draw(sheetCG, in: drawRect)
                ctx.restoreGState()
            }
            if index < textFrames.count {
                ctx.saveGState()
                ctx.textMatrix = .identity
                CTFrameDraw(textFrames[index], ctx)
                ctx.restoreGState()
            }
        }

        for overlay in overlaysByPage[index] ?? [] {
            guard let cg = Self.cgImage(overlay.image) else { continue }
            let width = overlay.widthPt
            let height = width * CGFloat(cg.height) / CGFloat(cg.width)
            let center = CGPoint(
                x: size.width * CGFloat(overlay.x),
                y: size.height * (1 - CGFloat(overlay.y))
            )
            let rect = CGRect(x: center.x - width / 2, y: center.y - height / 2, width: width, height: height)
            ctx.saveGState()
            // multiply: белая бумага скана растворяется, остаются чернила
            ctx.setBlendMode(.multiply)
            if overlay.ink < 1 {
                // бледнее: полупрозрачный multiply
                ctx.setAlpha(CGFloat(max(0.2, overlay.ink)))
                ctx.draw(cg, in: rect)
            } else {
                ctx.draw(cg, in: rect)
                if overlay.ink > 1 {
                    // насыщеннее: второй multiply-проход добавляет плотность
                    ctx.setAlpha(CGFloat(min(1, overlay.ink - 1)))
                    ctx.draw(cg, in: rect)
                }
            }
            ctx.restoreGState()
        }
    }

    func previewImage(page index: Int, scale: CGFloat = 2) -> NSImage? {
        let size = pageSize(index)
        guard let ctx = CGContext(
            data: nil,
            width: Int(size.width * scale),
            height: Int(size.height * scale),
            bitsPerComponent: 8,
            bytesPerRow: 0,
            space: CGColorSpaceCreateDeviceRGB(),
            bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue
        ) else { return nil }

        ctx.scaleBy(x: scale, y: scale)
        draw(page: index, in: ctx)
        guard let cg = ctx.makeImage() else { return nil }
        return NSImage(cgImage: cg, size: size)
    }

    func pdfData() -> Data? {
        let data = NSMutableData()
        guard let consumer = CGDataConsumer(data: data as CFMutableData) else { return nil }
        var mediaBox = CGRect(origin: .zero, size: pageSize(0))
        guard let ctx = CGContext(consumer: consumer, mediaBox: &mediaBox, nil) else { return nil }

        for index in 0..<pageCount {
            var box = CGRect(origin: .zero, size: pageSize(index))
            let boxData = Data(bytes: &box, count: MemoryLayout<CGRect>.size)
            ctx.beginPDFPage([kCGPDFContextMediaBox as String: boxData] as CFDictionary)
            draw(page: index, in: ctx)
            ctx.endPDFPage()
        }
        ctx.closePDF()
        return data as Data
    }

    private static func cgImage(_ image: NSImage?) -> CGImage? {
        guard let image else { return nil }
        var rect = CGRect(origin: .zero, size: image.size)
        return image.cgImage(forProposedRect: &rect, context: nil, hints: nil)
    }
}

// MARK: - Экран «Документы»

struct DocumentSigningView: View {
    @StateObject private var store = SigningAssetStore()
    @StateObject private var projectStore = DocumentProjectStore()
    @StateObject private var richContext = RichTextContext()

    @State private var source: DocumentSource = .text
    @State private var richText = NSAttributedString()
    @State private var editVersion = 0
    @State private var pdfDocument: PDFDocument?

    @State private var currentProjectID: UUID?
    @State private var currentProjectName: String?

    @State private var sheetID: UUID?
    @State private var placements: [OverlayPlacement] = []
    @State private var selectedPlacementID: UUID?
    @State private var selectedSigner = SigningAssetStore.defaultSigner

    @State private var pageIndex = 0
    @State private var previewImage: NSImage?
    @State private var exportMessage: String?

    private var composition: DocumentComposition {
        let overlays = placements.compactMap { placement -> (OverlayPlacement, NSImage, CGFloat)? in
            guard let image = store.image(for: placement.assetID),
                  let realWidth = store.realWidthPoints(for: placement.assetID) else { return nil }
            return (placement, image, realWidth)
        }
        return DocumentComposition.make(
            source: source,
            richText: richText,
            pdfDocument: pdfDocument,
            sheet: store.image(for: sheetID),
            overlays: overlays.map { (placement: $0.0, image: $0.1, realWidthPt: $0.2) }
        )
    }

    private struct RenderKey: Hashable {
        let sourceIsPDF: Bool
        let editVersion: Int
        let sheetID: UUID?
        let placementsHash: Int
        let pageIndex: Int
        let assetCount: Int
    }

    private var renderKey: RenderKey {
        var hasher = Hasher()
        for p in placements {
            hasher.combine(p.id); hasher.combine(p.assetID); hasher.combine(p.page)
            hasher.combine(p.x); hasher.combine(p.y); hasher.combine(p.scalePercent)
            hasher.combine(p.inkPercent)
        }
        return RenderKey(
            sourceIsPDF: source.isPDF,
            editVersion: editVersion,
            sheetID: sheetID,
            placementsHash: hasher.finalize(),
            pageIndex: pageIndex,
            assetCount: store.assets.count
        )
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            HStack(spacing: 0) {
                controls
                    .frame(width: 440)
                Divider()
                preview
            }
        }
        .background(AppTheme.pageBackground)
        .task(id: renderKey) {
            try? await Task.sleep(nanoseconds: 200_000_000)
            if Task.isCancelled { return }
            renderPreview()
        }
    }

    // MARK: header

    private var header: some View {
        HStack(spacing: 14) {
            Image(systemName: "signature")
                .font(.system(size: 26, weight: .semibold))
                .foregroundStyle(AppTheme.accent)
                .frame(width: 48, height: 48)
                .background(AppTheme.cardBackground)
                .clipShape(RoundedRectangle(cornerRadius: 16))

            VStack(alignment: .leading, spacing: 4) {
                Text("Подписание документов")
                    .font(.title2.bold())
                    .foregroundStyle(AppTheme.textPrimary)
                Text("Редактор · DOCX и PDF · подпись и печать в реальном размере · итог в PDF")
                    .foregroundStyle(AppTheme.textSecondary)
            }

            Spacer()

            if let exportMessage {
                Text(exportMessage)
                    .font(.callout)
                    .foregroundStyle(AppTheme.success)
            }
        }
        .padding(24)
        .background(AppTheme.sidebarBackground)
    }

    // MARK: controls

    private var controls: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                projectsCard
                sourceCard
                if !source.isPDF {
                    sheetCard
                }
                placementsCard

                Button {
                    exportPDF()
                } label: {
                    Label("Сохранить PDF", systemImage: "square.and.arrow.down.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .controlSize(.large)
            }
            .padding(22)
        }
        .background(AppTheme.panelBackground)
    }

    // MARK: проекты

    private var projectsCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Сохранённые документы")
                .font(.headline)
                .foregroundStyle(AppTheme.textPrimary)

            HStack(spacing: 10) {
                Menu {
                    if projectStore.projects.isEmpty {
                        Text("Пока нет сохранённых документов")
                    }
                    ForEach(projectStore.projects) { project in
                        Button {
                            loadProject(project)
                        } label: {
                            Label(
                                "\(project.name) · \(project.updatedAt.formatted(date: .numeric, time: .shortened))",
                                systemImage: project.kind == "pdf" ? "doc.richtext" : "doc.text"
                            )
                        }
                    }
                    if !projectStore.projects.isEmpty {
                        Divider()
                        Menu("Удалить…") {
                            ForEach(projectStore.projects) { project in
                                Button(role: .destructive) {
                                    projectStore.delete(project)
                                    if currentProjectID == project.id {
                                        currentProjectID = nil
                                        currentProjectName = nil
                                    }
                                } label: {
                                    Text(project.name)
                                }
                            }
                        }
                    }
                } label: {
                    HStack(spacing: 8) {
                        Image(systemName: "folder")
                        Text(currentProjectName ?? "Открыть документ…")
                            .lineLimit(1)
                        Spacer(minLength: 4)
                        Image(systemName: "chevron.up.chevron.down")
                            .font(.caption)
                    }
                    .controlSurface()
                }
                .menuStyle(.button)
                .buttonStyle(.plain)

                Button {
                    saveProject()
                } label: {
                    Image(systemName: "internaldrive")
                        .fontWeight(.bold)
                        .foregroundStyle(.white)
                        .frame(width: 30, height: 30)
                        .background(AppTheme.success)
                        .clipShape(RoundedRectangle(cornerRadius: 8))
                }
                .buttonStyle(.plain)
                .help("Сохранить документ для последующего редактирования")

                Button {
                    newDocument()
                } label: {
                    Image(systemName: "doc.badge.plus")
                        .fontWeight(.bold)
                        .foregroundStyle(.white)
                        .frame(width: 30, height: 30)
                        .background(AppTheme.brand)
                        .clipShape(RoundedRectangle(cornerRadius: 8))
                }
                .buttonStyle(.plain)
                .help("Новый пустой документ")
            }
        }
        .brandCard()
    }

    // MARK: документ

    private var sourceCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Документ")
                    .font(.headline)
                    .foregroundStyle(AppTheme.textPrimary)
                Spacer()
                Button {
                    importDocument()
                } label: {
                    Label("Импорт DOCX / PDF…", systemImage: "square.and.arrow.down.on.square")
                        .font(.callout.weight(.semibold))
                }
                .buttonStyle(.borderedProminent)
                .tint(AppTheme.brand)
            }

            if case .pdf(let name) = source {
                HStack(spacing: 8) {
                    Image(systemName: "doc.richtext")
                        .foregroundStyle(AppTheme.info)
                    VStack(alignment: .leading, spacing: 2) {
                        Text(name)
                            .fontWeight(.medium)
                            .foregroundStyle(AppTheme.textPrimary)
                        Text("PDF: текст не редактируется, доступно подписание")
                            .font(.caption)
                            .foregroundStyle(AppTheme.textMuted)
                    }
                    Spacer()
                    Button("Закрыть PDF") {
                        newDocument()
                    }
                }
                .padding(10)
                .background(AppTheme.brandSoft.opacity(0.4))
                .clipShape(RoundedRectangle(cornerRadius: 8))
            } else {
                formatToolbar
                RichTextEditor(text: $richText, context: richContext) {
                    editVersion += 1
                }
                .frame(minHeight: 240)
                .clipShape(RoundedRectangle(cornerRadius: 8))
                .overlay(RoundedRectangle(cornerRadius: 8).stroke(AppTheme.cardStroke))
            }
        }
        .brandCard()
    }

    private var formatToolbar: some View {
        HStack(spacing: 6) {
            formatButton("bold") { richContext.toggleBold() }
            formatButton("italic") { richContext.toggleItalic() }
            formatButton("underline") { richContext.toggleUnderline() }
            Divider().frame(height: 18)
            formatButton("textformat.size.smaller") { richContext.changeFontSize(by: -1) }
            formatButton("textformat.size.larger") { richContext.changeFontSize(by: 1) }
            Divider().frame(height: 18)
            formatButton("text.alignleft") { richContext.alignLeft() }
            formatButton("text.aligncenter") { richContext.alignCenter() }
            formatButton("text.justify") { richContext.alignJustified() }
            Spacer()
        }
        .padding(6)
        .background(AppTheme.brandSoft.opacity(0.45))
        .clipShape(RoundedRectangle(cornerRadius: 8))
    }

    private func formatButton(_ icon: String, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Image(systemName: icon)
                .frame(width: 26, height: 24)
                .foregroundStyle(AppTheme.brand)
                .background(.white)
                .clipShape(RoundedRectangle(cornerRadius: 5))
        }
        .buttonStyle(.plain)
    }

    // MARK: чистый лист

    private var sheetCard: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Чистый лист (фон)")
                .font(.headline)
                .foregroundStyle(AppTheme.textPrimary)

            HStack(spacing: 10) {
                Menu {
                    Button("Белый фон") { sheetID = nil }
                    Divider()
                    ForEach(store.assets(of: .sheet)) { asset in
                        Button {
                            sheetID = asset.id
                        } label: {
                            menuItemLabel(asset)
                        }
                    }
                } label: {
                    HStack(spacing: 8) {
                        Image(systemName: "doc.plaintext")
                        Text(store.asset(id: sheetID)?.name ?? "Белый фон")
                            .lineLimit(1)
                        Spacer(minLength: 4)
                        Image(systemName: "chevron.up.chevron.down")
                            .font(.caption)
                    }
                    .controlSurface()
                }
                .menuStyle(.button)
                .buttonStyle(.plain)

                Button {
                    if let added = store.importAsset(kind: .sheet) {
                        sheetID = added.id
                    }
                } label: {
                    Image(systemName: "plus")
                        .fontWeight(.bold)
                        .foregroundStyle(.white)
                        .frame(width: 30, height: 30)
                        .background(AppTheme.brand)
                        .clipShape(RoundedRectangle(cornerRadius: 8))
                }
                .buttonStyle(.plain)
                .help("Добавить скан листа")
            }
        }
        .brandCard()
    }

    private func menuItemLabel(_ asset: SigningAsset) -> some View {
        Label {
            Text(asset.name)
        } icon: {
            if let thumb = store.thumbnail(for: asset.id) {
                Image(nsImage: thumb)
            } else {
                Image(systemName: asset.kind == .stamp ? "seal" : "signature")
            }
        }
    }

    // MARK: подписи и печати

    private var placementsCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Подписи и печати")
                .font(.headline)
                .foregroundStyle(AppTheme.textPrimary)

            HStack(spacing: 10) {
                Menu {
                    ForEach(store.signers, id: \.self) { signer in
                        Button {
                            selectedSigner = signer
                        } label: {
                            if signer == selectedSigner {
                                Label(signer, systemImage: "checkmark")
                            } else {
                                Text(signer)
                            }
                        }
                    }
                    Divider()
                    Button("Добавить подписанта…") {
                        addSigner()
                    }
                } label: {
                    HStack(spacing: 8) {
                        Image(systemName: "person.crop.circle")
                        Text(selectedSigner)
                            .lineLimit(1)
                        Spacer(minLength: 4)
                        Image(systemName: "chevron.up.chevron.down")
                            .font(.caption)
                    }
                    .controlSurface()
                }
                .menuStyle(.button)
                .buttonStyle(.plain)
            }

            HStack(spacing: 8) {
                addMenu(kind: .signature, title: "Подпись")
                addMenu(kind: .stamp, title: "Печать")
            }

            Text("Добавляется на текущую страницу (стр. \(pageIndex + 1)). Перетаскивайте мышью по предпросмотру.")
                .font(.caption)
                .foregroundStyle(AppTheme.textMuted)

            if placements.isEmpty {
                Text("Пока ничего не добавлено")
                    .font(.callout)
                    .foregroundStyle(AppTheme.textMuted)
            }

            ForEach(placements) { placement in
                placementRow(placement)
            }
        }
        .brandCard()
    }

    private func addSigner() {
        let alert = NSAlert()
        alert.messageText = "Новый подписант"
        alert.informativeText = "Например: Почепа О.А. Импортируемые далее подписи и печати будут привязаны к нему."
        alert.addButton(withTitle: "Добавить")
        alert.addButton(withTitle: "Отмена")
        let field = NSTextField(frame: NSRect(x: 0, y: 0, width: 260, height: 24))
        alert.accessoryView = field
        guard alert.runModal() == .alertFirstButtonReturn else { return }
        let name = field.stringValue.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !name.isEmpty else { return }
        AppLog.info("Добавлен подписант: \(name)")
        selectedSigner = name
    }

    private func addMenu(kind: SigningAssetKind, title: String) -> some View {
        Menu {
            ForEach(store.assets(of: kind, signer: selectedSigner)) { asset in
                Button {
                    let placement = OverlayPlacement(
                        assetID: asset.id,
                        page: pageIndex,
                        x: kind == .stamp ? 0.38 : 0.68,
                        y: 0.82
                    )
                    AppLog.info("Добавлено наложение: \(asset.name) на стр. \(pageIndex + 1)")
                    placements.append(placement)
                    selectedPlacementID = placement.id
                } label: {
                    menuItemLabel(asset)
                }
            }
            Divider()
            Button("Импортировать новый скан…") {
                if let added = store.importAsset(kind: kind, signer: selectedSigner) {
                    let placement = OverlayPlacement(assetID: added.id, page: pageIndex)
                    placements.append(placement)
                    selectedPlacementID = placement.id
                }
            }
        } label: {
            HStack(spacing: 7) {
                Image(systemName: "plus.circle.fill")
                Text(title)
                    .fontWeight(.semibold)
            }
            .foregroundStyle(.white)
            .padding(.horizontal, 16)
            .padding(.vertical, 9)
            .background(kind == .stamp ? AppTheme.info : AppTheme.accent)
            .clipShape(Capsule())
        }
        .menuStyle(.button)
        .buttonStyle(.plain)
    }

    private func placementRow(_ placement: OverlayPlacement) -> some View {
        let isSelected = placement.id == selectedPlacementID
        let asset = store.asset(id: placement.assetID)
        let realWidth = store.realWidthPoints(for: placement.assetID) ?? 0
        let widthMM = realWidth * CGFloat(placement.scalePercent / 100) * 25.4 / 72

        return VStack(alignment: .leading, spacing: 6) {
            HStack {
                Button {
                    selectedPlacementID = placement.id
                    pageIndex = min(placement.page, max(0, composition.pageCount - 1))
                } label: {
                    HStack(spacing: 6) {
                        Image(systemName: asset?.kind == .stamp ? "seal" : "signature")
                        Text(asset?.name ?? "—")
                            .lineLimit(1)
                    }
                }
                .buttonStyle(.plain)
                .foregroundStyle(isSelected ? AppTheme.accent : AppTheme.textPrimary)

                if let thumb = store.thumbnail(for: placement.assetID, height: 30) {
                    Image(nsImage: thumb)
                        .resizable()
                        .scaledToFit()
                        .frame(height: 30)
                        .background(.white)
                        .clipShape(RoundedRectangle(cornerRadius: 5))
                        .overlay(RoundedRectangle(cornerRadius: 5).stroke(AppTheme.cardStroke))
                }

                Spacer()

                Text("стр. \(placement.page + 1) · \(Int(widthMM)) мм")
                    .font(.caption)
                    .foregroundStyle(AppTheme.textMuted)

                Button {
                    removePlacement(id: placement.id)
                } label: {
                    Image(systemName: "trash")
                }
                .buttonStyle(.plain)
                .foregroundStyle(AppTheme.textMuted)
            }

            if isSelected {
                Stepper("Страница: \(placement.page + 1)",
                        value: placementBinding(placement.id, \.page, fallback: placement.page),
                        in: 0...max(0, composition.pageCount - 1))
                    .font(.caption)
                labeledSlider("Размер, %",
                              value: placementBinding(placement.id, \.scalePercent, fallback: placement.scalePercent),
                              range: 50...150)
                labeledSlider("Цветность, %",
                              value: placementBinding(placement.id, \.inkPercent, fallback: placement.inkPercent),
                              range: 40...180)
            }
        }
        .padding(8)
        .background(isSelected ? AppTheme.brandSoft.opacity(0.5) : Color.clear)
        .clipShape(RoundedRectangle(cornerRadius: 8))
    }

    /// Безопасный биндинг по id: после удаления строки чтение не приводит
    /// к выходу за границы массива (причина прошлого краша).
    private func placementBinding<T>(
        _ id: UUID,
        _ keyPath: WritableKeyPath<OverlayPlacement, T>,
        fallback: T
    ) -> Binding<T> {
        Binding(
            get: {
                placements.first(where: { $0.id == id })?[keyPath: keyPath] ?? fallback
            },
            set: { newValue in
                guard let index = placements.firstIndex(where: { $0.id == id }) else {
                    AppLog.warn("placementBinding: элемент \(id) уже удалён, запись игнорируется")
                    return
                }
                placements[index][keyPath: keyPath] = newValue
            }
        )
    }

    private func removePlacement(id: UUID) {
        let name = store.asset(id: placements.first(where: { $0.id == id })?.assetID)?.name ?? "?"
        AppLog.info("Удаление наложения: \(name) (\(id))")
        if selectedPlacementID == id { selectedPlacementID = nil }
        withAnimation {
            placements.removeAll { $0.id == id }
        }
    }

    private func labeledSlider(_ label: String, value: Binding<Double>, range: ClosedRange<Double>) -> some View {
        HStack {
            Text(label)
                .font(.caption)
                .foregroundStyle(AppTheme.textSecondary)
                .frame(width: 90, alignment: .leading)
            Slider(value: value, in: range)
        }
    }

    // MARK: preview

    private var preview: some View {
        VStack(spacing: 10) {
            GeometryReader { geo in
                ZStack {
                    if let previewImage {
                        let pageSize = composition.pageSize(pageIndex)
                        let fit = fitRect(content: pageSize, in: geo.size)
                        Image(nsImage: previewImage)
                            .resizable()
                            .frame(width: fit.width, height: fit.height)
                            .position(x: fit.midX, y: fit.midY)
                            .shadow(color: .black.opacity(0.25), radius: 12, y: 5)
                            .gesture(
                                DragGesture(minimumDistance: 0)
                                    .onChanged { value in
                                        moveSelectedPlacement(to: value.location, fit: fit)
                                    }
                            )
                    } else {
                        Text("Предпросмотр документа")
                            .foregroundStyle(AppTheme.textMuted)
                    }
                }
                .frame(width: geo.size.width, height: geo.size.height)
            }
            .padding(16)

            pageBar
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(AppTheme.pageBackground)
    }

    private var pageBar: some View {
        HStack(spacing: 14) {
            Button {
                pageIndex = max(0, pageIndex - 1)
            } label: {
                Image(systemName: "chevron.left")
            }
            .disabled(pageIndex == 0)

            Text("Страница \(pageIndex + 1) из \(composition.pageCount)")
                .font(.callout)
                .foregroundStyle(AppTheme.textSecondary)

            Button {
                pageIndex = min(composition.pageCount - 1, pageIndex + 1)
            } label: {
                Image(systemName: "chevron.right")
            }
            .disabled(pageIndex >= composition.pageCount - 1)
        }
        .padding(.bottom, 14)
    }

    private func fitRect(content: CGSize, in container: CGSize) -> CGRect {
        guard content.width > 0, content.height > 0 else { return .zero }
        let scale = min(container.width / content.width, container.height / content.height)
        let size = CGSize(width: content.width * scale, height: content.height * scale)
        return CGRect(
            x: (container.width - size.width) / 2,
            y: (container.height - size.height) / 2,
            width: size.width,
            height: size.height
        )
    }

    private func moveSelectedPlacement(to point: CGPoint, fit: CGRect) {
        guard let id = selectedPlacementID,
              let index = placements.firstIndex(where: { $0.id == id }),
              placements[index].page == pageIndex,
              fit.width > 0, fit.height > 0 else { return }
        placements[index].x = min(1, max(0, Double((point.x - fit.minX) / fit.width)))
        placements[index].y = min(1, max(0, Double((point.y - fit.minY) / fit.height)))
    }

    private func renderPreview() {
        let comp = composition
        let clamped = min(pageIndex, comp.pageCount - 1)
        if clamped != pageIndex { pageIndex = clamped }
        previewImage = comp.previewImage(page: clamped)
        if previewImage == nil {
            AppLog.error("Предпросмотр: не удалось отрисовать страницу \(clamped + 1) из \(comp.pageCount)")
        }
    }

    // MARK: документы: новый / импорт / сохранение / загрузка

    private func newDocument() {
        AppLog.info("Новый документ (сброс состояния)")
        source = .text
        richText = NSAttributedString()
        pdfDocument = nil
        placements = []
        selectedPlacementID = nil
        currentProjectID = nil
        currentProjectName = nil
        pageIndex = 0
        editVersion += 1
    }

    private func importDocument() {
        let panel = NSOpenPanel()
        var types: [UTType] = [.pdf]
        if let docx = UTType(filenameExtension: "docx") { types.append(docx) }
        if let rtf = UTType(filenameExtension: "rtf") { types.append(rtf) }
        panel.allowedContentTypes = types
        panel.allowsMultipleSelection = false
        panel.message = "Выберите документ DOCX, RTF или PDF"
        guard panel.runModal() == .OK, let url = panel.url else { return }
        AppLog.info("Импорт документа: \(url.lastPathComponent)")

        pageIndex = 0
        currentProjectID = nil
        currentProjectName = url.deletingPathExtension().lastPathComponent

        if url.pathExtension.lowercased() == "pdf" {
            if let doc = PDFDocument(url: url) {
                AppLog.info("PDF открыт: \(doc.pageCount) стр.")
                pdfDocument = doc
                source = .pdf(name: url.lastPathComponent)
                editVersion += 1
            } else {
                AppLog.error("Не удалось открыть PDF: \(url.path)")
                exportMessage = "Не удалось открыть PDF"
            }
            return
        }

        let docType: NSAttributedString.DocumentType =
            url.pathExtension.lowercased() == "rtf" ? .rtf : .officeOpenXML
        do {
            let attributed = try NSAttributedString(
                url: url,
                options: [.documentType: docType],
                documentAttributes: nil
            )
            let mutable = NSMutableAttributedString(attributedString: attributed)
            mutable.addAttribute(.foregroundColor, value: NSColor.black,
                                 range: NSRange(location: 0, length: mutable.length))
            AppLog.info("Текстовый документ прочитан: \(mutable.length) символов")
            richText = mutable
            pdfDocument = nil
            source = .text
            editVersion += 1
        } catch {
            AppLog.error("Не удалось прочитать документ \(url.lastPathComponent)", error)
            exportMessage = "Не удалось прочитать документ: \(error.localizedDescription)"
        }
    }

    private func saveProject() {
        var name = currentProjectName ?? ""
        if currentProjectID == nil {
            guard let entered = promptForName(default: name.isEmpty ? "Документ" : name) else { return }
            name = entered
        }

        let saved: DocumentProject?
        if case .pdf = source, let pdf = pdfDocument {
            saved = projectStore.savePDF(name: name, existingID: currentProjectID, pdf: pdf,
                                         sheetID: sheetID, placements: placements)
        } else {
            saved = projectStore.saveText(name: name, existingID: currentProjectID, text: richText,
                                          sheetID: sheetID, placements: placements)
        }

        if let saved {
            currentProjectID = saved.id
            currentProjectName = saved.name
            exportMessage = "Документ сохранён: \(saved.name)"
        } else {
            AppLog.error("Сохранение документа «\(name)» не удалось")
            exportMessage = "Не удалось сохранить документ"
        }
    }

    private func loadProject(_ project: DocumentProject) {
        AppLog.info("Открытие сохранённого документа: «\(project.name)» (\(project.kind))")
        if project.kind == "pdf" {
            guard let pdf = projectStore.loadPDF(project) else {
                AppLog.error("Не удалось загрузить PDF проекта «\(project.name)»: \(project.fileName)")
                exportMessage = "Не удалось открыть документ"
                return
            }
            pdfDocument = pdf
            source = .pdf(name: project.name)
        } else {
            guard let text = projectStore.loadText(project) else {
                AppLog.error("Не удалось загрузить RTF проекта «\(project.name)»: \(project.fileName)")
                exportMessage = "Не удалось открыть документ"
                return
            }
            richText = text
            pdfDocument = nil
            source = .text
        }
        sheetID = project.sheetID
        placements = project.placements
        selectedPlacementID = nil
        currentProjectID = project.id
        currentProjectName = project.name
        pageIndex = 0
        editVersion += 1
    }

    private func promptForName(default defaultName: String) -> String? {
        let alert = NSAlert()
        alert.messageText = "Название документа"
        alert.informativeText = "Под этим именем документ появится в списке сохранённых."
        alert.addButton(withTitle: "Сохранить")
        alert.addButton(withTitle: "Отмена")
        let field = NSTextField(frame: NSRect(x: 0, y: 0, width: 260, height: 24))
        field.stringValue = defaultName
        alert.accessoryView = field
        guard alert.runModal() == .alertFirstButtonReturn else { return nil }
        let name = field.stringValue.trimmingCharacters(in: .whitespacesAndNewlines)
        return name.isEmpty ? "Документ" : name
    }

    private func exportPDF() {
        guard let data = composition.pdfData() else {
            AppLog.error("Экспорт PDF: не удалось сформировать данные")
            exportMessage = "Не удалось сформировать PDF"
            return
        }

        let panel = NSSavePanel()
        panel.allowedContentTypes = [.pdf]
        let baseName = currentProjectName ?? "Документ"
        panel.nameFieldStringValue = baseName + ".pdf"
        guard panel.runModal() == .OK, let url = panel.url else { return }

        do {
            try data.write(to: url)
            AppLog.info("PDF экспортирован: \(url.path) (\(data.count) байт)")
            exportMessage = "Сохранено: \(url.lastPathComponent)"
        } catch {
            AppLog.error("Экспорт PDF: ошибка записи в \(url.path)", error)
            exportMessage = "Ошибка сохранения: \(error.localizedDescription)"
        }
    }
}

private extension View {
    /// Заметная поверхность для элементов выбора: белый фон + рамка + тёмный текст
    func controlSurface() -> some View {
        self
            .font(.body.weight(.medium))
            .foregroundStyle(AppTheme.textPrimary)
            .padding(.horizontal, 12)
            .padding(.vertical, 8)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(.white)
            .clipShape(RoundedRectangle(cornerRadius: 8))
            .overlay(
                RoundedRectangle(cornerRadius: 8)
                    .stroke(AppTheme.brand.opacity(0.5), lineWidth: 1.5)
            )
    }
}
