import type { Bot } from "grammy";
import type { AgentRunner } from "../agent/agentRunner.js";
import type { ConversationLogStore } from "../chat/conversationLog.js";
import type { IikoMentorClient } from "../mentor/client.js";
import type { TaskStore } from "../tasks/types.js";
import type { DialogModeStore } from "./dialogModeStore.js";

interface DialogOrchestratorOptions {
  bot: Bot;
  dialogMode: DialogModeStore;
  mentorClient: IikoMentorClient;
  agentRunner: AgentRunner;
  store: TaskStore;
  conversationLog: ConversationLogStore;
  maxIterations?: number;
}

export class DialogOrchestrator {
  private readonly maxIterations: number;

  constructor(private readonly options: DialogOrchestratorOptions) {
    this.maxIterations = options.maxIterations ?? 8;
  }

  start(chatId: string, startedBy: number, durationMs: number, goal?: string): void {
    if (this.options.dialogMode.isActive(chatId)) {
      void this.sendMessage(
        chatId,
        "Режим /диалог уже идёт. Дождитесь окончания или остановите: /диалог_стоп"
      );
      return;
    }

    this.options.dialogMode.start(chatId, startedBy, durationMs, goal);

    void this.sendMessage(
      chatId,
      [
        "Запустил режим /диалог на 30 минут.",
        "Бот-наставник анализирует задачу, основной агент выполняет и вносит изменения в код.",
        goal ? `Цель: ${goal}` : "Цель: двигаться к текущей цели проекта notame.ru.",
        "",
        "Остановить досрочно: /диалог_стоп"
      ].join("\n")
    );

    void this.runLoop(chatId, goal);
  }

  stop(chatId: string): void {
    const session = this.options.dialogMode.stop(chatId);

    if (!session) {
      void this.sendMessage(chatId, "Активного режима /диалог сейчас нет.");
      return;
    }

    void this.sendMessage(
      chatId,
      [
        "Режим /диалог остановлен.",
        `Задач в этом цикле: ${session.tasks.length}.`,
        session.tasks.length > 0
          ? `Последняя проверка: ${session.tasks[session.tasks.length - 1]?.review ?? "без итога"}`
          : "Задач не было."
      ].join("\n")
    );
  }

  private async runLoop(chatId: string, goal?: string): Promise<void> {
    const { dialogMode, mentorClient, agentRunner, store, conversationLog } = this.options;

    try {
      for (let step = 0; step < this.maxIterations; step += 1) {
        if (!dialogMode.isActive(chatId)) {
          return;
        }

        const session = dialogMode.get(chatId);

        if (!session) {
          return;
        }

        const context = conversationLog.formatContext(chatId);
        const plan = await mentorClient.dialogPlan({
          chatId,
          ...(goal ? { goal } : {}),
          context,
          previousTasks: session.tasks
        });

        if (plan.done || !plan.task) {
          await this.finishDialog(
            chatId,
            plan.summary ?? "Наставник считает, что на этом этапе достаточно."
          );
          return;
        }

        dialogMode.addTask(chatId, plan.task);

        await this.sendMessage(
          chatId,
          [
            `Задача ${session.iteration + 1} от бота-наставника:`,
            plan.task,
            plan.summary ? `Зачем: ${plan.summary}` : ""
          ]
            .filter(Boolean)
            .join("\n")
        );

        const taskInput = [
          "Режим /диалог: выполни задачу от бота-наставника.",
          "Только основной агент вносит изменения в код.",
          "",
          `Задача: ${plan.task}`,
          ...(plan.summary ? ["", `Контекст от Наставника: ${plan.summary}`] : []),
          ...(goal ? ["", `Цель диалога: ${goal}`] : [])
        ].join("\n");

        const task = store.create({
          kind: "agent",
          input: taskInput,
          createdBy: session.startedBy
        });

        await this.sendMessage(chatId, "Основной агент выполняет задачу...");

        const result = await agentRunner.runAgentTask(task);
        const resultText =
          result.output ??
          result.error ??
          "Основной агент завершил задачу без текстового отчёта.";

        if (!dialogMode.isActive(chatId)) {
          return;
        }

        const previousTasks = dialogMode.get(chatId)?.tasks;
        const review = await mentorClient.dialogReview({
          chatId,
          task: plan.task,
          result: resultText,
          context,
          ...(previousTasks ? { previousTasks } : {})
        });

        dialogMode.completeTask(chatId, resultText, review.summary ?? "");

        await this.sendMessage(
          chatId,
          [
            `Проверка Наставника (задача ${session.iteration + 1}):`,
            review.summary ?? "Проверка завершена.",
            result.status === "failed" ? "Основной агент не смог выполнить задачу полностью." : ""
          ]
            .filter(Boolean)
            .join("\n")
        );

        if (review.done || !review.task) {
          await this.finishDialog(
            chatId,
            review.summary ?? "Наставник завершил цикл задач."
          );
          return;
        }
      }

      await this.finishDialog(
        chatId,
        `Достигнут лимит ${this.maxIterations} задач за один запуск /диалог. Можно запустить снова.`
      );
    } catch (error) {
      const message = error instanceof Error ? error.message : "unknown error";
      dialogMode.stop(chatId);
      await this.sendMessage(
        chatId,
        [
          "Режим /диалог прервался из-за ошибки.",
          `Причина: ${message}`,
          "Можно запустить заново: /диалог"
        ].join("\n")
      );
    } finally {
      dialogMode.setRunning(chatId, false);
    }
  }

  private async finishDialog(chatId: string, summary: string): Promise<void> {
    const activeSession = this.options.dialogMode.get(chatId);
    const taskCount = activeSession?.tasks.length ?? 0;
    const remaining = this.options.dialogMode.remainingMinutes(chatId);
    this.options.dialogMode.stop(chatId);

    await this.sendMessage(
      chatId,
      [
        "Режим /диалог завершён.",
        summary,
        taskCount > 0 ? `Задач выполнено: ${taskCount}.` : "",
        remaining > 0 ? `Оставалось минут: ${remaining}.` : ""
      ]
        .filter(Boolean)
        .join("\n")
    );
  }

  private async sendMessage(chatId: string, text: string): Promise<void> {
    await this.options.bot.api.sendMessage(chatId, text);
    this.options.conversationLog.append({
      chatId,
      direction: "out",
      kind: "text",
      text
    });
  }
}
