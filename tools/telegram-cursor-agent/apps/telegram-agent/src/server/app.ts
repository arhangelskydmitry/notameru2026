import Fastify from "fastify";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import type { TaskStore } from "../tasks/types.js";

interface CreateServerOptions {
  config: AppConfig;
  store: TaskStore;
  startedAt: Date;
}

export function createServer(options: CreateServerOptions) {
  const { config, store, startedAt } = options;
  const app = Fastify({ loggerInstance: logger });

  app.get("/health", async () => ({
    ok: true,
    service: "telegram-dev-bot"
  }));

  app.get("/status", async () => ({
    ok: true,
    uptimeSeconds: Math.round((Date.now() - startedAt.getTime()) / 1000),
    activeTasks: store.countActive(),
    publicBaseUrl: config.publicBaseUrl,
    workingDirectory: config.workingDirectory
  }));

  app.get("/tasks", async () => ({
    tasks: store.list(50)
  }));

  app.get<{ Params: { id: string } }>("/tasks/:id", async (request, reply) => {
    const task =
      store.get(request.params.id) ??
      store.list(100).find((candidate) =>
        candidate.id.startsWith(request.params.id)
      );

    if (!task) {
      return reply.code(404).send({ error: "Task not found" });
    }

    return { task };
  });

  return app;
}
