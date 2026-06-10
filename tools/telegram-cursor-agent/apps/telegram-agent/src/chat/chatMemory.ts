import { readJsonFile, writeJsonFile } from "../storage/fileStore.js";

interface ChatMemoryEntry {
  chatId: string;
  lastMessages: string[];
  lastActionableContext?: string;
  updatedAt: string;
}

export interface ChatContext {
  lastMessages: string[];
  lastActionableContext?: string;
}

export class ChatMemoryStore {
  private readonly entries = new Map<string, ChatMemoryEntry>();

  constructor(private readonly memoryFile: string) {
    for (const entry of readJsonFile<ChatMemoryEntry[]>(this.memoryFile, [])) {
      this.entries.set(entry.chatId, entry);
    }
  }

  get(chatId: string): ChatContext {
    const entry = this.entries.get(chatId);
    const context: ChatContext = {
      lastMessages: entry?.lastMessages ?? []
    };

    if (entry?.lastActionableContext) {
      context.lastActionableContext = entry.lastActionableContext;
    }

    return context;
  }

  rememberMessage(chatId: string, text: string): void {
    const current = this.entries.get(chatId);
    const lastMessages = [...(current?.lastMessages ?? []), text].slice(-8);

    const next: ChatMemoryEntry = {
      chatId,
      lastMessages,
      updatedAt: new Date().toISOString()
    };

    if (current?.lastActionableContext) {
      next.lastActionableContext = current.lastActionableContext;
    }

    this.entries.set(chatId, next);
    this.persist();
  }

  rememberActionableContext(chatId: string, text: string): void {
    const current = this.entries.get(chatId);

    this.entries.set(chatId, {
      chatId,
      lastMessages: current?.lastMessages ?? [],
      lastActionableContext: text,
      updatedAt: new Date().toISOString()
    });
    this.persist();
  }

  private persist(): void {
    writeJsonFile(this.memoryFile, [...this.entries.values()]);
  }
}
