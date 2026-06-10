import { readJsonFile, writeJsonFile } from "../storage/fileStore.js";

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
  private readonly sessions = new Map<string, DialogSession>();

  constructor(private readonly dialogModeFile: string) {
    for (const session of readJsonFile<DialogSession[]>(this.dialogModeFile, [])) {
      this.sessions.set(session.chatId, session);
    }
  }

  isActive(chatId: string, now = Date.now()): boolean {
    const session = this.sessions.get(chatId);

    if (!session) {
      return false;
    }

    if (Date.parse(session.expiresAt) <= now) {
      this.stop(chatId);
      return false;
    }

    return true;
  }

  get(chatId: string): DialogSession | undefined {
    const session = this.sessions.get(chatId);

    if (!session) {
      return undefined;
    }

    if (Date.parse(session.expiresAt) <= Date.now()) {
      this.stop(chatId);
      return undefined;
    }

    return session;
  }

  start(
    chatId: string,
    startedBy: number,
    durationMs: number,
    goal?: string
  ): DialogSession {
    const now = Date.now();
    const session: DialogSession = {
      chatId,
      startedAt: new Date(now).toISOString(),
      expiresAt: new Date(now + durationMs).toISOString(),
      startedBy,
      ...(goal ? { goal } : {}),
      iteration: 0,
      tasks: [],
      running: true
    };

    this.sessions.set(chatId, session);
    this.persist();
    return session;
  }

  setRunning(chatId: string, running: boolean): DialogSession | undefined {
    const session = this.get(chatId);

    if (!session) {
      return undefined;
    }

    session.running = running;
    this.persist();
    return session;
  }

  addTask(chatId: string, task: string): DialogSession | undefined {
    const session = this.get(chatId);

    if (!session) {
      return undefined;
    }

    session.iteration += 1;
    session.tasks.push({ task });
    this.persist();
    return session;
  }

  completeTask(chatId: string, result: string, review: string): DialogSession | undefined {
    const session = this.get(chatId);

    if (!session || session.tasks.length === 0) {
      return undefined;
    }

    const current = session.tasks[session.tasks.length - 1]!;

    current.result = result;
    current.review = review;
    this.persist();
    return session;
  }

  stop(chatId: string): DialogSession | undefined {
    const session = this.sessions.get(chatId);

    if (!session) {
      return undefined;
    }

    session.running = false;
    this.sessions.delete(chatId);
    this.persist();
    return session;
  }

  remainingMinutes(chatId: string): number {
    const session = this.get(chatId);

    if (!session) {
      return 0;
    }

    const remainingMs = Date.parse(session.expiresAt) - Date.now();
    return Math.max(0, Math.ceil(remainingMs / 60_000));
  }

  private persist(): void {
    writeJsonFile(this.dialogModeFile, [...this.sessions.values()]);
  }
}
