import SwiftUI

enum AppTheme {
    static let brand = Color(red: 0.08, green: 0.23, blue: 0.52)
    static let accent = Color(red: 0.82, green: 0.06, blue: 0.02)
    static let brandSoft = Color(red: 0.82, green: 0.88, blue: 0.98)
    static let pageBackground = Color(red: 0.91, green: 0.94, blue: 0.98)
    static let panelBackground = Color(red: 0.98, green: 0.99, blue: 1.00)
    static let sidebarBackground = Color(red: 0.86, green: 0.91, blue: 0.98)
    static let cardBackground = Color(red: 1.00, green: 1.00, blue: 1.00)
    static let cardStroke = Color(red: 0.10, green: 0.20, blue: 0.38).opacity(0.22)
    static let textPrimary = Color(red: 0.06, green: 0.08, blue: 0.12)
    static let textSecondary = Color(red: 0.27, green: 0.32, blue: 0.42)
    static let textMuted = Color(red: 0.43, green: 0.48, blue: 0.57)
    static let success = Color(red: 0.02, green: 0.43, blue: 0.19)
    static let warning = Color(red: 0.72, green: 0.35, blue: 0.00)
    static let info = Color(red: 0.05, green: 0.30, blue: 0.68)
}

struct BrandCard: ViewModifier {
    func body(content: Content) -> some View {
        content
            .padding(16)
            .background(AppTheme.cardBackground)
            .foregroundStyle(AppTheme.textPrimary)
            .shadow(color: Color.black.opacity(0.10), radius: 14, x: 0, y: 6)
            .overlay(
                RoundedRectangle(cornerRadius: 12)
                    .stroke(AppTheme.cardStroke, lineWidth: 1)
            )
            .clipShape(RoundedRectangle(cornerRadius: 12))
    }
}

extension View {
    func brandCard() -> some View {
        modifier(BrandCard())
    }

    func lightEditorSurface(cornerRadius: CGFloat = 8) -> some View {
        self
            .scrollContentBackground(.hidden)
            .padding(8)
            .background(AppTheme.cardBackground)
            .foregroundStyle(AppTheme.textPrimary)
            .clipShape(RoundedRectangle(cornerRadius: cornerRadius))
            .overlay(RoundedRectangle(cornerRadius: cornerRadius).stroke(AppTheme.cardStroke))
    }

    func lightFieldSurface(cornerRadius: CGFloat = 6) -> some View {
        self
            .textFieldStyle(.plain)
            .padding(.horizontal, 8)
            .padding(.vertical, 5)
            .background(AppTheme.cardBackground)
            .foregroundStyle(AppTheme.textPrimary)
            .clipShape(RoundedRectangle(cornerRadius: cornerRadius))
            .overlay(RoundedRectangle(cornerRadius: cornerRadius).stroke(AppTheme.cardStroke))
    }
}
