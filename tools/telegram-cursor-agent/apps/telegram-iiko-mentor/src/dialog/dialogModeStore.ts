import { readJsonFile } from "../storage/fileStore.js";

export interface DialogTaskRecord {
  task: string;
  result?: string;
  review?: string;
}

export interface DialogSession {
  chatId: string;
  startedAt: string;
  expiresAt: string;
  startedBy: number;
  goal?: string;
  iteration: number;
  tasks: DialogTaskRecord[];
  running: boolean;
}

export class DialogModeStore {
  constructor(private readonly dialogModeFile: string) {}

  isActive(chatId: string, now = Date.now()): boolean {
    const session = this.get(chatId, now);
    return session !== undefined;
  }

  get(chatId: string, now = Date.now()): DialogSession | undefined {
    for (const session of readJsonFile<DialogSession[]>(this.dialogModeFile, [])) {
      if (session.chatId !== chatId) {
        continue;
      }

      if (Date.parse(session.expiresAt) <= now) {
        return undefined;
      }

      return session;
    }

    return undefined;
  }
}
