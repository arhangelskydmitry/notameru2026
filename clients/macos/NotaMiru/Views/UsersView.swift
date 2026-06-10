import SwiftUI

struct UsersView: View {
    @EnvironmentObject private var appState: AppState
    @State private var selectedUserId: StaffUser.ID?
    @State private var showingCreateUser = false

    private var selectedUser: StaffUser? {
        guard let selectedUserId else { return appState.staffUsers.first }
        return appState.staffUsers.first(where: { $0.id == selectedUserId })
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 0) {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Сотрудники")
                        .font(.title2.bold())
                        .foregroundStyle(AppTheme.textPrimary)
                    Text("Управление авторами, ролями и доступом к редакционному клиенту.")
                        .font(.caption)
                        .foregroundStyle(AppTheme.textSecondary)
                }
                Spacer()
                Button {
                    showingCreateUser = true
                } label: {
                    Label("Новый сотрудник", systemImage: "person.badge.plus")
                }
                .buttonStyle(.borderedProminent)
                .tint(AppTheme.brand)
                Button("Обновить") {
                    Task { await appState.reloadStaffUsers() }
                }
            }
            .padding()
            .background(AppTheme.panelBackground)

            HStack(spacing: 0) {
                StaffListView(users: appState.staffUsers, selectedUserId: $selectedUserId)
                    .frame(minWidth: 680)

                Divider()

                StaffDetailView(user: selectedUser)
                    .frame(width: 340)
            }
        }
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
        .sheet(isPresented: $showingCreateUser) {
            CreateStaffUserSheet(isPresented: $showingCreateUser) { created in
                selectedUserId = created.id
            }
            .environmentObject(appState)
        }
        .task {
            await appState.reloadStaffUsers()
            selectedUserId = selectedUserId ?? appState.staffUsers.first?.id
        }
        .onChange(of: appState.staffUsers) { users in
            if selectedUserId == nil || !users.contains(where: { $0.id == selectedUserId }) {
                selectedUserId = users.first?.id
            }
        }
    }
}

private struct StaffListView: View {
    let users: [StaffUser]
    @Binding var selectedUserId: StaffUser.ID?

    var body: some View {
        VStack(spacing: 0) {
            HStack(spacing: 12) {
                Text("Имя").frame(maxWidth: .infinity, alignment: .leading)
                Text("Логин").frame(width: 120, alignment: .leading)
                Text("Должность").frame(width: 160, alignment: .leading)
                Text("Роль").frame(width: 140, alignment: .leading)
                Text("Карта").frame(width: 120, alignment: .leading)
                Text("Активен").frame(width: 74, alignment: .center)
            }
            .font(.caption.bold())
            .foregroundStyle(AppTheme.textSecondary)
            .padding(.horizontal, 14)
            .padding(.vertical, 10)
            .background(AppTheme.brandSoft)

            ScrollView {
                LazyVStack(spacing: 8) {
                    ForEach(users) { user in
                        StaffRowView(user: user, isSelected: selectedUserId == user.id)
                            .onTapGesture { selectedUserId = user.id }
                    }
                }
                .padding(12)
            }
            .background(AppTheme.pageBackground)
        }
    }
}

private struct StaffRowView: View {
    @EnvironmentObject private var appState: AppState
    let user: StaffUser
    let isSelected: Bool

    var body: some View {
        HStack(spacing: 12) {
            VStack(alignment: .leading, spacing: 2) {
                Text(user.name)
                    .font(.headline)
                    .foregroundStyle(AppTheme.textPrimary)
                Text(user.email)
                    .font(.caption)
                    .foregroundStyle(AppTheme.textSecondary)
            }
            .frame(maxWidth: .infinity, alignment: .leading)

            Text(user.login ?? "—")
                .frame(width: 120, alignment: .leading)
            Text(user.position ?? "—")
                .frame(width: 160, alignment: .leading)
            Text(user.roleLabel ?? user.role ?? "—")
                .frame(width: 140, alignment: .leading)
            Text(user.pressCard?.cardNumber ?? "—")
                .frame(width: 120, alignment: .leading)
            Toggle("", isOn: Binding(
                get: { user.active },
                set: { newValue in
                    Task {
                        do {
                            try await appState.setUserActive(id: user.id, active: newValue)
                        } catch {
                            appState.errorMessage = error.localizedDescription
                        }
                    }
                }
            ))
            .toggleStyle(.switch)
            .labelsHidden()
            .frame(width: 74)
        }
        .font(.callout)
        .padding(12)
        .background(isSelected ? AppTheme.brandSoft : AppTheme.cardBackground)
        .clipShape(RoundedRectangle(cornerRadius: 12))
        .overlay(RoundedRectangle(cornerRadius: 12).stroke(isSelected ? AppTheme.brand.opacity(0.45) : AppTheme.cardStroke))
        .contentShape(Rectangle())
    }
}

private struct CreateStaffUserSheet: View {
    @EnvironmentObject private var appState: AppState
    @Binding var isPresented: Bool
    var onCreated: (StaffUser) -> Void

    @State private var name = ""
    @State private var email = ""
    @State private var login = ""
    @State private var role = "author"
    @State private var position = "Автор"
    @State private var password = ""
    @State private var active = true
    @State private var isSaving = false

    private let roles: [(id: String, label: String, defaultPosition: String)] = [
        ("author", "Автор", "Автор"),
        ("editor", "Главный редактор", "Главный редактор"),
        ("super_admin", "Суперадминистратор", "Суперадминистратор"),
    ]

    var body: some View {
        VStack(alignment: .leading, spacing: 18) {
            Text("Новый сотрудник")
                .font(.title2.bold())
                .foregroundStyle(AppTheme.textPrimary)

            VStack(alignment: .leading, spacing: 10) {
                TextField("Имя", text: $name)
                    .lightFieldSurface()
                TextField("Email", text: $email)
                    .lightFieldSurface()
                TextField("Логин", text: $login)
                    .lightFieldSurface()
                Picker("Роль", selection: $role) {
                    ForEach(roles, id: \.id) { item in
                        Text(item.label).tag(item.id)
                    }
                }
                .onChange(of: role) { newRole in
                    if let item = roles.first(where: { $0.id == newRole }) {
                        position = item.defaultPosition
                    }
                }
                TextField("Должность", text: $position)
                    .lightFieldSurface()
                SecureField("Пароль для входа (необязательно)", text: $password)
                    .lightFieldSurface()
                Toggle("Аккаунт активен", isOn: $active)
            }

            HStack {
                Button("Отмена") { isPresented = false }
                Spacer()
                Button {
                    Task { await createUser() }
                } label: {
                    if isSaving {
                        ProgressView().controlSize(.small)
                    } else {
                        Text("Создать")
                    }
                }
                .buttonStyle(.borderedProminent)
                .tint(AppTheme.brand)
                .disabled(isSaving || name.isEmpty || email.isEmpty || login.isEmpty)
            }
        }
        .padding(24)
        .frame(width: 420)
        .background(AppTheme.pageBackground)
        .foregroundStyle(AppTheme.textPrimary)
    }

    private func createUser() async {
        isSaving = true
        defer { isSaving = false }
        do {
            let created = try await appState.createStaffUser(StaffUserInput(
                name: name,
                email: email,
                login: login,
                role: role,
                position: position,
                password: password,
                active: active
            ))
            onCreated(created)
            isPresented = false
        } catch {
            appState.errorMessage = error.localizedDescription
        }
    }
}

private struct StaffDetailView: View {
    let user: StaffUser?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                if let user {
                    VStack(alignment: .leading, spacing: 6) {
                        Text(user.name)
                            .font(.title3.bold())
                            .foregroundStyle(AppTheme.textPrimary)
                        Text(user.email)
                            .foregroundStyle(AppTheme.textSecondary)
                        LabeledContent("Логин", value: user.login ?? "—")
                        LabeledContent("Slug автора", value: user.slug ?? "—")
                        LabeledContent("Роль", value: user.roleLabel ?? user.role ?? "—")
                        LabeledContent("Должность", value: user.position ?? "—")
                    }
                    .padding()
                    .background(AppTheme.cardBackground)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                    .overlay(RoundedRectangle(cornerRadius: 12).stroke(AppTheme.cardStroke))

                    VStack(alignment: .leading, spacing: 8) {
                        Text("Пресс-карта")
                            .font(.headline)
                            .foregroundStyle(AppTheme.textPrimary)
                        if let card = user.pressCard {
                            LabeledContent("Номер", value: card.cardNumber)
                            LabeledContent("Статус", value: card.statusLabel)
                            LabeledContent("Действует до", value: card.expiresAt ?? "—")
                            if let rawUrl = card.verifyUrl, let url = URL(string: rawUrl) {
                                Link("Открыть страницу проверки", destination: url)
                            }
                        } else {
                            Text("Активной пресс-карты нет")
                                .foregroundStyle(AppTheme.textSecondary)
                        }
                    }
                    .padding()
                    .background(AppTheme.cardBackground)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                    .overlay(RoundedRectangle(cornerRadius: 12).stroke(AppTheme.cardStroke))

                    VStack(alignment: .leading, spacing: 8) {
                        Text("Статистика автора")
                            .font(.headline)
                            .foregroundStyle(AppTheme.textPrimary)
                        if let stats = user.statistics {
                            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], alignment: .leading, spacing: 8) {
                                StatTile(title: "Всего", value: "\(stats.totalPosts)")
                                StatTile(title: "Опубликовано", value: "\(stats.publishedPosts)")
                                StatTile(title: "Черновики", value: "\(stats.draftPosts)")
                                StatTile(title: "За месяц", value: "\(stats.thisMonthPosts)")
                                StatTile(title: "За неделю", value: "\(stats.thisWeekPosts)")
                                StatTile(title: "Просмотры", value: "\(stats.totalViews)")
                            }
                            LabeledContent("Комментарии", value: "\(stats.totalComments)")
                            LabeledContent("Последняя публикация", value: stats.lastPostDate ?? "—")
                        } else {
                            Text("Статистика пока не рассчитана")
                                .foregroundStyle(AppTheme.textSecondary)
                        }
                    }
                    .padding()
                    .background(AppTheme.cardBackground)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                    .overlay(RoundedRectangle(cornerRadius: 12).stroke(AppTheme.cardStroke))
                } else {
                    Text("Выберите сотрудника")
                        .foregroundStyle(AppTheme.textSecondary)
                }
            }
            .padding()
        }
        .background(AppTheme.pageBackground)
    }
}

private struct StatTile: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(value)
                .font(.headline)
                .foregroundStyle(AppTheme.textPrimary)
            Text(title)
                .font(.caption)
                .foregroundStyle(AppTheme.textSecondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(8)
        .background(AppTheme.brandSoft)
        .clipShape(RoundedRectangle(cornerRadius: 8))
        .overlay(RoundedRectangle(cornerRadius: 8).stroke(AppTheme.cardStroke))
    }
}
