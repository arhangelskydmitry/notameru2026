import type { Bot } from "grammy";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import type { ChatMemoryStore } from "./chatMemory.js";
import type { ConversationLogStore } from "./conversationLog.js";
import type { KanbanStore } from "../collab/kanbanStore.js";
import type { TaskStore } from "../tasks/types.js";

interface ChatSilenceMonitorOptions {
  bot: Bot;
  config: Pick<
    AppConfig,
    "chatSilenceCheckIntervalMs" | "chatSilenceThresholdMs"
  >;
  conversationLog: ConversationLogStore;
  chatMemory: ChatMemoryStore;
  kanban: KanbanStore;
  store: TaskStore;
}

function formatSilenceNudge(
  chatId: string,
  conversationLog: ConversationLogStore,
  chatMemory: ChatMemoryStore,
  kanban: KanbanStore,
  store: TaskStore
): string {
  const memory = chatMemory.get(chatId);
  const doing = kanban.list("doing").slice(0, 3);
  const blockers = kanban.list("blockers").slice(0, 3);
  const activeTasks = store
    .list(5)
    .filter((task) => task.status === "queued" || task.status === "running");
  const recentContext = conversationLog.formatContext(chatId, 8);

  const lines = [
    "@dm9998111 @osipovalena",
    "",
    "В чате тишина больше часа. Коротко сверяюсь по статусу.",
    ""
  ];

  if (memory.lastActionableContext) {
    lines.push("Последняя рабочая тема:", memory.lastActionableContext, "");
  }

  if (doing.length > 0) {
    lines.push(
      "В работе:",
      ...doing.map((item) => `- ${item.text}`),
      ""
    );
  }

  if (blockers.length > 0) {
    lines.push(
      "Блокеры:",
      ...blockers.map((item) => `- ${item.text}`),
      ""
    );
  }

  if (activeTasks.length > 0) {
    lines.push(
      "Активные задачи агента:",
      ...activeTasks.map((task) => `- ${task.input.slice(0, 120)}`),
      ""
    );
  }

  lines.push(
    "Предлагаю выбрать следующий шаг:",
    "1) Продолжить последнюю тему",
    "2) Зафиксировать новую задачу для основного агента",
    "3) Передать тестирование и уточнения второму агенту, когда он появится",
    "",
    "Напишите, что делаем дальше — или просто пришлите задачу."
  );

  if (recentContext.length < 2_000) {
    lines.push("", "Контекст последних сообщений:", recentContext);
  }

  return lines.join("\n");
}

export function startChatSilenceMonitor(options: ChatSilenceMonitorOptions): () => void {
  const { bot, config, conversationLog, chatMemory, kanban, store } = options;

  const timer = setInterval(() => {
    void (async () => {
      const now = Date.now();
      const chatIds = conversationLog.listChatIds();

      for (const chatId of chatIds) {
        const lastActivity = conversationLog.lastActivityAt(chatId);

        if (!lastActivity) {
          continue;
        }

        const silentForMs = now - lastActivity.getTime();

        if (silentForMs < config.chatSilenceThresholdMs) {
          continue;
        }

        const text = formatSilenceNudge(
          chatId,
          conversationLog,
          chatMemory,
          kanban,
          store
        );

        try {
          await bot.api.sendMessage(Number(chatId), text);
          conversationLog.append({
            chatId,
            direction: "out",
            kind: "text",
            text
          });
          logger.info({ chatId, silentForMs }, "Sent chat silence nudge");
        } catch (error) {
          logger.error({ chatId, error }, "Failed to send chat silence nudge");
        }
      }
    })();
  }, config.chatSilenceCheckIntervalMs);

  return () => clearInterval(timer);
}
