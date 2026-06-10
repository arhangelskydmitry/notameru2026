import fs from "node:fs";
import path from "node:path";
import { appendJsonLine, ensureParentDirectory } from "../storage/fileStore.js";

export interface ConversationLogEntry {
  ts: string;
  chatId: string;
  fromId?: number;
  username?: string;
  direction: "in" | "out";
  kind: "text" | "voice" | "attachment" | "agent_context" | "task_status";
  text: string;
}

export class ConversationLogStore {
  constructor(private readonly directory: string) {}

  append(entry: Omit<ConversationLogEntry, "ts">): void {
    appendJsonLine(this.filePath(entry.chatId), {
      ts: new Date().toISOString(),
      ...entry
    });
  }

  tail(chatId: string, limit = 30): ConversationLogEntry[] {
    const filePath = this.filePath(chatId);

    if (!fs.existsSync(filePath)) {
      return [];
    }

    return fs
      .readFileSync(filePath, "utf8")
      .trim()
      .split("\n")
      .filter(Boolean)
      .slice(-limit)
      .flatMap((line) => {
        try {
          return [JSON.parse(line) as ConversationLogEntry];
        } catch {
          return [];
        }
      });
  }

  listChatIds(): string[] {
    if (!fs.existsSync(this.directory)) {
      return [];
    }

    return fs
      .readdirSync(this.directory)
      .filter((name) => name.endsWith(".jsonl"))
      .map((name) => name.slice(0, -".jsonl".length));
  }

  formatContext(chatId: string, limit = 30): string {
    const entries = this.tail(chatId, limit);

    if (entries.length === 0) {
      return "Предыдущих сообщений в журнале беседы пока нет.";
    }

    return entries
      .map((entry) => {
        const author =
          entry.direction === "out"
            ? "Бот"
            : entry.username
              ? `@${entry.username}`
              : `user:${entry.fromId ?? "unknown"}`;

        return `[${entry.ts}] ${author}: ${entry.text}`;
      })
      .join("\n");
  }

  private filePath(chatId: string): string {
    const safeChatId = chatId.replace(/[^a-zA-Z0-9_-]/g, "_");
    const filePath = path.join(this.directory, `${safeChatId}.jsonl`);
    ensureParentDirectory(filePath);
    return filePath;
  }
}
