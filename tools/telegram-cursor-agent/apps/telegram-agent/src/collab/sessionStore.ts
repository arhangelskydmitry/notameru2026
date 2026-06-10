import { appendFileSync } from "node:fs";
import { ensureParentDirectory, readJsonFile, writeJsonFile } from "../storage/fileStore.js";

export interface WorkSession {
  id: string;
  chatId: string;
  title: string;
  startedBy: number;
  startedAt: string;
  endedAt?: string;
  messages: string[];
  decisions: string[];
}

export class SessionStore {
  private readonly sessions = new Map<string, WorkSession>();

  constructor(
    private readonly sessionsFile: string,
    private readonly decisionsFile: string
  ) {
    for (const session of readJsonFile<WorkSession[]>(this.sessionsFile, [])) {
      this.sessions.set(session.id, session);
    }
  }

  start(chatId: string, title: string, startedBy: number): WorkSession {
    const session: WorkSession = {
      id: crypto.randomUUID(),
      chatId,
      title: title || "Рабочая сессия",
      startedBy,
      startedAt: new Date().toISOString(),
      messages: [],
      decisions: []
    };

    this.sessions.set(session.id, session);
    this.persist();
    return session;
  }

  active(chatId: string): WorkSession | undefined {
    return [...this.sessions.values()]
      .filter((session) => session.chatId === chatId && !session.endedAt)
      .sort((left, right) => right.startedAt.localeCompare(left.startedAt))[0];
  }

  addMessage(chatId: string, message: string): void {
    const session = this.active(chatId);

    if (!session) {
      return;
    }

    session.messages.push(message);
    session.messages = session.messages.slice(-80);
    this.persist();
  }

  addDecision(chatId: string, decision: string): WorkSession | undefined {
    const session = this.active(chatId);

    if (!session) {
      return undefined;
    }

    session.decisions.push(decision);
    this.persist();
    this.appendDecision(session, decision);
    return session;
  }

  end(chatId: string): WorkSession | undefined {
    const session = this.active(chatId);

    if (!session) {
      return undefined;
    }

    session.endedAt = new Date().toISOString();
    this.persist();
    return session;
  }

  summary(chatId: string): string {
    const session = this.active(chatId);

    if (!session) {
      return "Активной рабочей сессии нет. Начните: /session_start <тема>";
    }

    return [
      `Сессия: ${session.title}`,
      `ID: ${session.id.slice(0, 8)}`,
      `Сообщений: ${session.messages.length}`,
      `Решений: ${session.decisions.length}`,
      "",
      session.decisions.length > 0
        ? `Решения:\n${session.decisions.map((item) => `- ${item}`).join("\n")}`
        : "Решений пока нет."
    ].join("\n");
  }

  private appendDecision(session: WorkSession, decision: string): void {
    ensureParentDirectory(this.decisionsFile);
    appendFileSync(
      this.decisionsFile,
      `\n## ${new Date().toISOString()} - ${session.title}\n\n- ${decision}\n`,
      { mode: 0o600 }
    );
  }

  private persist(): void {
    writeJsonFile(this.sessionsFile, [...this.sessions.values()]);
  }
}
