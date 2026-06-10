import fs from "node:fs";
import path from "node:path";
import { Bot, InputFile, type Context } from "grammy";
import { InlineKeyboard } from "grammy";
import type { AppConfig } from "../config.js";
import { isAllowedTelegramUser, isTelegramApprover } from "../auth/telegramAccess.js";
import { formatAllowedCommands } from "../tasks/commandPolicy.js";
import type { TaskStore } from "../tasks/types.js";
import type { TaskRunner } from "../tasks/taskRunner.js";
import { transcribeTelegramVoice } from "../voice/voiceTranscriber.js";
import type { AgentRunner } from "../agent/agentRunner.js";
import type { ApprovalStore, PendingApproval } from "../approvals/approvalStore.js";
import { decideMessage } from "../chat/messageIntent.js";
import type { EventLog } from "../storage/eventLog.js";
import type { ChatMemoryStore } from "../chat/chatMemory.js";
import type { ConversationLogStore } from "../chat/conversationLog.js";
import type { SessionStore } from "../collab/sessionStore.js";
import {
  formatKanban,
  type KanbanColumn,
  type KanbanStore
} from "../collab/kanbanStore.js";
import type { IikoMentorClient } from "../mentor/client.js";
import type { DialogModeStore } from "../collab/dialogModeStore.js";
import { DialogOrchestrator } from "../collab/dialogOrchestrator.js";
import type { BackupService, SnapshotMetadata } from "../backup/backupService.js";

interface CreateBotOptions {
  config: AppConfig;
  store: TaskStore;
  runner: TaskRunner;
  agentRunner: AgentRunner;
  backups: BackupService;
  approvals: ApprovalStore;
  eventLog: EventLog;
  chatMemory: ChatMemoryStore;
  conversationLog: ConversationLogStore;
  sessions: SessionStore;
  kanban: KanbanStore;
  dialogMode: DialogModeStore;
  mentorClient: IikoMentorClient;
  startedAt: Date;
}

function chatKey(ctx: Context): string {
  return String(ctx.chat?.id ?? ctx.from?.id ?? "unknown");
}

async function replyAndLog(
  ctx: Context,
  conversationLog: ConversationLogStore,
  text: string
): Promise<void> {
  await ctx.reply(text);
  conversationLog.append({
    chatId: chatKey(ctx),
    direction: "out",
    kind: "text",
    text
  });
}

function logIncomingText(
  ctx: Context,
  conversationLog: ConversationLogStore,
  text: string,
  kind: "text" | "voice" | "attachment" = "text"
): void {
  conversationLog.append({
    chatId: chatKey(ctx),
    ...(ctx.from?.id !== undefined ? { fromId: ctx.from.id } : {}),
    ...(ctx.from?.username ? { username: ctx.from.username } : {}),
    direction: "in",
    kind,
    text
  });
}

function describeMessageAttachment(ctx: Context): string | null {
  const message = ctx.message;

  if (!message) {
    return null;
  }

  if ("document" in message && message.document) {
    return `документ "${message.document.file_name ?? "без имени"}"`;
  }

  if ("photo" in message && message.photo) {
    return "фото";
  }

  if ("video" in message && message.video) {
    return `видео "${message.video.file_name ?? "без имени"}"`;
  }

  if ("animation" in message && message.animation) {
    return `анимация "${message.animation.file_name ?? "без имени"}"`;
  }

  if ("audio" in message && message.audio) {
    return `аудио "${message.audio.file_name ?? message.audio.title ?? "без имени"}"`;
  }

  if ("sticker" in message && message.sticker) {
    return "стикер";
  }

  return null;
}

function formatAttachmentInput(ctx: Context): string | null {
  const caption = ctx.message?.caption?.trim();
  const attachment = describeMessageAttachment(ctx);

  if (!caption && !attachment) {
    return null;
  }

  return [
    attachment ? `Пользователь отправил вложение: ${attachment}.` : "",
    caption ? `Подпись к вложению: ${caption}` : ""
  ]
    .filter(Boolean)
    .join("\n");
}

function commandPayload(ctx: Context): string {
  const text = ctx.message?.text ?? "";
  return text.replace(/^\/\S+\s*/, "").trim();
}

const DIALOG_DURATION_MS = 30 * 60_000;

function parseDialogCommand(text: string): { type: "start"; goal: string } | { type: "stop" } | null {
  const normalized = text.trim();

  if (/^\/диалог_стоп(?:@\S+)?$/i.test(normalized)) {
    return { type: "stop" };
  }

  const startMatch = normalized.match(/^\/диалог(?:@\S+)?(?:\s+(.*))?$/i);

  if (startMatch) {
    return { type: "start", goal: (startMatch[1] ?? "").trim() };
  }

  return null;
}

function formatTaskLine(task: ReturnType<TaskStore["list"]>[number]): string {
  return `${task.id.slice(0, 8)} | ${task.kind} | ${task.status} | ${task.input}`;
}

function formatTaskResult(task: ReturnType<TaskStore["get"]>): string {
  if (!task) {
    return "Не нашел эту задачу. Возможно, она уже очищена из истории.";
  }

  if (task.status === "failed" || task.status === "rejected") {
    return [
      "Не получилось выполнить задачу.",
      task.error ? `Причина: ${task.error}` : "Причина пока не определена.",
      "Я сохранил это в журнале, можно вернуться и разобрать спокойно."
    ].join("\n");
  }

  if (task.output) {
    return task.output;
  }

  return "Готово. Выполнил задачу.";
}

function formatSnapshot(snapshot: SnapshotMetadata | null): string {
  if (!snapshot) {
    return "Пока нет резервных копий агентских задач.";
  }

  const changedFiles = snapshot.changedFiles ?? [];

  return [
    `Последняя резервная копия: ${snapshot.id}`,
    `Создана: ${snapshot.createdAt}`,
    `Задача: ${snapshot.taskId.slice(0, 8)}`,
    changedFiles.length > 0
      ? `Изменённые файлы:\n${changedFiles.map((file) => `- ${file}`).join("\n")}`
      : "Изменённых файлов в этой задаче не зафиксировано."
  ].join("\n");
}

function formatDiff(snapshot: SnapshotMetadata | null): string {
  if (!snapshot) {
    return "Пока нет diff по агентским задачам.";
  }

  if (!snapshot.diffSummary?.trim()) {
    return "Diff summary пустой: git сейчас не показывает изменений или задача не меняла файлы.";
  }

  return snapshot.diffSummary;
}

function formatApproval(approval: PendingApproval): string {
  return [
    `Нужно подтверждение: ${approval.id.slice(0, 8)}`,
    `Причина: ${approval.reason}`,
    "",
    approval.input,
    "",
    `Подтвердить: /approve ${approval.id.slice(0, 8)}`,
    `Отклонить: /reject ${approval.id.slice(0, 8)}`
  ].join("\n");
}

function approvalKeyboard(approval: PendingApproval): InlineKeyboard {
  return new InlineKeyboard()
    .text("Подтвердить", `approve:${approval.id}`)
    .text("Отклонить", `reject:${approval.id}`);
}

function statusText(store: TaskStore, startedAt: Date): string {
  const uptimeSeconds = Math.round((Date.now() - startedAt.getTime()) / 1000);

  return [
    "Server is running.",
    `Uptime: ${uptimeSeconds}s`,
    `Active tasks: ${store.countActive()}`
  ].join("\n");
}

const partnerDocumentFiles = [
  {
    path: "docs/project-overview-for-partners.docx",
    caption: "Проектная документация для Елены Петровны в Word"
  },
  {
    path: "docs/technical-specification.md",
    caption: "Техническое задание"
  },
  {
    path: "docs/financial-model.md",
    caption: "Финансовая модель"
  },
  {
    path: "docs/architecture-overview.md",
    caption: "Архитектура проекта"
  },
  {
    path: "docs/implementation-plan.md",
    caption: "План реализации"
  }
];

async function sendPartnerDocuments(
  ctx: Context,
  config: AppConfig,
  conversationLog: ConversationLogStore
): Promise<void> {
  await replyAndLog(
    ctx,
    conversationLog,
    "Отправляю проектные документы для проверки и правок."
  );

  for (const documentFile of partnerDocumentFiles) {
    const absolutePath = path.join(config.workingDirectory, documentFile.path);

    if (!fs.existsSync(absolutePath)) {
      await replyAndLog(
        ctx,
        conversationLog,
        `Не нашел файл: ${documentFile.path}. Его нужно сначала сгенерировать или восстановить.`
      );
      continue;
    }

    await ctx.replyWithDocument(new InputFile(absolutePath), {
      caption: documentFile.caption
    });
    conversationLog.append({
      chatId: chatKey(ctx),
      direction: "out",
      kind: "text",
      text: `Отправил файл: ${documentFile.path}`
    });
  }
}

let agentQueueTail = Promise.resolve();
let agentQueueWaiting = 0;
let agentQueueRunning = 0;

function agentQueueStatusText(): string {
  return `В работе сейчас: ${agentQueueRunning}. В очереди: ${agentQueueWaiting}.`;
}

async function runAgentTaskFromChat(
  ctx: Context,
  input: string,
  store: TaskStore,
  agentRunner: AgentRunner,
  conversationLog: ConversationLogStore
): Promise<void> {
  const key = chatKey(ctx);
  const conversationContext = conversationLog.formatContext(chatKey(ctx));
  const inputWithContext = [
    "Контекст последних сообщений Telegram-беседы:",
    conversationContext,
    "",
    "Текущее сообщение пользователя:",
    input
  ].join("\n");

  conversationLog.append({
    chatId: key,
    direction: "out",
    kind: "agent_context",
    text: "Передал агенту последние сообщения беседы как контекст."
  });

  const task = store.create({
    kind: "agent",
    input: inputWithContext,
    createdBy: ctx.from!.id
  });

  agentQueueWaiting += 1;
  await replyAndLog(
    ctx,
    conversationLog,
    `Поставил задачу в очередь. ${agentQueueStatusText()}`
  );

  const runQueuedTask = async (): Promise<void> => {
    agentQueueWaiting = Math.max(0, agentQueueWaiting - 1);
    agentQueueRunning += 1;

    try {
      await replyAndLog(
        ctx,
        conversationLog,
        `Начал выполнять следующую задачу. ${agentQueueStatusText()}`
      );
      const result = await agentRunner.runAgentTask(task);
      await replyAndLog(ctx, conversationLog, formatTaskResult(result));
    } finally {
      agentQueueRunning = Math.max(0, agentQueueRunning - 1);

      if (agentQueueWaiting > 0) {
        await replyAndLog(
          ctx,
          conversationLog,
          `Перехожу к следующей задаче. ${agentQueueStatusText()}`
        );
      }
    }
  };

  agentQueueTail = agentQueueTail.then(runQueuedTask, runQueuedTask).catch((error) => {
    console.error("Agent queue task failed", error);
  });
}

async function handleNaturalMessage(
  ctx: Context,
  input: string,
  store: TaskStore,
  agentRunner: AgentRunner,
  approvals: ApprovalStore,
  eventLog: EventLog,
  chatMemory: ChatMemoryStore,
  conversationLog: ConversationLogStore
): Promise<void> {
  const key = chatKey(ctx);
  const context = chatMemory.get(key);
  chatMemory.rememberMessage(key, input);

  const decision = decideMessage(
    input,
    context.lastActionableContext
      ? { lastActionableContext: context.lastActionableContext }
      : {}
  );

  if (decision.type === "clarify") {
    await replyAndLog(ctx, conversationLog, decision.question);
    return;
  }

  if (decision.type === "chat") {
    await replyAndLog(ctx, conversationLog, "Ваше сообщение принял. Работаю над ответом.");
    await replyAndLog(ctx, conversationLog, decision.response);
    return;
  }

  if (decision.type === "approval_required") {
    const approval = approvals.create({
      input: decision.normalizedInput,
      reason: decision.reason,
      requestedBy: ctx.from!.id
    });

    eventLog.write("approval.created", {
      approvalId: approval.id,
      requestedBy: approval.requestedBy,
      reason: approval.reason
    });
    chatMemory.rememberActionableContext(key, decision.normalizedInput);
    const approvalText = formatApproval(approval);
    await ctx.reply(approvalText, {
      reply_markup: approvalKeyboard(approval)
    });
    conversationLog.append({
      chatId: key,
      direction: "out",
      kind: "text",
      text: approvalText
    });
    return;
  }

  eventLog.write("message.accepted", {
    from: ctx.from?.id,
    username: ctx.from?.username,
    input: decision.normalizedInput
  });
  chatMemory.rememberActionableContext(key, decision.normalizedInput);
  await replyAndLog(ctx, conversationLog, "Ваше сообщение принял. Выполняю задачу.");
  await runAgentTaskFromChat(
    ctx,
    decision.normalizedInput,
    store,
    agentRunner,
    conversationLog
  );
}

async function requireAccess(ctx: Context, config: AppConfig): Promise<boolean> {
  const userId = ctx.from?.id;
  const username = ctx.from?.username;

  if (isAllowedTelegramUser(userId, username, config)) {
    return true;
  }

  await ctx.reply(
    [
      "Access denied.",
      `Your Telegram user ID is ${userId ?? "unknown"}.`,
      `Your Telegram username is ${username ? `@${username}` : "unknown"}.`
    ].join("\n")
  );
  return false;
}

async function requireApprover(ctx: Context, config: AppConfig): Promise<boolean> {
  const userId = ctx.from?.id;
  const username = ctx.from?.username;

  if (isTelegramApprover(userId, username, config)) {
    return true;
  }

  await ctx.reply("Это действие может подтвердить только пользователь из списка approver-ов.");
  return false;
}

async function approvePendingAction(
  ctx: Context,
  approvalId: string,
  approvals: ApprovalStore,
  store: TaskStore,
  agentRunner: AgentRunner,
  eventLog: EventLog,
  conversationLog: ConversationLogStore
): Promise<void> {
  const approval = approvals.get(approvalId);

  if (!approval) {
    await ctx.reply("Не нашел такое подтверждение. Проверьте /approvals.");
    return;
  }

  approvals.delete(approval.id);
  eventLog.write("approval.approved", {
    approvalId: approval.id,
    approvedBy: ctx.from?.id
  });
  await replyAndLog(ctx, conversationLog, "Ваше сообщение принял. Выполняю задачу.");
  await runAgentTaskFromChat(
    ctx,
    approval.input,
    store,
    agentRunner,
    conversationLog
  );
}

async function rejectPendingAction(
  ctx: Context,
  approvalId: string,
  approvals: ApprovalStore,
  eventLog: EventLog
): Promise<void> {
  const approval = approvals.get(approvalId);

  if (!approval) {
    await ctx.reply("Не нашел такое подтверждение. Проверьте /approvals.");
    return;
  }

  approvals.delete(approval.id);
  eventLog.write("approval.rejected", {
    approvalId: approval.id,
    rejectedBy: ctx.from?.id
  });
  await ctx.reply(`Отклонил действие ${approval.id.slice(0, 8)}. Ничего не запускаю.`);
}

async function handleKanbanCommand(
  ctx: Context,
  column: KanbanColumn,
  kanban: KanbanStore
): Promise<void> {
  const payload = commandPayload(ctx);

  if (!payload) {
    await ctx.reply(formatKanban(kanban.list(column), column));
    return;
  }

  const moveMatch = payload.match(/^move\s+(\S+)\s+(todo|doing|done|blockers)$/i);

  if (moveMatch?.[1] && moveMatch[2]) {
    const moved = kanban.move(moveMatch[1], moveMatch[2].toLowerCase() as KanbanColumn);
    await ctx.reply(
      moved
        ? `Перенес ${moved.id.slice(0, 8)} в ${moved.column}.`
        : "Не нашел такую задачу."
    );
    return;
  }

  const item = kanban.add(column, payload, ctx.from!.id);
  await ctx.reply(`Добавил в ${column}: ${item.id.slice(0, 8)} | ${item.text}`);
}

export function createTelegramBot(options: CreateBotOptions): Bot {
  const {
    config,
    store,
    runner,
    agentRunner,
    backups,
    approvals,
    eventLog,
    chatMemory,
    conversationLog,
    sessions,
    kanban,
    dialogMode,
    mentorClient,
    startedAt
  } = options;
  const bot = new Bot(config.telegramBotToken);
  const dialogOrchestrator = new DialogOrchestrator({
    bot,
    dialogMode,
    mentorClient,
    agentRunner,
    store,
    conversationLog
  });

  bot.command("start", async (ctx) => {
    const allowed = isAllowedTelegramUser(
      ctx.from?.id,
      ctx.from?.username,
      config
    );

    await ctx.reply(
      [
        "Telegram Dev Bot is ready.",
        `Access: ${allowed ? "allowed" : "denied"}`,
        `Your user ID: ${ctx.from?.id ?? "unknown"}`,
        `Your username: ${ctx.from?.username ? `@${ctx.from.username}` : "unknown"}`,
        "Use /help to see available commands."
      ].join("\n")
    );
  });

  bot.command("help", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    await ctx.reply(
      [
        "Команды:",
        "/status - show server status",
        "/chat_context - show recent saved chat context",
        "/queue - show current agent queue",
        "/backup last - show latest pre-agent backup",
        "/diff last - show latest git diff summary",
        "/rollback last - restore files changed by the latest agent task",
        "/docs_files - send project documents to the chat",
        "/run <command> - run whitelisted dev command",
        "/task <text> - create a text task",
        "/agent <text> - ask Cursor agent to develop or document",
        "/session_start <topic> - start a working session",
        "/session_summary - show current session summary",
        "/session_end - finish current session",
        "/decision <text> - save product decision",
        "/todo [text] - list or add todo item",
        "/doing [text] - list or add doing item",
        "/done [text] - list or add done item",
        "/blockers [text] - list or add blocker",
        "/kanban - show all kanban items",
        "/approvals - show actions waiting for approval",
        "/approve <id> - approve and run a pending action",
        "/reject <id> - reject a pending action",
        "/диалог [цель] - совместная работа с ботом-наставником (30 минут)",
        "/диалог_стоп - остановить режим /диалог",
        "Text message - create an agent task when AUTO_AGENT_ON_CHAT=true",
        "Voice message - transcribe and create an agent task",
        "/tasks - show recent tasks",
        "/logs <taskId> - show task output",
        "",
        "Allowed /run commands:",
        formatAllowedCommands()
      ].join("\n")
    );
  });

  bot.command("status", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    await ctx.reply(statusText(store, startedAt));
  });

  bot.command("chat_context", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    await replyAndLog(
      ctx,
      conversationLog,
      conversationLog.formatContext(chatKey(ctx), 20)
    );
  });

  bot.command("queue", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    await replyAndLog(ctx, conversationLog, agentQueueStatusText());
  });

  bot.command("backup", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const payload = commandPayload(ctx);

    if (payload && payload !== "last") {
      await ctx.reply("Пока поддерживается команда: /backup last");
      return;
    }

    await replyAndLog(ctx, conversationLog, formatSnapshot(backups.latestSnapshot()));
  });

  bot.command("diff", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const payload = commandPayload(ctx);

    if (payload && payload !== "last") {
      await ctx.reply("Пока поддерживается команда: /diff last");
      return;
    }

    await replyAndLog(ctx, conversationLog, formatDiff(backups.latestSnapshot()));
  });

  bot.command("rollback", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (!(await requireApprover(ctx, config))) {
      return;
    }

    const payload = commandPayload(ctx);

    if (payload && payload !== "last") {
      await ctx.reply("Пока поддерживается команда: /rollback last");
      return;
    }

    const snapshot = backups.latestSnapshot();

    if (!snapshot) {
      await ctx.reply("Пока нет резервной копии, к которой можно откатиться.");
      return;
    }

    const result = backups.rollback(snapshot.id);
    eventLog.write("backup.rollback", {
      snapshotId: result.snapshotId,
      restoredFiles: result.restoredFiles,
      removedFiles: result.removedFiles,
      requestedBy: ctx.from?.id
    });

    await replyAndLog(
      ctx,
      conversationLog,
      [
        `Откатил изменения последней агентской задачи: ${result.snapshotId}`,
        result.restoredFiles.length > 0
          ? `Восстановленные файлы:\n${result.restoredFiles.map((file) => `- ${file}`).join("\n")}`
          : "Файлов для восстановления не было.",
        result.removedFiles.length > 0
          ? `Удалённые новые файлы:\n${result.removedFiles.map((file) => `- ${file}`).join("\n")}`
          : ""
      ]
        .filter(Boolean)
        .join("\n")
    );
  });

  bot.command("docs_files", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    await sendPartnerDocuments(ctx, config, conversationLog);
  });

  bot.command("run", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const input = commandPayload(ctx);

    if (!input) {
      await ctx.reply("Usage: /run <allowed command>");
      return;
    }

    const task = store.create({
      kind: "command",
      input,
      createdBy: ctx.from!.id
    });

    await ctx.reply(`Task queued: ${task.id}`);
    const result = await runner.runCommand(task);
    await ctx.reply(formatTaskResult(result));
  });

  bot.command("task", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const input = commandPayload(ctx);

    if (!input) {
      await ctx.reply("Usage: /task <text>");
      return;
    }

    const task = store.create({
      kind: "note",
      input,
      createdBy: ctx.from!.id,
      output: "Text task created. Connect a worker to process it later."
    });

    const updated = store.update(task.id, {
      status: "succeeded",
      finishedAt: new Date().toISOString()
    })!;

    await ctx.reply(formatTaskResult(updated));
  });

  bot.command("agent", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const input = commandPayload(ctx);

    if (!input) {
      await ctx.reply("Usage: /agent <development or documentation task>");
      return;
    }

    chatMemory.rememberMessage(chatKey(ctx), input);
    chatMemory.rememberActionableContext(chatKey(ctx), input);
    logIncomingText(ctx, conversationLog, input);
    await replyAndLog(ctx, conversationLog, "Ваше сообщение принял. Выполняю задачу.");
    await runAgentTaskFromChat(ctx, input, store, agentRunner, conversationLog);
  });

  bot.command("approvals", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const pending = approvals.list(10);

    await ctx.reply(
      pending.length > 0
        ? pending.map(formatApproval).join("\n\n---\n\n")
        : "Сейчас нет действий, ожидающих подтверждения."
    );
  });

  bot.command("approve", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (!(await requireApprover(ctx, config))) {
      return;
    }

    const approvalId = commandPayload(ctx);

    if (!approvalId) {
      await ctx.reply("Напишите так: /approve <id>");
      return;
    }

    await approvePendingAction(
      ctx,
      approvalId,
      approvals,
      store,
      agentRunner,
      eventLog,
      conversationLog
    );
  });

  bot.command("reject", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (!(await requireApprover(ctx, config))) {
      return;
    }

    const approvalId = commandPayload(ctx);

    if (!approvalId) {
      await ctx.reply("Напишите так: /reject <id>");
      return;
    }

    await rejectPendingAction(ctx, approvalId, approvals, eventLog);
  });

  bot.callbackQuery(/^approve:(.+)$/, async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (!(await requireApprover(ctx, config))) {
      return;
    }

    const approvalId = ctx.match[1];

    if (!approvalId) {
      await ctx.answerCallbackQuery("Не вижу ID подтверждения");
      return;
    }

    await ctx.answerCallbackQuery("Подтверждаю");
    await approvePendingAction(
      ctx,
      approvalId,
      approvals,
      store,
      agentRunner,
      eventLog,
      conversationLog
    );
  });

  bot.callbackQuery(/^reject:(.+)$/, async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (!(await requireApprover(ctx, config))) {
      return;
    }

    const approvalId = ctx.match[1];

    if (!approvalId) {
      await ctx.answerCallbackQuery("Не вижу ID подтверждения");
      return;
    }

    await ctx.answerCallbackQuery("Отклоняю");
    await rejectPendingAction(ctx, approvalId, approvals, eventLog);
  });

  bot.command("tasks", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const tasks = store.list(10);
    await ctx.reply(
      tasks.length > 0
        ? tasks.map(formatTaskLine).join("\n")
        : "No tasks yet."
    );
  });

  bot.command("logs", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const taskId = commandPayload(ctx);

    if (!taskId) {
      await ctx.reply("Usage: /logs <taskId>");
      return;
    }

    const task =
      store.get(taskId) ??
      store.list(100).find((candidate) => candidate.id.startsWith(taskId));

    await ctx.reply(formatTaskResult(task));
  });

  bot.command("todo", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await handleKanbanCommand(ctx, "todo", kanban);
  });

  bot.command("doing", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await handleKanbanCommand(ctx, "doing", kanban);
  });

  bot.command("done", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await handleKanbanCommand(ctx, "done", kanban);
  });

  bot.command("blockers", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await handleKanbanCommand(ctx, "blockers", kanban);
  });

  bot.command("kanban", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await ctx.reply(formatKanban(kanban.list(), "Канбан"));
  });

  bot.command("session_start", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    const title = commandPayload(ctx) || "Рабочая сессия notame.ru";
    const session = sessions.start(chatKey(ctx), title, ctx.from!.id);
    await ctx.reply(`Начали рабочую сессию: ${session.title}\nID: ${session.id.slice(0, 8)}`);
  });

  bot.command("session_summary", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    await ctx.reply(sessions.summary(chatKey(ctx)));
  });

  bot.command("session_end", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    const summary = sessions.summary(chatKey(ctx));
    const session = sessions.end(chatKey(ctx));
    await ctx.reply(
      session
        ? `Сессию завершил.\n\n${summary}`
        : "Активной рабочей сессии нет."
    );
  });

  bot.command("decision", async (ctx) => {
    if (!(await requireAccess(ctx, config))) return;
    const decision = commandPayload(ctx);

    if (!decision) {
      await ctx.reply("Напишите так: /decision <решение>");
      return;
    }

    const session = sessions.addDecision(chatKey(ctx), decision);
    await ctx.reply(
      session
        ? `Записал решение в сессию и product-decisions.md: ${decision}`
        : "Активной сессии нет. Начните: /session_start <тема>"
    );
  });

  bot.on("message:voice", async (ctx) => {
    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const voice = ctx.message.voice;
    sessions.addMessage(chatKey(ctx), `[voice] ${voice.file_id}`);

    if (voice.duration > config.maxVoiceDurationSeconds) {
      await ctx.reply(
        `Voice message is too long. Limit: ${config.maxVoiceDurationSeconds}s.`
      );
      return;
    }

    await ctx.reply("Transcribing voice message...");

    try {
      const file = await ctx.api.getFile(voice.file_id);

      if (!file.file_path) {
        await ctx.reply("Telegram did not return a file path for this voice message.");
        return;
      }

      const transcript = await transcribeTelegramVoice({
        config,
        filePath: file.file_path
      });

      if (!transcript) {
        await ctx.reply("Could not recognize text in the voice message.");
        return;
      }

      await ctx.reply(`Распознал голосовое:\n${transcript}`);
      logIncomingText(ctx, conversationLog, transcript, "voice");
      await handleNaturalMessage(
        ctx,
        transcript,
        store,
        agentRunner,
        approvals,
        eventLog,
        chatMemory,
        conversationLog
      );
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unknown error";
      await ctx.reply(`Voice transcription failed: ${message}`);
    }
  });

  bot.on("message:text", async (ctx) => {
    const text = ctx.message.text.trim();
    const dialogCommand = parseDialogCommand(text);

    if (dialogCommand) {
      if (!(await requireAccess(ctx, config))) {
        return;
      }

      logIncomingText(ctx, conversationLog, text);

      if (dialogCommand.type === "stop") {
        dialogOrchestrator.stop(chatKey(ctx));
        return;
      }

      dialogOrchestrator.start(
        chatKey(ctx),
        ctx.from!.id,
        DIALOG_DURATION_MS,
        dialogCommand.goal || undefined
      );
      return;
    }

    if (text.startsWith("/")) {
      return;
    }

    if (!config.autoAgentOnChat) {
      return;
    }

    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (dialogMode.isActive(chatKey(ctx))) {
      await replyAndLog(
        ctx,
        conversationLog,
        "Сейчас идёт режим /диалог. Обычные сообщения не запускают отдельную работу основного агента. Остановить: /диалог_стоп"
      );
      return;
    }

    sessions.addMessage(chatKey(ctx), text);
    logIncomingText(ctx, conversationLog, text);
    await handleNaturalMessage(
      ctx,
      text,
      store,
      agentRunner,
      approvals,
      eventLog,
      chatMemory,
      conversationLog
    );
  });

  bot.on("message:caption", async (ctx) => {
    const input = formatAttachmentInput(ctx);

    if (!input) {
      return;
    }

    if (!config.autoAgentOnChat) {
      return;
    }

    if (!(await requireAccess(ctx, config))) {
      return;
    }

    if (dialogMode.isActive(chatKey(ctx))) {
      await replyAndLog(
        ctx,
        conversationLog,
        "Вижу вложение, но сейчас идёт режим /диалог. Остановить: /диалог_стоп"
      );
      return;
    }

    sessions.addMessage(chatKey(ctx), input);
    logIncomingText(ctx, conversationLog, input, "attachment");
    await handleNaturalMessage(
      ctx,
      input,
      store,
      agentRunner,
      approvals,
      eventLog,
      chatMemory,
      conversationLog
    );
  });

  bot.on([
    "message:document",
    "message:photo",
    "message:video",
    "message:animation",
    "message:audio",
    "message:sticker"
  ], async (ctx) => {
    if (ctx.message?.caption) {
      return;
    }

    if (!(await requireAccess(ctx, config))) {
      return;
    }

    const attachment = describeMessageAttachment(ctx);

    if (!attachment) {
      return;
    }

    const input = `Пользователь отправил вложение без подписи: ${attachment}.`;
    sessions.addMessage(chatKey(ctx), input);
    logIncomingText(ctx, conversationLog, input, "attachment");
    await replyAndLog(
      ctx,
      conversationLog,
      "Вижу вложение. Напишите, пожалуйста, что с ним сделать или что проверить."
    );
  });

  bot.catch((error) => {
    console.error("Telegram bot error", error);
    const description =
      error.error instanceof Error ? error.error.message : String(error.error);

    if (
      description.includes("409") ||
      description.includes("terminated by other getUpdates request")
    ) {
      eventLog.write("telegram.conflict", { description });
      console.error(
        "Telegram polling conflict: another bot instance is using this token. Stop the duplicate instance."
      );
      process.exitCode = 1;
      bot.stop();
    }
  });

  return bot;
}
