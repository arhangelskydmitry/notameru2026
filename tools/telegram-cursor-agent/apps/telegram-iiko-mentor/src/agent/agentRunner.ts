import { Agent } from "@cursor/sdk";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import type { Task, TaskStore } from "../tasks/types.js";
import type { EventLog } from "../storage/eventLog.js";
import { appendJsonLine } from "../storage/fileStore.js";
import {
  AGENT_TASK_STATUS,
  formatAgentStepStatus
} from "./agentStatusText.js";
import {
  buildDialogPlanPrompt,
  buildDialogReviewPrompt,
  parseDialogAgentResponse,
  type DialogAgentResponse
} from "./dialogPrompt.js";

function trimForTelegram(value: string, maxChars: number): string {
  if (value.length <= maxChars) {
    return value;
  }

  return `${value.slice(0, maxChars - 80)}\n\n... ответ обрезан, полный текст в логах сервера ...`;
}

function buildAgentPrompt(input: string): string {
  return [
    "Ты работаешь как Telegram-бот «Наставник IIKO» проекта notame.pro / iiko Control.",
    "Твоя роль — маркетолог и специалист по IIKO: помогать тестировать программу, объяснять экраны и отчёты, формулировать требования простым языком, давать обратную связь по UX.",
    "Дима и Елена Петровна — равноправные партнеры проекта.",
    "Елена Петровна не программист: объясняй простым человеческим языком, с пользой для управления, финансов и контроля ресторанов.",
    "Если обращаешься к Елене Петровне напрямую, пожелай ей хорошего дня.",
    "Если обращаешься к конкретному партнеру, обязательно ставь тег @dm9998111 или @osipovalena.",
    "Если Дима пишет Елене Петровне (или наоборот) без вопроса к тебе — не вмешивайся.",
    "Ты НЕ разработчик. Тебе ЗАПРЕЩЕНО изменять файлы, код и документацию — только чтение.",
    "Изменения в код вносит только Контролер (@CursorLenaPetrovna_bot).",
    "Можешь читать код и документацию только чтобы понять продукт и ответить на вопрос.",
    "Чек-лист тестирования: docs/iiko-testing-checklist.md. API: docs/iiko-control-api.md.",
    "Если нашёл баг, несоответствие или нужна доработка кода — чётко опиши проблему и предложи передать задачу Контролеру (напиши @dm9998111).",
    "Не раскрывай токены, ключи, пароли и содержимое env-файлов.",
    "После работы дай короткий отчёт для Telegram: что проверил или объяснил, практический вывод, нужна ли эскалация Контролеру.",
    "Не пиши task id, run id, stack trace и длинные технические простыни, если об этом не просят.",
    "Не используй декоративные нейросимволы, эмодзи и канцелярит. Пиши спокойно, как надёжный помощник в рабочем чате.",
    "",
    "Сообщение из Telegram:",
    input
  ].join("\n");
}

export interface AgentRunCallbacks {
  onStatusUpdate?: (status: string) => void | Promise<void>;
}

export type DialogAgentMode = "plan" | "review";

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
      | "eventLogFile"
      | "agentRunTimeoutMs"
    >,
    private readonly eventLog: EventLog
  ) {}

  async runDialogTask(
    input: string,
    mode: DialogAgentMode,
    callbacks?: AgentRunCallbacks
  ): Promise<DialogAgentResponse> {
    if (!this.config.cursorApiKey) {
      throw new Error("Агент не настроен: отсутствует CURSOR_API_KEY");
    }

    const task = this.store.create({
      kind: "agent",
      input,
      createdBy: 0
    });

    this.store.update(task.id, {
      status: "running",
      startedAt: new Date().toISOString()
    });

    logger.info({ taskId: task.id, mode }, "Starting IIKO mentor dialog task");
    this.eventLog.write("mentor.dialog.started", { taskId: task.id, mode, input });

    const prompt =
      mode === "plan" ? buildDialogPlanPrompt(input) : buildDialogReviewPrompt(input);
    const result = await this.runPromptTask(task, prompt, callbacks);

    if (result.status === "failed" || result.status === "rejected") {
      throw new Error(result.error ?? "Наставник IIKO не смог ответить в режиме /диалог");
    }

    return parseDialogAgentResponse(result.output ?? "");
  }

  async runAgentTask(task: Task, callbacks?: AgentRunCallbacks): Promise<Task> {
    if (!this.config.cursorApiKey) {
      return this.store.update(task.id, {
        status: "rejected",
        finishedAt: new Date().toISOString(),
        error: "Агент не настроен: отсутствует CURSOR_API_KEY"
      })!;
    }

    const input =
      task.input.length > this.config.maxAgentPromptChars
        ? task.input.slice(0, this.config.maxAgentPromptChars)
        : task.input;

    this.store.update(task.id, {
      status: "running",
      startedAt: new Date().toISOString()
    });

    logger.info({ taskId: task.id }, "Starting IIKO mentor agent task");
    this.eventLog.write("mentor.started", { taskId: task.id, input: task.input });

    return this.runPromptTask(task, buildAgentPrompt(input), callbacks);
  }

  private async runPromptTask(
    task: Task,
    prompt: string,
    callbacks?: AgentRunCallbacks
  ): Promise<Task> {
    if (!this.config.cursorApiKey) {
      return this.store.update(task.id, {
        status: "rejected",
        finishedAt: new Date().toISOString(),
        error: "Агент не настроен: отсутствует CURSOR_API_KEY"
      })!;
    }

    const agent = await Agent.create({
      apiKey: this.config.cursorApiKey,
      model: { id: this.config.cursorModel },
      local: { cwd: this.config.workingDirectory }
    });

    const notifyStatus = async (status: string): Promise<void> => {
      if (callbacks?.onStatusUpdate) {
        await callbacks.onStatusUpdate(status);
      }
    };

    try {
      await notifyStatus(AGENT_TASK_STATUS.accepted);

      const run = await agent.send(prompt, {
        onStep: async ({ step }) => {
          const status = formatAgentStepStatus(step);

          if (status) {
            await notifyStatus(status);
          }
        }
      });

      const result = await Promise.race([
        run.wait(),
        new Promise<never>((_, reject) => {
          setTimeout(
            () =>
              reject(
                new Error(`Агент не ответил за ${this.config.agentRunTimeoutMs}ms`)
              ),
            this.config.agentRunTimeoutMs
          );
        })
      ]);

      await notifyStatus(AGENT_TASK_STATUS.finishing);

      const status = result.status === "finished" ? "succeeded" : "failed";
      const output = trimForTelegram(
        String(result.result ?? `Агент завершил работу со статусом: ${result.status}`),
        this.config.logTailChars
      );

      const updated = this.store.update(task.id, {
        status,
        finishedAt: new Date().toISOString(),
        output,
        runId: result.id
      })!;

      logger.info({ taskId: task.id, runId: result.id, status }, "IIKO mentor task finished");
      this.eventLog.write("mentor.finished", {
        taskId: task.id,
        runId: result.id,
        status
      });

      if (status === "failed") {
        appendJsonLine(this.config.eventLogFile, {
          ts: new Date().toISOString(),
          event: "mentor.failed",
          taskId: task.id,
          input: task.input,
          runId: result.id,
          status
        });
      }

      return updated;
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unknown error";
      const updated = this.store.update(task.id, {
        status: "failed",
        finishedAt: new Date().toISOString(),
        error: message
      })!;

      logger.error({ taskId: task.id, error }, "IIKO mentor task failed");
      this.eventLog.write("mentor.failed", { taskId: task.id, error: message });
      return updated;
    } finally {
      agent.close();
    }
  }
}
