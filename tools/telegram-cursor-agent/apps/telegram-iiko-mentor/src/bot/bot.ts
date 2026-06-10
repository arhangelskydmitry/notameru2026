import fs from "node:fs";
import { Bot, type Context } from "grammy";
import type { AppConfig } from "../config.js";
import { isAllowedTelegramUser } from "../auth/telegramAccess.js";
import type { TaskStore } from "../tasks/types.js";
import { transcribeTelegramVoice } from "../voice/voiceTranscriber.js";
import type { AgentRunner } from "../agent/agentRunner.js";
import { decideMessage } from "../chat/messageIntent.js";
import type { EventLog } from "../storage/eventLog.js";
import type { ConversationLogStore } from "../chat/conversationLog.js";
import type { IikoControlClient } from "../iikoControl/client.js";
import type { DialogModeStore } from "../dialog/dialogModeStore.js";
import {
  sendTaskStatusMessage,
  type TaskStatusMessage
} from "./taskStatusMessage.js";
import { AGENT_TASK_STATUS } from "../agent/agentStatusText.js";

interface CreateBotOptions {
  config: AppConfig;
  store: TaskStore;
  agentRunner: AgentRunner;
  eventLog: EventLog;
  conversationLog: ConversationLogStore;
  iikoControl: IikoControlClient;
  dialogMode: DialogModeStore;
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

function formatTaskResult(task: ReturnType<TaskStore["get"]>): string {
  if (!task) {
    return "Не нашёл эту задачу.";
  }

  if (task.status === "failed" || task.status === "rejected") {
    return [
      "Не получилось обработать вопрос.",
      task.error ? `Причина: ${task.error}` : "Причина пока не определена.",
      "Можно переформулировать вопрос или передать задачу Контролеру — @dm9998111."
    ].join("\n");
  }

  if (task.output) {
    return task.output;
  }

  return "Готово.";
}

function formatIikoIncidentLine(
  incident: Awaited<ReturnType<IikoControlClient["listIncidents"]>>[number]
): string {
  return [
    `${incident.id.slice(0, 8)} | ${incident.level} | ${incident.status}`,
    incident.title,
    incident.description
  ].join("\n");
}

function readChecklistPreview(filePath: string, maxChars = 3_000): string {
  if (!fs.existsSync(filePath)) {
    return "Чек-лист пока не найден. Спросите Контролера — @dm9998111.";
  }

  const content = fs.readFileSync(filePath, "utf8").trim();

  if (content.length <= maxChars) {
    return content;
  }

  return `${content.slice(0, maxChars)}\n\n... чек-лист обрезан, полный текст в docs/iiko-testing-checklist.md`;
}

async function runAgentTaskFromChat(
  ctx: Context,
  input: string,
  store: TaskStore,
  agentRunner: AgentRunner,
  conversationLog: ConversationLogStore,
  statusMessage?: TaskStatusMessage
): Promise<void> {
  const conversationContext = conversationLog.formatContext(chatKey(ctx));
  const inputWithContext = [
    "Контекст последних сообщений Telegram-беседы:",
    conversationContext,
    "",
    "Текущее сообщение пользователя:",
    input
  ].join("\n");

  conversationLog.append({
    chatId: chatKey(ctx),
    direction: "out",
    kind: "agent_context",
    text: "Передал агенту последние сообщения беседы как контекст."
  });

  const task = store.create({
    kind: "agent",
    input: inputWithContext,
    createdBy: ctx.from!.id
  });

  const result = await agentRunner.runAgentTask(
    task,
    statusMessage
      ? {
          onStatusUpdate: async (status) => {
            await statusMessage.update(status);
          }
        }
      : undefined
  );

  if (statusMessage) {
    await statusMessage.update(AGENT_TASK_STATUS.done);
  }

  await replyAndLog(ctx, conversationLog, formatTaskResult(result));
}

async function handleNaturalMessage(
  ctx: Context,
  input: string,
  config: AppConfig,
  store: TaskStore,
  agentRunner: AgentRunner,
  conversationLog: ConversationLogStore,
  dialogMode: DialogModeStore
): Promise<void> {
  const decision = decideMessage(input, {
    ...(ctx.from?.username ? { senderUsername: ctx.from.username } : {}),
    isPrivateChat: ctx.chat?.type === "private",
    mentorBotUsername: config.mentorBotUsername,
    controllerBotUsername: config.controllerBotUsername,
    dialogModeActive: dialogMode.isActive(chatKey(ctx))
  });

  if (decision.type === "observe") {
    conversationLog.append({
      chatId: chatKey(ctx),
      direction: "out",
      kind: "agent_context",
      text: "Сообщение не для Наставника: слушаю, ответ не отправляю."
    });
    return;
  }

  if (decision.type === "chat") {
    await replyAndLog(ctx, conversationLog, decision.response);
    return;
  }

  if (decision.type === "clarify") {
    await replyAndLog(ctx, conversationLog, decision.question);
    return;
  }

  const statusMessage = await sendTaskStatusMessage(
    ctx,
    conversationLog,
    AGENT_TASK_STATUS.accepted
  );

  await runAgentTaskFromChat(
    ctx,
    decision.normalizedInput,
    store,
    agentRunner,
    conversationLog,
    statusMessage
  );
}

export function createTelegramBot(options: CreateBotOptions): Bot {
  const {
    config,
    store,
    agentRunner,
    eventLog,
    conversationLog,
    iikoControl,
    dialogMode,
    startedAt
  } = options;

  const bot = new Bot(config.telegramBotToken);

  bot.use(async (ctx, next) => {
    const allowed = isAllowedTelegramUser(
      ctx.from?.id,
      ctx.from?.username,
      config
    );

    if (!allowed && ctx.message) {
      await ctx.reply(
        [
          "Доступ ограничен.",
          `Your Telegram user ID is ${ctx.from?.id ?? "unknown"}.`,
          `Your Telegram username is ${ctx.from?.username ? `@${ctx.from.username}` : "unknown"}.`
        ].join("\n")
      );
      return;
    }

    await next();
  });

  bot.command("start", async (ctx) => {
    await replyAndLog(
      ctx,
      conversationLog,
      [
        "Привет. Я Наставник IIKO — помогаю тестировать iiko Control и объяснять всё простым языком.",
        "",
        "Могу:",
        "— разобрать экран или отчёт;",
        "— пройти сценарий из чек-листа;",
        "— сформулировать требование для разработки;",
        "— передать баг Контролеру, если нужна правка кода.",
        "",
        "Команды:",
        "/checklist — чек-лист тестирования",
        "/status — статус iiko Control API",
        "/incidents — последние инциденты",
        "",
        "В общем чате обращайтесь с тегом @ElenaPetrovnaMentor_bot.",
        "В личке можно писать без тега.",
        "",
        "Совместная работа ботов: /диалог в чате Контролера (30 минут)."
      ].join("\n")
    );
  });

  bot.command("checklist", async (ctx) => {
    await replyAndLog(ctx, conversationLog, readChecklistPreview(config.checklistFile));
  });

  bot.command("status", async (ctx) => {
    try {
      const status = await iikoControl.getStatus();
      await replyAndLog(
        ctx,
        conversationLog,
        [
          "iiko Control API:",
          `ok: ${status.ok}`,
          `service: ${status.service}`,
          `uptime: ${status.uptimeSeconds}s`,
          `incidents: ${status.incidents}`
        ].join("\n")
      );
    } catch (error) {
      await replyAndLog(
        ctx,
        conversationLog,
        `Не смог получить статус iiko Control API: ${
          error instanceof Error ? error.message : "unknown error"
        }`
      );
    }
  });

  bot.command("incidents", async (ctx) => {
    try {
      const incidents = await iikoControl.listIncidents();
      const text =
        incidents.length > 0
          ? incidents.slice(0, 10).map(formatIikoIncidentLine).join("\n\n")
          : "В iiko Control API пока нет инцидентов.";

      await replyAndLog(ctx, conversationLog, text);
    } catch (error) {
      await replyAndLog(
        ctx,
        conversationLog,
        `Не смог получить инциденты iiko Control API: ${
          error instanceof Error ? error.message : "unknown error"
        }`
      );
    }
  });

  bot.command("help", async (ctx) => {
    const uptimeSeconds = Math.round((Date.now() - startedAt.getTime()) / 1000);

    await replyAndLog(
      ctx,
      conversationLog,
      [
        "Наставник IIKO — бот для тестирования и объяснений по iiko Control.",
        `Uptime: ${uptimeSeconds}s`,
        `Active tasks: ${store.countActive()}`,
        "",
        "/checklist — чек-лист тестирования",
        "/status — статус API",
        "/incidents — инциденты",
        "",
        "Для правок кода и разработки — пишите Контролеру в основной бот."
      ].join("\n")
    );
  });

  bot.on("message:text", async (ctx) => {
    const text = ctx.message.text.trim();

    if (text.startsWith("/")) {
      return;
    }

    logIncomingText(ctx, conversationLog, text);
    eventLog.write("message.received", {
      chatId: chatKey(ctx),
      fromId: ctx.from?.id,
      username: ctx.from?.username
    });

    await handleNaturalMessage(
      ctx,
      text,
      config,
      store,
      agentRunner,
      conversationLog,
      dialogMode
    );
  });

  bot.on("message:caption", async (ctx) => {
    const input = formatAttachmentInput(ctx);

    if (!input) {
      return;
    }

    logIncomingText(ctx, conversationLog, input, "attachment");
    eventLog.write("message.attachment.received", {
      chatId: chatKey(ctx),
      fromId: ctx.from?.id,
      username: ctx.from?.username
    });

    await handleNaturalMessage(
      ctx,
      input,
      config,
      store,
      agentRunner,
      conversationLog,
      dialogMode
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

    const attachment = describeMessageAttachment(ctx);

    if (!attachment) {
      return;
    }

    const input = `Пользователь отправил вложение без подписи: ${attachment}.`;
    logIncomingText(ctx, conversationLog, input, "attachment");
    await replyAndLog(
      ctx,
      conversationLog,
      "Вижу вложение. Напишите, пожалуйста, что нужно проверить или объяснить по нему."
    );
  });

  bot.on("message:voice", async (ctx) => {
    const voice = ctx.message.voice;

    if (voice.duration > config.maxVoiceDurationSeconds) {
      await replyAndLog(
        ctx,
        conversationLog,
        `Голосовое слишком длинное. Максимум ${config.maxVoiceDurationSeconds} секунд.`
      );
      return;
    }

    try {
      const file = await ctx.getFile();
      const filePath = file.file_path;

      if (!filePath) {
        await ctx.reply("Telegram did not return a file path for this voice message.");
        return;
      }

      const transcript = await transcribeTelegramVoice({
        config,
        filePath
      });

      logIncomingText(ctx, conversationLog, transcript, "voice");
      await handleNaturalMessage(
        ctx,
        transcript,
        config,
        store,
        agentRunner,
        conversationLog,
        dialogMode
      );
    } catch (error) {
      await replyAndLog(
        ctx,
        conversationLog,
        `Не смог распознать голосовое: ${
          error instanceof Error ? error.message : "unknown error"
        }`
      );
    }
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
    }
  });

  return bot;
}
