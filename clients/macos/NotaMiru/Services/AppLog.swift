import Foundation
import os

/// Единый логгер приложения: пишет в unified log (Console.app)
/// и дублирует в файл ~/Library/Application Support/NotaMiru/Logs/notamiru.log,
/// чтобы можно было быстро разобрать любую проблему.
enum AppLog {
    enum Level: String {
        case debug = "DEBUG"
        case info = "INFO"
        case warn = "WARN"
        case error = "ERROR"
    }

    private static let osLogger = Logger(subsystem: "ru.notame.NotaMiru", category: "app")

    private static let queue = DispatchQueue(label: "ru.notame.NotaMiru.log", qos: .utility)

    private static let dateFormatter: DateFormatter = {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd HH:mm:ss.SSS"
        f.locale = Locale(identifier: "en_US_POSIX")
        return f
    }()

    private static let fileURL: URL = {
        let base = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first
            ?? FileManager.default.temporaryDirectory
        let dir = base.appendingPathComponent("NotaMiru/Logs", isDirectory: true)
        try? FileManager.default.createDirectory(at: dir, withIntermediateDirectories: true)
        return dir.appendingPathComponent("notamiru.log")
    }()

    private static let maxFileSize = 2 * 1024 * 1024 // 2 МБ, потом ротация в .old

    static func debug(_ message: String, source: String = #fileID, line: Int = #line) {
        write(.debug, message, source: source, line: line)
    }

    static func info(_ message: String, source: String = #fileID, line: Int = #line) {
        write(.info, message, source: source, line: line)
    }

    static func warn(_ message: String, source: String = #fileID, line: Int = #line) {
        write(.warn, message, source: source, line: line)
    }

    static func error(_ message: String, source: String = #fileID, line: Int = #line) {
        write(.error, message, source: source, line: line)
    }

    static func error(_ message: String, _ err: Error, source: String = #fileID, line: Int = #line) {
        write(.error, "\(message): \(err.localizedDescription)", source: source, line: line)
    }

    /// Путь к файлу лога — показывается в настройках/для диагностики
    static var logFilePath: String { fileURL.path }

    private static func write(_ level: Level, _ message: String, source: String, line: Int) {
        let shortSource = source.split(separator: "/").last.map(String.init) ?? source
        let composed = "[\(shortSource):\(line)] \(message)"

        switch level {
        case .debug: osLogger.debug("\(composed, privacy: .public)")
        case .info: osLogger.info("\(composed, privacy: .public)")
        case .warn: osLogger.warning("\(composed, privacy: .public)")
        case .error: osLogger.error("\(composed, privacy: .public)")
        }

        let lineText = "\(dateFormatter.string(from: Date())) \(level.rawValue.padding(toLength: 5, withPad: " ", startingAt: 0)) \(composed)\n"
        queue.async {
            appendToFile(lineText)
        }
    }

    private static func appendToFile(_ text: String) {
        rotateIfNeeded()
        guard let data = text.data(using: .utf8) else { return }
        if let handle = try? FileHandle(forWritingTo: fileURL) {
            defer { try? handle.close() }
            _ = try? handle.seekToEnd()
            try? handle.write(contentsOf: data)
        } else {
            try? data.write(to: fileURL)
        }
    }

    private static func rotateIfNeeded() {
        guard let size = (try? FileManager.default.attributesOfItem(atPath: fileURL.path)[.size]) as? Int,
              size > maxFileSize else { return }
        let old = fileURL.deletingPathExtension().appendingPathExtension("old.log")
        try? FileManager.default.removeItem(at: old)
        try? FileManager.default.moveItem(at: fileURL, to: old)
    }
}
