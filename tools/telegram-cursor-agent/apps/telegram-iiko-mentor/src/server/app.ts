import Fastify from "fastify";
import { z } from "zod";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import type { AgentRunner } from "../agent/agentRunner.js";
import type { TaskStore } from "../tasks/types.js";

interface CreateServerOptions {
  config: AppConfig;
  store: TaskStore;
  agentRunner: AgentRunner;
  startedAt: Date;
}

const consultBodySchema = z.object({
  question: z.string().min(1),
  context: z.string().optional()
});

const dialogTaskSchema = z.object({
  task: z.string(),
  result: z.string().optional(),
  review: z.string().optional()
});

const dialogPlanBodySchema = z.object({
  chatId: z.string().min(1),
  goal: z.string().optional(),
  context: z.string().optional(),
  previousTasks: z.array(dialogTaskSchema).optional()
});

const dialogReviewBodySchema = z.object({
  chatId: z.string().min(1),
  task: z.string().min(1),
  result: z.string().min(1),
  context: z.string().optional(),
  previousTasks: z.array(dialogTaskSchema).optional()
});

type DialogTaskHistoryItem = {
  task: string;
  result?: string | undefined;
  review?: string | undefined;
};

function formatPreviousTasks(tasks: DialogTaskHistoryItem[] | undefined): string {
  if (!tasks || tasks.length === 0) {
    return "Предыдущих задач в этом диалоге пока нет.";
  }

  return tasks
    .map((item, index) => {
      const lines = [`${index + 1}. Задача: ${item.task}`];

      if (item.result) {
        lines.push(`Результат Контролера: ${item.result}`);
      }

      if (item.review) {
        lines.push(`Проверка Наставника: ${item.review}`);
      }

      return lines.join("\n");
    })
    .join("\n\n");
}

export function createServer(options: CreateServerOptions) {
  const { config, store, agentRunner, startedAt } = options;
  const app = Fastify({ loggerInstance: logger });

  app.get("/health", async () => ({
    ok: true,
    service: "telegram-iiko-mentor"
  }));

  app.get("/status", async () => ({
    ok: true,
    uptimeSeconds: Math.round((Date.now() - startedAt.getTime()) / 1000),
    activeTasks: store.countActive(),
    publicBaseUrl: config.publicBaseUrl,
    workingDirectory: config.workingDirectory
  }));

  app.post("/consult", async (request, reply) => {
    const parsed = consultBodySchema.safeParse(request.body);

    if (!parsed.success) {
      return reply.status(400).send({
        ok: false,
        error: "Нужен текст вопроса в поле question."
      });
    }

    const input = [
      ...(parsed.data.context
        ? ["Контекст беседы:", parsed.data.context, ""]
        : []),
      "Вопрос от Контролера:",
      parsed.data.question
    ].join("\n");

    const task = store.create({
      kind: "agent",
      input,
      createdBy: 0
    });

    const result = await agentRunner.runAgentTask(task);

    if (result.status === "failed" || result.status === "rejected") {
      return reply.status(502).send({
        ok: false,
        error: result.error ?? "Наставник IIKO не смог ответить."
      });
    }

    return {
      ok: true,
      answer: result.output ?? "Готово."
    };
  });

  app.post("/dialog/plan", async (request, reply) => {
    const parsed = dialogPlanBodySchema.safeParse(request.body);

    if (!parsed.success) {
      return reply.status(400).send({
        ok: false,
        error: "Нужны chatId и при необходимости goal/context."
      });
    }

    const input = [
      "Режим /диалог: поставь первую или следующую задачу Контролеру.",
      ...(parsed.data.goal ? ["", `Цель диалога: ${parsed.data.goal}`] : []),
      ...(parsed.data.context ? ["", "Контекст беседы:", parsed.data.context] : []),
      "",
      "История задач в этом диалоге:",
      formatPreviousTasks(parsed.data.previousTasks)
    ].join("\n");

    try {
      const response = await agentRunner.runDialogTask(input, "plan");

      return {
        ok: true,
        task: response.task,
        done: response.done,
        summary: response.summary
      };
    } catch (error) {
      return reply.status(502).send({
        ok: false,
        error: error instanceof Error ? error.message : "Наставник IIKO не смог спланировать задачу."
      });
    }
  });

  app.post("/dialog/review", async (request, reply) => {
    const parsed = dialogReviewBodySchema.safeParse(request.body);

    if (!parsed.success) {
      return reply.status(400).send({
        ok: false,
        error: "Нужны chatId, task и result."
      });
    }

    const input = [
      "Режим /диалог: проверь выполненную задачу Контролера.",
      "",
      `Задача: ${parsed.data.task}`,
      "",
      `Результат Контролера: ${parsed.data.result}`,
      ...(parsed.data.context ? ["", "Контекст беседы:", parsed.data.context] : []),
      "",
      "История задач в этом диалоге:",
      formatPreviousTasks(parsed.data.previousTasks)
    ].join("\n");

    try {
      const response = await agentRunner.runDialogTask(input, "review");

      return {
        ok: true,
        task: response.task,
        done: response.done,
        summary: response.summary
      };
    } catch (error) {
      return reply.status(502).send({
        ok: false,
        error: error instanceof Error ? error.message : "Наставник IIKO не смог проверить результат."
      });
    }
  });

  return app;
}
