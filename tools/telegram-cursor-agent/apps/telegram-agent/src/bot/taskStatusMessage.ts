import type { Api, Context } from "grammy";
import type { ConversationLogStore } from "../chat/conversationLog.js";

export interface TaskStatusMessageRef {
  chatId: number;
  messageId: number;
}

const MIN_UPDATE_INTERVAL_MS = 3_000;

export class TaskStatusMessage {
  private lastText = "";
  private lastUpdatedAt = 0;

  constructor(
    private readonly ref: TaskStatusMessageRef,
    private readonly api: Api,
    private readonly conversationLog: ConversationLogStore,
    private readonly logChatId: string
  ) {}

  async update(text: string): Promise<void> {
    const normalized = text.trim();

    if (!normalized || normalized === this.lastText) {
      return;
    }

    const now = Date.now();

    if (now - this.lastUpdatedAt < MIN_UPDATE_INTERVAL_MS) {
      return;
    }

    try {
      await this.api.editMessageText(this.ref.chatId, this.ref.messageId, normalized);
      this.lastText = normalized;
      this.lastUpdatedAt = now;
      this.conversationLog.append({
        chatId: this.logChatId,
        direction: "out",
        kind: "task_status",
        text: normalized
      });
    } catch {
      // Telegram may reject identical edits or rate-limit; ignore and retry later.
    }
  }
}

export async function sendTaskStatusMessage(
  ctx: Context,
  conversationLog: ConversationLogStore,
  text: string
): Promise<TaskStatusMessage> {
  const sent = await ctx.reply(text);
  const logChatId = String(ctx.chat?.id ?? ctx.from?.id ?? "unknown");

  conversationLog.append({
    chatId: logChatId,
    direction: "out",
    kind: "task_status",
    text
  });

  return new TaskStatusMessage(
    {
      chatId: sent.chat.id,
      messageId: sent.message_id
    },
    ctx.api,
    conversationLog,
    logChatId
  );
}
