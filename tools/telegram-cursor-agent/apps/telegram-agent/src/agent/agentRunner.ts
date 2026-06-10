import { Agent } from "@cursor/sdk";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import type { Task, TaskStore } from "../tasks/types.js";
import type { EventLog } from "../storage/eventLog.js";
import { appendJsonLine } from "../storage/fileStore.js";
import type { BackupService, SnapshotMetadata } from "../backup/backupService.js";

function trimForTelegram(value: string, maxChars: number): string {
  if (value.length <= maxChars) {
    return value;
  }

  return `${value.slice(0, maxChars - 80)}\n\n... trimmed, see server logs for full output ...`;
}

function buildAgentPrompt(input: string): string {
  return [
    "Ты работаешь как полноценный Cursor-агент из Telegram-чата проекта notame.ru.",
    "Проект — Laravel-сайт сетевого издания «Нота Миру» и связанное Mac-приложение для обслуживания сайта.",
    "Ты работаешь прямо в production path, поэтому перед запуском задачи сервис уже создаёт резервную копию файлов.",
    "Воспринимай сообщение пользователя как задачу на разработку, документацию, диагностику, контент или продуктовые изменения сайта.",
    "Если задача понятна и безопасна, внеси необходимые изменения в код или документацию.",
    "Если не хватает критического контекста, не выдумывай: в результате задай короткий уточняющий вопрос.",
    "Не выполняй опасные действия без явного подтверждения через Telegram: deploy, удаление данных, sudo/systemd/nginx, миграции, composer/npm install, git reset/clean/force, работа с секретами.",
    "Не раскрывай токены, ключи, пароли и содержимое env-файлов.",
    "После работы дай короткий отчет для Telegram: что изменилось, что проверено, какой практический результат, что нужно от людей дальше.",
    "Не пиши task id, run id, stack trace, внутренние системные детали и длинные технические простыни, если пользователь об этом прямо не просит.",
    "Не используй декоративные нейросимволы, эмодзи и канцелярит. Пиши спокойно, как надежный помощник в рабочем чате.",
    "",
    "Сообщение из Telegram:",
    input
  ].join("\n");
}

export class AgentRunner {
  constructor(
    private readonly store: TaskStore,
    private readonly config: Pick<
      AppConfig,
      | "cursorApiKey"
      | "cursorModel"
      | "workingDirectory"
      | "logTailChars"
      | "maxAgentPromptChars"
      | "failedQueueFile"
      | "agentRunTimeoutMs"
    >
    ,
    private readonly eventLog: EventLog,
    private readonly backups: BackupService
  ) {}

  async runAgentTask(task: Task): Promise<Task> {
    if (!this.config.cursorApiKey) {
      return this.store.update(task.id, {
        status: "rejected",
        finishedAt: new Date().toISOString(),
        error: "Cursor agent is not configured: CURSOR_API_KEY is missing"
      })!;
    }

    const input =
      task.input.length > this.config.maxAgentPromptChars
        ? task.input.slice(0, this.config.maxAgentPromptChars)
        : task.input;

    let snapshot: SnapshotMetadata | null = null;

    try {
      snapshot = await this.backups.createSnapshot(task.id);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unknown backup error";
      return this.store.update(task.id, {
        status: "failed",
        finishedAt: new Date().toISOString(),
        error: `Не удалось создать резервную копию перед запуском агента: ${message}`
      })!;
    }

    this.store.update(task.id, {
      status: "running",
      startedAt: new Date().toISOString(),
      backupId: snapshot.id
    });

    logger.info({ taskId: task.id, backupId: snapshot.id }, "Starting Cursor agent task");
    this.eventLog.write("agent.started", {
      taskId: task.id,
      backupId: snapshot.id,
      input: task.input
    });

    try {
      const result = await Promise.race([
        Agent.prompt(buildAgentPrompt(input), {
          apiKey: this.config.cursorApiKey,
          model: { id: this.config.cursorModel },
          local: { cwd: this.config.workingDirectory }
        }),
        new Promise<never>((_, reject) => {
          setTimeout(
            () =>
              reject(
                new Error(`Cursor agent timed out after ${this.config.agentRunTimeoutMs}ms`)
              ),
            this.config.agentRunTimeoutMs
          );
        })
      ]);

      const status = result.status === "finished" ? "succeeded" : "failed";
      const output = trimForTelegram(
        String(result.result ?? `Cursor run finished with status: ${result.status}`),
        this.config.logTailChars
      );

      const finalizedSnapshot = await this.backups.finalizeSnapshot(snapshot.id);
      const changedFiles = finalizedSnapshot.changedFiles ?? [];
      const changeSummary =
        changedFiles.length > 0
          ? `\n\nИзменённые файлы:\n${changedFiles.map((file) => `- ${file}`).join("\n")}`
          : "\n\nФайловых изменений после задачи не обнаружено.";

      const successPatch: Partial<Omit<Task, "id" | "createdAt">> = {
        status,
        finishedAt: new Date().toISOString(),
        output: `${output}${changeSummary}`,
        runId: result.id,
        backupId: snapshot.id,
        changedFiles
      };

      if (finalizedSnapshot.diffSummary !== undefined) {
        successPatch.diffSummary = finalizedSnapshot.diffSummary;
      }

      const updated = this.store.update(task.id, successPatch)!;

      logger.info({ taskId: task.id, runId: result.id, status }, "Cursor agent task finished");
      this.eventLog.write("agent.finished", {
        taskId: task.id,
        runId: result.id,
        backupId: snapshot.id,
        changedFiles,
        status
      });

      if (status === "failed") {
        appendJsonLine(this.config.failedQueueFile, {
          ts: new Date().toISOString(),
          taskId: task.id,
          input: task.input,
          runId: result.id,
          status
        });
      }

      return updated;
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unknown error";
      let finalizedSnapshot: SnapshotMetadata | null = null;

      try {
        finalizedSnapshot = await this.backups.finalizeSnapshot(snapshot.id);
      } catch {
        finalizedSnapshot = null;
      }

      const failurePatch: Partial<Omit<Task, "id" | "createdAt">> = {
        status: "failed",
        finishedAt: new Date().toISOString(),
        backupId: snapshot.id,
        error: message
      };

      if (finalizedSnapshot?.changedFiles !== undefined) {
        failurePatch.changedFiles = finalizedSnapshot.changedFiles;
      }

      if (finalizedSnapshot?.diffSummary !== undefined) {
        failurePatch.diffSummary = finalizedSnapshot.diffSummary;
      }

      const updated = this.store.update(task.id, failurePatch)!;

      logger.error({ taskId: task.id, error }, "Cursor agent task failed");
      this.eventLog.write("agent.failed", { taskId: task.id, error: message });
      appendJsonLine(this.config.failedQueueFile, {
        ts: new Date().toISOString(),
        taskId: task.id,
        input: task.input,
        error: message
      });
      return updated;
    }
  }
}
